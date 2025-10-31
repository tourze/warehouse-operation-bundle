<?php

declare(strict_types=1);

namespace Tourze\WarehouseOperationBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Tourze\WarehouseOperationBundle\Entity\CountPlan;
use Tourze\WarehouseOperationBundle\Entity\CountTask;
use Tourze\WarehouseOperationBundle\Enum\TaskStatus;
use Tourze\WarehouseOperationBundle\Event\TaskCreatedEvent;
use Tourze\WarehouseOperationBundle\Repository\CountTaskRepository;
use Tourze\WarehouseOperationBundle\Service\Count\CountAnalysisService;
use Tourze\WarehouseOperationBundle\Service\Count\CountDataValidatorService;
use Tourze\WarehouseOperationBundle\Service\Count\CountDiscrepancyHandlerService;
use Tourze\WarehouseOperationBundle\Service\Count\CountPlanGeneratorService;

/**
 * 盘点管理服务实现
 *
 * 重构后的主服务类，协调各个子服务完成盘点业务逻辑。
 * 通过依赖注入使用专门的子服务，降低认知复杂度。
 */
class InventoryCountService implements InventoryCountServiceInterface
{
    private EntityManagerInterface $entityManager;

    private EventDispatcherInterface $eventDispatcher;

    private CountTaskRepository $countTaskRepository;

    private CountPlanGeneratorService $planGeneratorService;

    private CountDiscrepancyHandlerService $discrepancyHandlerService;

    private CountAnalysisService $analysisService;

    private CountDataValidatorService $dataValidatorService;

    public function __construct(
        EntityManagerInterface $entityManager,
        EventDispatcherInterface $eventDispatcher,
        CountTaskRepository $countTaskRepository,
        CountPlanGeneratorService $planGeneratorService,
        CountDiscrepancyHandlerService $discrepancyHandlerService,
        CountAnalysisService $analysisService,
        CountDataValidatorService $dataValidatorService,
    ) {
        $this->entityManager = $entityManager;
        $this->eventDispatcher = $eventDispatcher;
        $this->countTaskRepository = $countTaskRepository;
        $this->planGeneratorService = $planGeneratorService;
        $this->discrepancyHandlerService = $discrepancyHandlerService;
        $this->analysisService = $analysisService;
        $this->dataValidatorService = $dataValidatorService;
    }

    /**
     * {@inheritdoc}
     */
    public function generateCountPlan(string $countType, array $criteria, array $planOptions = []): CountPlan
    {
        $countPlan = $this->planGeneratorService->generatePlan($countType, $criteria, $planOptions);

        // 生成相关的盘点任务
        $this->generateCountTasks($countPlan, $criteria, $planOptions);

        return $countPlan;
    }

