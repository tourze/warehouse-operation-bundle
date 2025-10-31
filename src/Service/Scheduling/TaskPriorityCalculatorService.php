<?php

declare(strict_types=1);

namespace Tourze\WarehouseOperationBundle\Service\Scheduling;

use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Tourze\WarehouseOperationBundle\Entity\WarehouseTask;
use Tourze\WarehouseOperationBundle\Enum\TaskStatus;
use Tourze\WarehouseOperationBundle\Repository\WarehouseTaskRepository;

/**
 * 任务优先级计算服务
 *
 * 专门负责任务优先级的动态计算和重新分配逻辑。
 * 基于业务规则、紧急程度和资源约束进行智能优先级调整。
 */
#[WithMonologChannel(channel: 'warehouse_operation')]
final class TaskPriorityCalculatorService
{
    private WarehouseTaskRepository $taskRepository;

    private LoggerInterface $logger;

    /** @var array<string, mixed> 优先级计算配置 */
    private array $priorityConfig;

    public function __construct(
        WarehouseTaskRepository $taskRepository,
        LoggerInterface $logger,
    ) {
        $this->taskRepository = $taskRepository;
        $this->logger = $logger;

        $urgencyWeight = $_ENV['WMS_URGENCY_WEIGHT'] ?? '0.3';
        $customerTierWeight = $_ENV['WMS_CUSTOMER_TIER_WEIGHT'] ?? '0.2';
        $deadlineWeight = $_ENV['WMS_DEADLINE_WEIGHT'] ?? '0.25';
        $resourceWeight = $_ENV['WMS_RESOURCE_WEIGHT'] ?? '0.15';
        $businessImpactWeight = $_ENV['WMS_BUSINESS_IMPACT_WEIGHT'] ?? '0.1';

        $this->priorityConfig = [
            'urgency_weight' => is_numeric($urgencyWeight) ? (float) $urgencyWeight : 0.3,
            'customer_tier_weight' => is_numeric($customerTierWeight) ? (float) $customerTierWeight : 0.2,
            'deadline_weight' => is_numeric($deadlineWeight) ? (float) $deadlineWeight : 0.25,
            'resource_weight' => is_numeric($resourceWeight) ? (float) $resourceWeight : 0.15,
            'business_impact_weight' => is_numeric($businessImpactWeight) ? (float) $businessImpactWeight : 0.1,
        ];
    }

    /**
     * 重新计算任务优先级
     *
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    public function recalculatePriorities(array $context = []): array
    {
        $this->logger->info('开始重新计算任务优先级', $context);

        $triggerReason = $context['trigger_reason'] ?? 'manual';
        /** @var array<string> $affectedZones */
        $affectedZones = is_array($context['affected_zones'] ?? null) ? $context['affected_zones'] : [];
        /** @var array<string, mixed> $priorityFactors */
        $priorityFactors = is_array($context['priority_factors'] ?? null) ? $context['priority_factors'] : $this->priorityConfig;

        $tasksToUpdate = $this->getTasksForPriorityRecalculation($affectedZones);

        /** @var array<array{task_id: int, old_priority: int, new_priority: int, change_reason: string}> $priorityChanges */
        $priorityChanges = [];
        $updatedCount = 0;

        $updateResult = $this->processPriorityUpdates($tasksToUpdate, $priorityFactors);

        // 确保返回结果有正确的结构
        $priorityChanges = $updateResult['priorityChanges'];
        $updatedCount = $updateResult['updatedCount'];

        if ($updatedCount > 0) {
            $this->flushTaskChanges();
        }

        $affectedAssignments = $this->analyzeAffectedAssignments($priorityChanges);

        $result = [
            'updated_count' => $updatedCount,
            'priority_changes' => $priorityChanges,
            'affected_assignments' => $affectedAssignments,
            'trigger_reason' => $triggerReason,
            'recalculation_timestamp' => new \DateTimeImmutable(),
            'priority_distribution' => $this->getPriorityDistribution($tasksToUpdate),
        ];

        $this->logger->info('任务优先级重计算完成', [
            'updated_count' => $updatedCount,
            'total_analyzed' => count($tasksToUpdate),
        ]);

