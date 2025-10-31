<?php

declare(strict_types=1);

namespace Tourze\WarehouseOperationBundle\Service\Count;

use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Tourze\WarehouseOperationBundle\Entity\CountTask;
use Tourze\WarehouseOperationBundle\Enum\TaskStatus;
use Tourze\WarehouseOperationBundle\Event\CountDiscrepancyEvent;
use Tourze\WarehouseOperationBundle\Repository\CountTaskRepository;

/**
 * 盘点差异处理服务
 *
 * 专门负责盘点差异的识别、分类和处理逻辑。
 * 使用策略模式处理不同类型的差异。
 */
final class CountDiscrepancyHandlerService
{
    private EventDispatcherInterface $eventDispatcher;

    private CountTaskRepository $countTaskRepository;

    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        CountTaskRepository $countTaskRepository,
    ) {
        $this->eventDispatcher = $eventDispatcher;
        $this->countTaskRepository = $countTaskRepository;
    }

    /**
     * 处理盘点差异
     *
     * @param array<string, mixed> $discrepancyData
     * @param array<string, mixed> $handlingOptions
     * @return array<string, mixed>
     */
    public function handleDiscrepancy(CountTask $task, array $discrepancyData, array $handlingOptions = []): array
    {
        $discrepancyInfo = $this->extractDiscrepancyInfo($discrepancyData);
        $thresholds = $this->getDiscrepancyThresholds($handlingOptions);

        $handlingStrategy = $this->determineHandlingStrategy($discrepancyInfo, $thresholds);

        $this->updateTaskWithHandling($task, $handlingStrategy, $discrepancyInfo);

        $action = is_string($handlingStrategy['action']) ? $handlingStrategy['action'] : 'auto_adjust';

        if ('recount' === $action) {
            $this->createRecountTask($task, $discrepancyData);
        }

        $this->countTaskRepository->save($task);

        $notificationSent = $this->sendDiscrepancyNotification($task, $action, $discrepancyData);

        return [
            'handling_action' => $action,
            'adjustment_amount' => $discrepancyInfo['value_impact'],
            'approval_required' => $handlingStrategy['approval_required'],
            'follow_up_tasks' => $handlingStrategy['follow_up_tasks'],
            'notification_sent' => $notificationSent,
        ];
    }

    /**
     * 检查并处理盘点差异
     *
     * @param array<string, mixed> $countData
     * @return array<array<string, mixed>>
     */
    public function checkForDiscrepancies(CountTask $task, array $countData): array
    {
        $systemQuantity = is_numeric($countData['system_quantity'] ?? null)
            ? (float) $countData['system_quantity']
            : 0.0;
        $actualQuantity = is_numeric($countData['actual_quantity'] ?? null)
            ? (float) $countData['actual_quantity']
            : 0.0;

        $discrepancies = [];

        if ($systemQuantity !== $actualQuantity) {
            $discrepancyData = [
                'discrepancy_type' => 'quantity',
                'quantity_difference' => $actualQuantity - $systemQuantity,
                'system_quantity' => $systemQuantity,
                'actual_quantity' => $actualQuantity,
                'location_code' => $countData['location_code'] ?? '',
                'product_info' => $countData['product_info'] ?? [],
            ];

            $discrepancies[] = $discrepancyData;

            $this->dispatchDiscrepancyEvent($task, $discrepancyData);
        }

        return $discrepancies;
    }

    /**
     * 提取差异信息
     *
     * @param array<string, mixed> $discrepancyData
     * @return array<string, mixed>
     */
    private function extractDiscrepancyInfo(array $discrepancyData): array
    {
        $quantityDiff = $discrepancyData['quantity_difference'] ?? 0;
        $valueImpact = $discrepancyData['value_impact'] ?? 0;

        return [
            'quantity_difference' => abs(is_numeric($quantityDiff) ? (float) $quantityDiff : 0),
            'value_impact' => abs(is_numeric($valueImpact) ? (float) $valueImpact : 0),
        ];
    }

    /**
     * 获取差异阈值
     *
     * @param array<string, mixed> $handlingOptions
     * @return array<string, mixed>
     */
    private function getDiscrepancyThresholds(array $handlingOptions): array
    {
        return [
            'auto_adjust_threshold' => $handlingOptions['auto_adjust_threshold'] ?? 100,
            'supervisor_threshold' => $handlingOptions['supervisor_threshold'] ?? 1000,
        ];
    }

    /**
     * 确定处理策略
     *
     * @param array<string, mixed> $discrepancyInfo
     * @param array<string, mixed> $thresholds
     * @return array<string, mixed>
     */
    private function determineHandlingStrategy(array $discrepancyInfo, array $thresholds): array
    {
        $valueImpact = $discrepancyInfo['value_impact'];
        $quantityDifference = $discrepancyInfo['quantity_difference'];

        if ($valueImpact > $thresholds['supervisor_threshold']) {
            return [
                'action' => 'manager_escalation',
                'approval_required' => true,
                'follow_up_tasks' => ['manager_approval_required'],
            ];
        }

        if ($valueImpact > $thresholds['auto_adjust_threshold'] || $quantityDifference > 10) {
            return [
                'action' => 'supervisor_review',
                'approval_required' => true,
                'follow_up_tasks' => ['supervisor_review_required'],
            ];
        }

        if ($quantityDifference > 5) {
            return [
                'action' => 'recount',
                'approval_required' => false,
                'follow_up_tasks' => ['schedule_recount_task'],
            ];
        }

        return [
            'action' => 'auto_adjust',
            'approval_required' => false,
            'follow_up_tasks' => [],
        ];
    }

    /**
     * 更新任务处理信息
     *
     * @param array<string, mixed> $strategy
     * @param array<string, mixed> $discrepancyInfo
     */
    private function updateTaskWithHandling(CountTask $task, array $strategy, array $discrepancyInfo): void
    {
        $taskData = $task->getTaskData();
        $taskData['discrepancy_handling'] = [
            'handling_action' => $strategy['action'],
            'value_impact' => $discrepancyInfo['value_impact'],
            'quantity_difference' => $discrepancyInfo['quantity_difference'],
            'handling_timestamp' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
            'approval_required' => $strategy['approval_required'],
        ];
        $task->setTaskData($taskData);
    }

    /**
     * 创建复盘任务
     *
     * @param array<string, mixed> $discrepancyData
     */
    private function createRecountTask(CountTask $originalTask, array $discrepancyData): void
    {
        $recountTask = new CountTask();
        $recountTask->setTaskType('recount');
        $recountTask->setTaskName($originalTask->getTaskName() . ' - 复盘');
        $recountTask->setPriority($originalTask->getPriority() + 10);
        $recountTask->setStatus(TaskStatus::PENDING);

        $taskData = $originalTask->getTaskData();
        $taskData['original_task_id'] = $originalTask->getId();
        $taskData['recount_reason'] = 'discrepancy_found';
        $taskData['discrepancy_data'] = $discrepancyData;
        $recountTask->setTaskData($taskData);

        $this->countTaskRepository->save($recountTask);
    }

    /**
     * 发送差异通知
     *
     * @param array<string, mixed> $discrepancyData
     */
    private function sendDiscrepancyNotification(CountTask $task, string $handlingAction, array $discrepancyData): bool
    {
        // 实际项目中会集成通知系统
        return true;
    }

    /**
     * 派发差异事件
     *
     * @param array<string, mixed> $discrepancyData
     */
    private function dispatchDiscrepancyEvent(CountTask $task, array $discrepancyData): void
    {
        $event = new CountDiscrepancyEvent($task, $discrepancyData);
        $this->eventDispatcher->dispatch($event);
    }
}
