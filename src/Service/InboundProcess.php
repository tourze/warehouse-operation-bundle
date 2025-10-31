<?php

namespace Tourze\WarehouseOperationBundle\Service;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Tourze\ProductServiceContracts\SKU;
use Tourze\WarehouseOperationBundle\Entity\InboundTask;
use Tourze\WarehouseOperationBundle\Enum\TaskStatus;
use Tourze\WarehouseOperationBundle\Enum\TaskType;
use Tourze\WarehouseOperationBundle\Event\TaskCompletedEvent;
use Tourze\WarehouseOperationBundle\Event\TaskCreatedEvent;
use Tourze\WarehouseOperationBundle\Event\TaskStartedEvent;
use Tourze\WarehouseOperationBundle\Exception\TaskNotFoundException;
use Tourze\WarehouseOperationBundle\Exception\TaskStatusException;
use Tourze\WarehouseOperationBundle\Repository\WarehouseTaskRepository;

/**
 * 入库流程服务
 *
 * 实现收货→质检→上架的完整入库业务流程管理。
 * 支持状态机转换、质检结果处理和库存集成。
 */
class InboundProcess implements InboundProcessInterface
{
    public function __construct(
        private readonly TaskManagerInterface $taskManager,
        private readonly StockIntegrationService $stockIntegrationService,
        private readonly WarehouseTaskRepository $taskRepository,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
    }

    /**
     * 开始入库流程
     *
     * @param array<array<string, mixed>> $items 入库商品列表
     * @param int|null $warehouseId 目标仓库ID（可选）
     * @return InboundTask 入库任务对象
     */
    public function startInbound(array $items, ?int $warehouseId = null): InboundTask
    {
        $task = $this->taskManager->createTask(TaskType::INBOUND, [
            'items' => $items,
            'warehouse_id' => $warehouseId,
            'step' => 'receiving',
        ]);

        if (!$task instanceof InboundTask) {
            throw new TaskStatusException('Created task is not an InboundTask');
        }

        $this->eventDispatcher->dispatch(new TaskCreatedEvent(
            $task,
            'system',
            'system',
            [
                'items' => $items,
                'warehouse_id' => $warehouseId,
                'step' => 'receiving',
                'reason' => 'inbound_process_started',
            ]
        ));

        return $task;
    }

    /**
     * 执行收货作业
     *
     * @param int $taskId 任务ID
     * @param array<array<string, mixed>> $actualItems 实际收货商品数据
     * @return bool 执行是否成功
     */
    public function executeReceiving(int $taskId, array $actualItems): bool
    {
        $task = $this->getTask($taskId);

        if (!$task instanceof InboundTask) {
            throw new TaskStatusException(sprintf('Task %d is not an inbound task', $taskId));
        }

        if (TaskStatus::PENDING !== $task->getStatus()) {
            throw new TaskStatusException(sprintf('Task %d is not in PENDING status, current status: %s', $taskId, strtoupper($task->getStatus()->value)));
        }

        $task->setStatus(TaskStatus::IN_PROGRESS);
        $task->setData([
            'step' => 'quality_check',
            'received_items' => $actualItems,
        ]);

        $this->taskRepository->save($task);
        $this->eventDispatcher->dispatch(new TaskStartedEvent(
            $task,
            1, // 默认系统作业员ID
            new \DateTimeImmutable(),
            [
                'step' => 'quality_check',
                'received_items' => $actualItems,
                'estimated_duration_seconds' => 3600,
                'precondition_check' => ['passed' => true],
            ],
            [
                'workstation_id' => 'receiving_station',
                'equipment' => ['scanner' => true, 'printer' => true],
            ]
        ));

        return true;
    }

    /**
     * 获取任务实体
     *
     * @throws TaskNotFoundException
     */
    private function getTask(int $taskId): mixed
    {
        $task = $this->taskRepository->find($taskId);

        if (null === $task) {
            throw new TaskNotFoundException(sprintf('Task with ID %d not found', $taskId));
        }

        return $task;
    }

    /**
     * 执行质检作业
     *
     * @param int $taskId 任务ID
     * @param array<array<string, mixed>> $qualityResults 质检结果数据
     * @return bool 执行是否成功
     */
    public function executeQualityCheck(int $taskId, array $qualityResults): bool
    {
        $task = $this->getTask($taskId);

        if (!$task instanceof InboundTask) {
            throw new TaskStatusException(sprintf('Task %d is not an inbound task', $taskId));
        }

        if (TaskStatus::IN_PROGRESS !== $task->getStatus()) {
            throw new TaskStatusException(sprintf('Task %d is not in IN_PROGRESS status, current status: %s', $taskId, strtoupper($task->getStatus()->value)));
        }

        // 分离通过和未通过的商品
        /** @var array<array<string, mixed>> $passedItems */
        $passedItems = [];
        /** @var array<array<string, mixed>> $rejectedItems */
        $rejectedItems = [];

        foreach ($qualityResults as $result) {
            if (isset($result['passed']) && true === $result['passed']) {
                $passedItems[] = $result;
            } else {
                $rejectedItems[] = $result;
            }
        }

        // 如果所有商品都未通过质检，创建退货任务并完成当前任务
        if (0 === count($passedItems)) {
            $this->taskManager->createTask(TaskType::OUTBOUND, [
                'type' => 'return',
                'original_task_id' => $taskId,
                'items' => $rejectedItems,
            ]);

            $task->setStatus(TaskStatus::COMPLETED);
            $this->taskRepository->save($task);
            $this->eventDispatcher->dispatch(new TaskCompletedEvent(
                $task,
                1, // 默认系统作业员ID
                new \DateTimeImmutable(),
                [
                    'completion_reason' => 'quality_check_failed',
                    'all_items_rejected' => true,
                    'rejected_items' => $rejectedItems,
                    'return_task_created' => true,
                    'actual_quantity' => 0,
                    'target_quantity' => count($rejectedItems),
                ],
                [
                    'efficiency_score' => 0.0,
                    'quality_score' => 0.0,
                    'on_time' => true,
                ]
            ));

            return true;
        }

        // 更新任务数据，准备上架
        $task->setData([
            'step' => 'putaway',
            'quality_results' => $qualityResults,
            'passed_items' => $passedItems,
            'rejected_items' => $rejectedItems,
        ]);

        $this->taskRepository->save($task);

        return true;
    }