    /**
     * {@inheritdoc}
     */
    public function executeCountTask(CountTask $task, array $countData, array $executionContext = []): array
    {
        $startTime = microtime(true);

        // 验证盘点数据完整性
        $validationResult = $this->dataValidatorService->validateCountData($countData);
        if (!$this->isValidationPassed($validationResult)) {
            $errors = is_array($validationResult['errors'] ?? null) ? $validationResult['errors'] : [];

            return [
                'task_status' => 'pending_review',
                'count_accuracy' => 0,
                'discrepancies' => $errors,
                'next_actions' => ['data_correction_required'],
                'completion_time' => 0,
            ];
        }

        // 执行盘点检查和差异检测
        $systemQuantityRaw = $countData['system_quantity'] ?? 0;
        $actualQuantityRaw = $countData['actual_quantity'] ?? 0;

        assert(is_int($systemQuantityRaw) || is_numeric($systemQuantityRaw), 'system_quantity must be numeric');
        assert(is_int($actualQuantityRaw) || is_numeric($actualQuantityRaw), 'actual_quantity must be numeric');

        $systemQuantity = is_int($systemQuantityRaw) ? $systemQuantityRaw : (int) $systemQuantityRaw;
        $actualQuantity = is_int($actualQuantityRaw) ? $actualQuantityRaw : (int) $actualQuantityRaw;
        $accuracy = $this->calculateCountAccuracy($systemQuantity, $actualQuantity);
        $discrepancies = $this->discrepancyHandlerService->checkForDiscrepancies($task, $countData);

        // 更新任务状态
        $taskStatus = 0 === count($discrepancies) ? TaskStatus::COMPLETED : TaskStatus::DISCREPANCY_FOUND;
        $task->setStatus($taskStatus);

        // 记录盘点结果
        $this->recordCountResult($task, $countData, $executionContext, $accuracy);
        $this->countTaskRepository->save($task);

        $completionTime = microtime(true) - $startTime;
        $nextActions = $this->determineNextActions($discrepancies, $executionContext);

        return [
            'task_status' => $taskStatus->value,
            'count_accuracy' => $accuracy,
            'discrepancies' => $discrepancies,
            'next_actions' => $nextActions,
            'completion_time' => round($completionTime, 3),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function handleDiscrepancy(CountTask $task, array $discrepancyData, array $handlingOptions = []): array
    {
        return $this->discrepancyHandlerService->handleDiscrepancy($task, $discrepancyData, $handlingOptions);
    }

    /**
     * {@inheritdoc}
     */
    public function getCountProgress(CountPlan $plan): array
    {
        return $this->analysisService->getCountProgress($plan);
    }

    /**
     * {@inheritdoc}
     */
    public function generateDiscrepancyReport(CountPlan $plan, array $reportOptions = []): array
    {
        return $this->analysisService->generateDiscrepancyReport($plan, $reportOptions);
    }

    /**
     * {@inheritdoc}
     */
    public function analyzeCountResults(array $planIds, array $analysisParams = []): array
    {
        return $this->analysisService->analyzeCountResults($planIds, $analysisParams);
    }

    /**
     * {@inheritdoc}
     */
    public function optimizeCountFrequency(array $optimizationCriteria): array
    {
        return $this->analysisService->optimizeCountFrequency($optimizationCriteria);
    }

    /**
     * {@inheritdoc}
     */
    public function handleCountException(CountTask $task, string $exceptionType, array $exceptionDetails = []): array
    {
        $exceptionHandlingStrategy = $this->getExceptionHandlingStrategy($exceptionType);

        /** @var array<string, mixed> $recoveryActions */
        $recoveryActions = is_array($exceptionHandlingStrategy['recovery_actions'] ?? null)
            ? $exceptionHandlingStrategy['recovery_actions']
            : [];
        $this->recordExceptionHandling($task, $exceptionType, $exceptionDetails, $recoveryActions);

        $impactAssessment = $this->assessExceptionImpact($task, $exceptionType, $exceptionDetails);

        return [
            'recovery_actions' => $exceptionHandlingStrategy['recovery_actions'],
            'alternative_procedures' => $exceptionHandlingStrategy['alternative_procedures'],
            'impact_assessment' => $impactAssessment,
            'escalation_required' => $exceptionHandlingStrategy['escalation_required'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function validateCountDataQuality(array $countDataBatch, array $validationRules = []): array
    {
        return $this->dataValidatorService->validateCountDataQuality($countDataBatch, $validationRules);
    }

    /**
     * 生成盘点任务
     *
     * @param array<string, mixed> $criteria
     * @param array<string, mixed> $planOptions
     */
    private function generateCountTasks(CountPlan $plan, array $criteria, array $planOptions): void
    {
        // 这里简化处理，实际应根据盘点范围生成具体任务
        $taskCount = $this->estimateTaskCount($plan->getCountType(), $criteria);

        for ($i = 1; $i <= min($taskCount, 10); ++$i) { // 限制最多10个任务用于演示
            $task = new CountTask();
            $task->setTaskType('count');
            $task->setTaskName("盘点任务 #{$i}");
            $task->setPriority($plan->getPriority());
            $task->setStatus(TaskStatus::PENDING);

            $taskData = [
                'count_plan_id' => $plan->getId(),
                'task_sequence' => $i,
                'location_code' => "LOC-{$i}",
                'estimated_items' => rand(10, 100),
            ];
            $task->setTaskData($taskData);

            $this->countTaskRepository->save($task, false);

            // 派发任务创建事件
            $event = new TaskCreatedEvent(
                $task,
                'system',
                'count_plan_generation',
                ['plan_id' => $plan->getId()],
                ['creation_context' => 'count_plan_generation']
            );
            $this->eventDispatcher->dispatch($event);
        }

        $this->entityManager->flush();
    }

    /**
     * 记录盘点结果
     *
     * @param array<string, mixed> $countData
     * @param array<string, mixed> $executionContext
     */
    private function recordCountResult(CountTask $task, array $countData, array $executionContext, float $accuracy): void
    {
        $taskData = $task->getTaskData();
        $taskData['count_result'] = [
            'system_quantity' => $countData['system_quantity'] ?? 0,
            'actual_quantity' => $countData['actual_quantity'] ?? 0,
            'accuracy' => $accuracy,
            'counter_id' => $executionContext['counter_id'] ?? null,
            'count_method' => $executionContext['count_method'] ?? 'manual',
            'count_timestamp' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
        ];
        $task->setTaskData($taskData);
    }

    /**
     * 计算盘点准确度
     */
    private function calculateCountAccuracy(int $systemQuantity, int $actualQuantity): float
    {
        if (0 === $systemQuantity && 0 === $actualQuantity) {
            return 100.0;
        }

        if (0 === $systemQuantity) {
            return 0.0;
        }

        $difference = abs($systemQuantity - $actualQuantity);
        $accuracy = (1 - ($difference / max($systemQuantity, $actualQuantity))) * 100;

        return max(0, round($accuracy, 2));
    }

    /**
     * 确定下一步动作
     *
     * @param array<array<string, mixed>> $discrepancies
     * @param array<string, mixed> $executionContext
     * @return array<string>
     */
    private function determineNextActions(array $discrepancies, array $executionContext): array
    {
        if (0 === count($discrepancies)) {
            return ['mark_completed'];
        }

        /** @var array<string> $actions */
        $actions = [];
        foreach ($discrepancies as $discrepancy) {
            $quantityDifference = $discrepancy['quantity_difference'] ?? 0;
            $quantityFloat = is_float($quantityDifference) || is_int($quantityDifference)
                ? (float) $quantityDifference
                : 0.0;
            $actions[] = $this->getDiscrepancyAction(abs($quantityFloat));
        }

        return array_unique($actions);
    }

    /**
     * 根据差异获取处理动作
     */
    private function getDiscrepancyAction(float $difference): string
    {
        if ($difference > 10) {
            return 'require_supervisor_review';
        }

        if ($difference > 5) {
            return 'schedule_recount';
        }

        return 'auto_adjust_inventory';
    }

    /**
     * 估算任务数量
     *
     * @param array<string, mixed> $criteria
     */
    private function estimateTaskCount(string $countType, array $criteria): int
    {
        $baseCount = match ($countType) {
            'full' => 1000,
            'abc' => 300,
            'cycle' => 200,
            'spot' => 50,
            'random' => 100,
            default => 100,
        };

        $warehouseZones = $criteria['warehouse_zones'] ?? [];
        $zoneCount = is_array($warehouseZones) ? count($warehouseZones) : 0;
        if ($zoneCount > 0) {
            $count = intval($baseCount * ($zoneCount / 5));
        } else {
            $count = $baseCount;
        }

        return max(1, $count);
    }

    /**
     * 获取异常处理策略
     *
     * @return array<string, mixed>
     */
    private function getExceptionHandlingStrategy(string $exceptionType): array
    {
        $strategies = [
            'equipment_failure' => [
                'recovery_actions' => ['switch_to_manual_count', 'request_backup_equipment'],
                'alternative_procedures' => ['manual_barcode_entry', 'visual_inspection'],
                'escalation_required' => false,
            ],
            'data_corruption' => [
                'recovery_actions' => ['restore_from_backup', 'manual_data_entry'],
                'alternative_procedures' => ['paper_based_recording', 'photo_documentation'],
                'escalation_required' => true,
            ],
            'access_denied' => [
                'recovery_actions' => ['request_access_clearance', 'reschedule_task'],
                'alternative_procedures' => ['remote_verification', 'supervisor_accompanied_count'],
                'escalation_required' => false,
            ],
            'time_expired' => [
                'recovery_actions' => ['extend_task_deadline', 'prioritize_critical_items'],
                'alternative_procedures' => ['sampling_count', 'focus_on_high_value_items'],
                'escalation_required' => false,
            ],
        ];

        return $strategies[$exceptionType] ?? [
            'recovery_actions' => ['escalate_to_supervisor'],
            'alternative_procedures' => [],
            'escalation_required' => true,
        ];
    }

    /**
     * 记录异常处理
     *
     * @param array<string, mixed> $exceptionDetails
     * @param array<string, mixed> $recoveryActions
     */
    private function recordExceptionHandling(CountTask $task, string $exceptionType, array $exceptionDetails, array $recoveryActions): void
    {
        $taskData = $task->getTaskData();
        $taskData['exception_handling'] = [
            'exception_type' => $exceptionType,
            'exception_details' => $exceptionDetails,
            'recovery_actions' => $recoveryActions,
            'timestamp' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
        ];
        $task->setTaskData($taskData);
        $this->countTaskRepository->save($task);
    }

    /**
     * 评估异常影响
     *
     * @param array<string, mixed> $exceptionDetails
     * @return array<string, mixed>
     */
    private function assessExceptionImpact(CountTask $task, string $exceptionType, array $exceptionDetails): array
    {
        $impactData = $this->getExceptionImpactData($exceptionType);

        $delay = is_numeric($impactData['delay'] ?? null) ? (float) $impactData['delay'] : 0.0;

        return [
            'severity_level' => $impactData['severity'],
            'estimated_delay_minutes' => $delay,
            'affected_task_count' => 1,
            'financial_impact' => $delay * 0.5,
        ];
    }

    /**
     * 获取异常影响数据
     *
     * @return array<string, mixed>
     */
    private function getExceptionImpactData(string $exceptionType): array
    {
        $impactMapping = [
            'equipment_failure' => ['severity' => 'high', 'delay' => 120],
            'data_corruption' => ['severity' => 'critical', 'delay' => 240],
            'access_denied' => ['severity' => 'medium', 'delay' => 60],
            'time_expired' => ['severity' => 'medium', 'delay' => 30],
        ];

        return $impactMapping[$exceptionType] ?? ['severity' => 'medium', 'delay' => 30];
    }

    /**
     * 检查验证是否通过
     *
     * @param array<string, mixed> $validationResult
     */
    private function isValidationPassed(array $validationResult): bool
    {
        return is_bool($validationResult['valid'] ?? false) && true === $validationResult['valid'];
    }
}