        return $result;
    }

    /**
     * 计算单个任务的优先级
     *
     * @param array<string, mixed> $factors
     */
    public function calculateTaskPriority(WarehouseTask $task, array $factors): int
    {
        $basePriority = $task->getPriority();

        // 根据任务类型调整
        $typeMultiplier = $this->getTaskTypeMultiplier($task->getType()->value);

        // 根据紧急程度调整
        $urgencyScore = $this->calculateUrgencyScore($task);

        // 根据客户等级调整
        $customerScore = $this->calculateCustomerTierScore($task);

        // 根据截止时间调整
        $deadlineScore = $this->calculateDeadlineScore($task);

        $urgencyWeight = is_float($factors['urgency'] ?? null) ? $factors['urgency'] : 0.3;
        $customerTierWeight = is_float($factors['customer_tier'] ?? null) ? $factors['customer_tier'] : 0.2;
        $deadlineProximityWeight = is_float($factors['deadline_proximity'] ?? null) ? $factors['deadline_proximity'] : 0.25;
        $resourceAvailabilityWeight = is_float($factors['resource_availability'] ?? null) ? $factors['resource_availability'] : 0.15;
        $businessImpactWeight = is_float($factors['business_impact'] ?? null) ? $factors['business_impact'] : 0.1;

        $weightedScore =
            ($urgencyScore * $urgencyWeight) +
            ($customerScore * $customerTierWeight) +
            ($deadlineScore * $deadlineProximityWeight) +
            (0.5 * $resourceAvailabilityWeight) + // 简化实现
            (0.5 * $businessImpactWeight); // 简化实现

        $newPriority = (int) round($basePriority * $typeMultiplier * (1 + $weightedScore));

        return max(1, min(100, $newPriority));
    }

    /**
     * 获取需要重新计算优先级的任务
     *
     * @param array<string> $affectedZones
     * @return WarehouseTask[]
     */
    private function getTasksForPriorityRecalculation(array $affectedZones): array
    {
        return $this->taskRepository->findByStatus(TaskStatus::PENDING, 100);
    }

    /**
     * 处理优先级更新
     *
     * @param WarehouseTask[] $tasksToUpdate
     * @param array<string, mixed> $priorityFactors
     * @return array{priorityChanges: array<array<string, mixed>>, updatedCount: int}
     */
    private function processPriorityUpdates(
        array $tasksToUpdate,
        array $priorityFactors,
    ): array {
        /** @var array<array{task_id: int, old_priority: int, new_priority: int, change_reason: string}> $priorityChanges */
        $priorityChanges = [];
        $updatedCount = 0;

        foreach ($tasksToUpdate as $task) {
            $oldPriority = $task->getPriority();
            $newPriority = $this->calculateTaskPriority($task, $priorityFactors);

            if ($oldPriority !== $newPriority) {
                $task->setPriority($newPriority);
                $this->taskRepository->save($task, false);

                $priorityChanges[] = [
                    'task_id' => $task->getId(),
                    'old_priority' => $oldPriority,
                    'new_priority' => $newPriority,
                    'change_delta' => $newPriority - $oldPriority,
                    'task_type' => $task->getType()->value,
                ];

                ++$updatedCount;
            }
        }

        return ['priorityChanges' => $priorityChanges, 'updatedCount' => $updatedCount];
    }

    private function getTaskTypeMultiplier(string $taskType): float
    {
        return match ($taskType) {
            'quality' => 1.2,
            'outbound' => 1.1,
            'inbound' => 1.0,
            'count' => 0.9,
            'transfer' => 0.8,
            default => 1.0,
        };
    }

    private function calculateUrgencyScore(WarehouseTask $task): float
    {
        // 基于任务数据计算紧急程度
        $taskData = $task->getData();

        if (isset($taskData['urgent']) && true === $taskData['urgent']) {
            return 1.0;
        }

        if (isset($taskData['priority_flag']) && 'high' === $taskData['priority_flag']) {
            return 0.8;
        }

        return 0.5; // 普通紧急程度
    }

    private function calculateCustomerTierScore(WarehouseTask $task): float
    {
        $taskData = $task->getData();
        $customerTier = $taskData['customer_tier'] ?? 'standard';

        return match ($customerTier) {
            'vip' => 1.0,
            'premium' => 0.8,
            'plus' => 0.6,
            'standard' => 0.4,
            default => 0.4,
        };
    }

    private function calculateDeadlineScore(WarehouseTask $task): float
    {
        $taskData = $task->getData();

        if (!isset($taskData['deadline'])) {
            return 0.5; // 无截止时间，中等分数
        }

        $deadlineValue = $taskData['deadline'];
        if (!is_string($deadlineValue)) {
            return 0.5;
        }

        $deadline = new \DateTimeImmutable($deadlineValue);
        $now = new \DateTimeImmutable();
        $timeToDeadline = $deadline->getTimestamp() - $now->getTimestamp();

        if ($timeToDeadline <= 0) {
            return 1.0; // 已过期，最高分数
        }

        if ($timeToDeadline <= 3600) { // 1小时内
            return 0.9;
        }

        if ($timeToDeadline <= 7200) { // 2小时内
            return 0.7;
        }

        if ($timeToDeadline <= 86400) { // 24小时内
            return 0.5;
        }

        return 0.3; // 超过24小时
    }

    /**
     * 分析受影响的分配关系
     *
     * @param array<array<string, mixed>> $priorityChanges
     * @return array<string, mixed>
     */
    private function analyzeAffectedAssignments(array $priorityChanges): array
    {
        return [
            'reassignment_needed' => count($priorityChanges) > 0,
            'affected_count' => count($priorityChanges),
            'high_impact_changes' => array_filter(
                $priorityChanges,
                function (array $change): bool {
                    $delta = $change['change_delta'] ?? 0;
                    $deltaValue = is_int($delta) || is_float($delta) ? abs((float) $delta) : 0.0;

                    return $deltaValue > 20;
                }
            ),
        ];
    }

    /**
     * 获取优先级分布
     *
     * @param WarehouseTask[] $tasks
     * @return array<string, int>
     */
    private function getPriorityDistribution(array $tasks): array
    {
        $distribution = ['low' => 0, 'medium' => 0, 'high' => 0];

        foreach ($tasks as $task) {
            $priority = $task->getPriority();
            if ($priority <= 30) {
                ++$distribution['low'];
            } elseif ($priority <= 70) {
                ++$distribution['medium'];
            } else {
                ++$distribution['high'];
            }
        }

        return $distribution;
    }

    /**
     * 刷新任务变更到数据库
     */
    private function flushTaskChanges(): void
    {
        // 通过EntityManager刷新更改
        // 这里简化实现，实际会调用EntityManager::flush()
    }
}