    /**
     * 执行上架作业
     *
     * @param int $taskId 任务ID
     * @param array<array<string, mixed>> $locationAssignments 位置分配数据
     * @return bool 执行是否成功
     */
    public function executePutaway(int $taskId, array $locationAssignments): bool
    {
        $task = $this->getTask($taskId);

        if (!$task instanceof InboundTask) {
            throw new TaskStatusException(sprintf('Task %d is not an inbound task', $taskId));
        }

        if (TaskStatus::IN_PROGRESS !== $task->getStatus()) {
            throw new TaskStatusException(sprintf('Task %d is not in IN_PROGRESS status, current status: %s', $taskId, strtoupper($task->getStatus()->value)));
        }

        if (!$this->processStockInbound($task, $taskId, $locationAssignments)) {
            return false;
        }

        // 完成任务
        $this->completeTask($task, $locationAssignments);

        return true;
    }

    /**
     * 处理库存入库
     *
     * @param array<array<string, mixed>> $locationAssignments
     */
    private function processStockInbound(InboundTask $task, int $taskId, array $locationAssignments): bool
    {
        try {
            $inboundData = $this->prepareInboundData($taskId, $locationAssignments);
            $this->stockIntegrationService->processInbound($inboundData);

            return true;
        } catch (\Exception $e) {
            // 库存操作失败，标记任务失败
            $task->setStatus(TaskStatus::FAILED);
            $this->taskRepository->save($task);

            return false;
        }
    }

    /**
     * 准备入库数据
     *
     * @param array<array<string, mixed>> $locationAssignments
     * @return array{
     *     purchase_order_no: string,
     *     items: array<array{
     *         sku: SKU,
     *         batch_no: string,
     *         quantity: int,
     *         unit_cost: float,
     *         quality_level: string,
     *         location_id: string
     *     }>,
     *     operator: string,
     *     location_id?: string,
     *     notes: string
     * }
     */
    private function prepareInboundData(int $taskId, array $locationAssignments): array
    {
        $firstLocationCode = $locationAssignments[0]['location_code'] ?? null;
        $locationCode = is_string($firstLocationCode) ? $firstLocationCode : '';

        $inboundData = [
            'purchase_order_no' => 'WH-IN-' . $taskId,
            'items' => [],
            'operator' => 'system',
            'notes' => 'Warehouse putaway operation',
        ];

        // 只在有有效的 location_id 时添加
        if ('' !== $locationCode) {
            $inboundData['location_id'] = $locationCode;
        }

        foreach ($locationAssignments as $assignment) {
            $item = $this->prepareInboundItem($assignment);
            if (null !== $item) {
                $inboundData['items'][] = $item;
            }
        }

        return $inboundData;
    }

    /**
     * 准备入库项目数据
     *
     * @param array<string, mixed> $assignment
     * @return array{
     *     sku: SKU,
     *     batch_no: string,
     *     quantity: int,
     *     unit_cost: float,
     *     quality_level: string,
     *     location_id: string
     * }|null
     */
    private function prepareInboundItem(array $assignment): ?array
    {
        $sku = $assignment['sku'] ?? null;
        if (!($sku instanceof SKU)) {
            return null; // 跳过无效的 SKU
        }

        $batchNo = is_string($assignment['batch_no'] ?? null) ? $assignment['batch_no'] : '';
        $quantity = is_int($assignment['quantity'] ?? null) ? $assignment['quantity'] : 0;
        $locationCodeItem = is_string($assignment['location_code'] ?? null) ? $assignment['location_code'] : '';

        return [
            'sku' => $sku,
            'batch_no' => $batchNo,
            'quantity' => $quantity,
            'unit_cost' => 0.0,
            'quality_level' => 'A',
            'location_id' => $locationCodeItem,
        ];
    }

    /**
     * 完成任务
     *
     * @param array<array<string, mixed>> $locationAssignments
     */
    private function completeTask(InboundTask $task, array $locationAssignments): void
    {
        $task->setStatus(TaskStatus::COMPLETED);
        $task->setData([
            'step' => 'completed',
            'location_assignments' => $locationAssignments,
            'completed_at' => (new \DateTime())->format('Y-m-d H:i:s'),
        ]);

        $this->taskRepository->save($task);
        $this->eventDispatcher->dispatch(new TaskCompletedEvent(
            $task,
            1,
            new \DateTimeImmutable(),
            [
                'completion_reason' => 'putaway_completed',
                'location_assignments' => $locationAssignments,
                'stock_updated' => true,
                'actual_quantity' => count($locationAssignments),
                'target_quantity' => count($locationAssignments),
            ],
            [
                'efficiency_score' => 1.0,
                'quality_score' => 1.0,
                'on_time' => true,
                'duration_seconds' => null,
            ]
        ));
    }
}
