<?php

declare(strict_types=1);

namespace Tourze\WarehouseOperationBundle\Service\Quality;

use Tourze\WarehouseOperationBundle\Entity\QualityTask;
use Tourze\WarehouseOperationBundle\Repository\WarehouseTaskRepository;

/**
 * 质检失败处理服务
 *
 * 专门负责质检失败商品的处理逻辑，包括隔离、成本评估和后续任务安排。
 * 使用策略模式处理不同类型和严重程度的质检失败。
 */
final class QualityFailureHandlerService
{
    private WarehouseTaskRepository $qualityTaskRepository;

    public function __construct(WarehouseTaskRepository $qualityTaskRepository)
    {
        $this->qualityTaskRepository = $qualityTaskRepository;
    }

    /**
     * 处理质检失败商品
     *
     * @param array<string, mixed> $failureDetails
     * @param array<string, mixed> $handlingOptions
     * @return array<string, mixed>
     */
    public function handleQualityFailure(QualityTask $task, string $failureReason, array $failureDetails = [], array $handlingOptions = []): array
    {
        $handlingContext = $this->buildHandlingContext($failureDetails, $handlingOptions);
        $handlingPlan = $this->createFailureHandlingPlan($handlingContext);

        $costEstimation = $this->calculateCostEstimation($handlingContext, $failureDetails);
        $timeline = $this->createHandlingTimeline($handlingPlan, $handlingContext);

        $this->recordFailureHandling($task, $handlingPlan);

        return $this->buildHandlingResult($handlingPlan, $costEstimation, $timeline);
    }

    /**
     * 计算成本估算
     *
     * @param array<string, mixed> $handlingContext
     * @param array<string, mixed> $failureDetails
     * @return array<string, mixed>
     */
    private function calculateCostEstimation(array $handlingContext, array $failureDetails): array
    {
        $failureType = is_string($handlingContext['failure_type'] ?? null) ? $handlingContext['failure_type'] : 'unknown';
        $severity = is_string($handlingContext['severity'] ?? null) ? $handlingContext['severity'] : 'medium';
        $quantity = is_int($handlingContext['quantity'] ?? null) ? $handlingContext['quantity'] : 1;

        return $this->estimateHandlingCost($failureType, $severity, $quantity, $failureDetails);
    }

    /**
     * 创建处理时间线
     *
     * @param array<string, mixed> $handlingPlan
     * @param array<string, mixed> $handlingContext
     * @return array<array<string, mixed>>
     */
    private function createHandlingTimeline(array $handlingPlan, array $handlingContext): array
    {
        $actions = is_array($handlingPlan['actions'] ?? null) ? $handlingPlan['actions'] : [];
        $followUpTasks = is_array($handlingPlan['follow_up_tasks'] ?? null) ? $handlingPlan['follow_up_tasks'] : [];

        /** @var array<array<string, mixed>> $typedActions */
        $typedActions = [];
        foreach ($actions as $action) {
            if (is_array($action)) {
                /** @var array<string, mixed> $action */
                $typedActions[] = $action;
            }
        }

        /** @var array<array<string, mixed>> $typedTasks */
        $typedTasks = [];
        foreach ($followUpTasks as $followUpTask) {
            if (is_array($followUpTask)) {
                /** @var array<string, mixed> $followUpTask */
                $typedTasks[] = $followUpTask;
            }
        }

        $severity = is_string($handlingContext['severity'] ?? null) ? $handlingContext['severity'] : 'medium';

        return $this->generateHandlingTimeline($typedActions, $typedTasks, $severity);
    }

    /**
     * 构建处理结果
     *
     * @param array<string, mixed> $handlingPlan
     * @param array<string, mixed> $costEstimation
     * @param array<array<string, mixed>> $timeline
     * @return array<string, mixed>
     */
    private function buildHandlingResult(array $handlingPlan, array $costEstimation, array $timeline): array
    {
        return [
            'handling_actions' => $handlingPlan['actions'],
            'isolation_location' => $handlingPlan['isolation_location'],
            'follow_up_tasks' => $handlingPlan['follow_up_tasks'],
            'cost_estimation' => $costEstimation,
            'timeline' => $timeline,
        ];
    }

    /**
     * 构建处理上下文
     *
     * @param array<string, mixed> $failureDetails
     * @param array<string, mixed> $handlingOptions
     * @return array<string, mixed>
     */
    private function buildHandlingContext(array $failureDetails, array $handlingOptions): array
    {
        return [
            'auto_isolate' => (bool) ($handlingOptions['auto_isolate'] ?? true),
            'notify_supplier' => (bool) ($handlingOptions['notify_supplier'] ?? false),
            'create_claim' => (bool) ($handlingOptions['create_claim'] ?? false),
            'failure_type' => $failureDetails['failure_type'] ?? 'unknown',
            'severity' => $failureDetails['severity_level'] ?? 'medium',
            'quantity' => $failureDetails['affected_quantity'] ?? 1,
            'cost_impact' => $failureDetails['cost_impact'] ?? 0,
        ];
    }

    /**
     * 创建失败处理计划
     *
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    /**
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    private function createFailureHandlingPlan(array $context): array
    {
        $plan = [
            'actions' => [],
            'follow_up_tasks' => [],
            'isolation_location' => null,
        ];

        $plan = $this->addIsolationActions($context, $plan);

        return $this->addSeverityBasedActions($context, $plan);
    }

    /**
     * 添加隔离动作
     *
     * @param array<string, mixed> $context
     * @param array<string, mixed> $plan
     */
    /**
     * @param array<string, mixed> $context
     * @param array<string, mixed> $plan
     * @return array<string, mixed>
     */
    private function addIsolationActions(array $context, array $plan): array
    {
        $failureType = is_string($context['failure_type'] ?? null) ? $context['failure_type'] : 'unknown';

        if (!(bool) $context['auto_isolate'] || !$this->shouldIsolate($failureType)) {
            return $plan;
        }

        $severity = is_string($context['severity'] ?? null) ? $context['severity'] : 'medium';
        $plan['isolation_location'] = $this->determineIsolationLocation($failureType, $severity);

        $existingActions = is_array($plan['actions'] ?? null) ? $plan['actions'] : [];
        $existingActions[] = [
            'action' => 'isolate',
            'location' => $plan['isolation_location'],
            'quantity' => $context['quantity'],
        ];
        $plan['actions'] = $existingActions;

        return $plan;
    }

    /**
     * 添加严重程度相关动作
     *
     * @param array<string, mixed> $context
     * @param array<string, mixed> $plan
     */
    /**
     * @param array<string, mixed> $context
     * @param array<string, mixed> $plan
     * @return array<string, mixed>
     */
    private function addSeverityBasedActions(array $context, array $plan): array
    {
        $severity = is_string($context['severity'] ?? null) ? $context['severity'] : 'medium';
        $severityActions = $this->getSeverityActions($severity, $context);

        $existingActions = is_array($plan['actions'] ?? null) ? $plan['actions'] : [];
        $newActions = is_array($severityActions['actions'] ?? null) ? $severityActions['actions'] : [];
        $plan['actions'] = array_merge($existingActions, $newActions);

        $existingTasks = is_array($plan['follow_up_tasks'] ?? null) ? $plan['follow_up_tasks'] : [];
        $newTasks = is_array($severityActions['follow_up_tasks'] ?? null) ? $severityActions['follow_up_tasks'] : [];
        $plan['follow_up_tasks'] = array_merge($existingTasks, $newTasks);

        return $plan;
    }

    /**
     * 获取严重程度动作
     *
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    private function getSeverityActions(string $severity, array $context): array
    {
        $actions = [
            'critical' => [
                'actions' => [['action' => 'immediate_stop', 'reason' => 'Critical quality issue']],
                'follow_up_tasks' => [['type' => 'emergency_review', 'priority' => 100]],
            ],
            'high' => [
                'actions' => [],
                'follow_up_tasks' => [['type' => 'quality_review', 'priority' => 80]],
            ],
            'medium' => [
                'actions' => [],
                'follow_up_tasks' => [['type' => 'rework_evaluation', 'priority' => 50]],
            ],
            'low' => [
                'actions' => [['action' => 'document', 'type' => 'minor_defect']],
                'follow_up_tasks' => [],
            ],
        ];

        return $this->addConditionalTasks($context, $actions[$severity] ?? $actions['medium']);
    }

    /**
     * 添加有条件的任务
     *
     * @param array<string, mixed> $context
     * @param array<string, mixed> $severityAction
     */
    /**
     * @param array<string, mixed> $context
     * @param array<string, mixed> $severityAction
     * @return array<string, mixed>
     */
    private function addConditionalTasks(array $context, array $severityAction): array
    {
        $tasks = is_array($severityAction['follow_up_tasks'] ?? null) ? $severityAction['follow_up_tasks'] : [];

        if ('critical' === $context['severity'] && $this->shouldCreateClaim($context)) {
            $tasks[] = ['type' => 'create_claim', 'supplier_notify' => true];
        }

        if ('high' === $context['severity'] && true === ($context['notify_supplier'] ?? false)) {
            $tasks[] = ['type' => 'supplier_notification', 'details' => $context];
        }

        $severityAction['follow_up_tasks'] = $tasks;

        return $severityAction;
    }

    /**
     * 估算处理成本
     *
     * @param array<string, mixed> $details
     * @return array<string, mixed>
     */
    private function estimateHandlingCost(string $failureType, string $severity, int $quantity, array $details): array
    {
        $baseCost = 10;
        $multiplier = match ($severity) {
            'critical' => 5,
            'high' => 3,
            'medium' => 2,
            'low' => 1,
            default => 1,
        };

        $typeCost = match ($failureType) {
            'damage' => 50,
            'expiry' => 30,
            'contamination' => 100,
            default => 20,
        };

        $totalCost = ($baseCost + $typeCost) * $multiplier * $quantity;

        return [
            'cost' => $totalCost,
            'currency' => 'CNY',
            'breakdown' => [
                'base_cost' => $baseCost,
                'type_cost' => $typeCost,
                'severity_multiplier' => $multiplier,
                'quantity' => $quantity,
            ],
        ];
    }

    /**
     * 生成处理时间线
     *
     * @param array<array<string, mixed>> $actions
     * @param array<array<string, mixed>> $tasks
     * @return array<array<string, mixed>>
     */
    private function generateHandlingTimeline(array $actions, array $tasks, string $severity): array
    {
        $timeline = [];
        $currentTime = new \DateTimeImmutable();

        foreach ($actions as $action) {
            $timeline[] = [
                'action' => $action['action'],
                'scheduled_time' => $currentTime,
                'estimated_duration' => '30 minutes',
            ];
            $currentTime = $currentTime->modify('+30 minutes');
        }

        foreach ($tasks as $task) {
            $duration = match ($task['type']) {
                'emergency_review' => '+2 hours',
                'quality_review' => '+4 hours',
                'supplier_notification' => '+1 hour',
                'create_claim' => '+24 hours',
                default => '+2 hours',
            };

            $timeline[] = [
                'task' => $task['type'],
                'scheduled_time' => $currentTime,
                'estimated_completion' => $currentTime->modify($duration),
            ];
            $currentTime = $currentTime->modify($duration);
        }

        return $timeline;
    }

    /**
     * 记录失败处理
     *
     * @param array<string, mixed> $handlingPlan
     */
    private function recordFailureHandling(QualityTask $task, array $handlingPlan): void
    {
        $handlingData = [
            'failure_handled_at' => new \DateTimeImmutable(),
            'handling_actions' => $handlingPlan['actions'],
            'isolation_location' => $handlingPlan['isolation_location'],
            'follow_up_tasks' => $handlingPlan['follow_up_tasks'],
        ];

        $task->setData(array_merge($task->getData(), ['failure_handling' => $handlingData]));
        $this->qualityTaskRepository->save($task);
    }

    private function shouldIsolate(string $failureType): bool
    {
        // 隔离所有已知的失败类型以及未知类型（未知类型需要通用隔离）
        return in_array($failureType, ['appearance', 'damage', 'specification', 'expiry', 'contamination'], true)
            || 'unknown' === $failureType
            || 'unknown_type' === $failureType;
    }

    /**
     * @param array<string, mixed> $context
     */
    private function shouldCreateClaim(array $context): bool
    {
        $createClaim = (bool) ($context['create_claim'] ?? false);
        $costImpactRaw = $context['cost_impact'] ?? 0;
        $costImpact = is_numeric($costImpactRaw) ? (int) $costImpactRaw : 0;

        return $createClaim || $costImpact > 1000;
    }

    private function determineIsolationLocation(string $failureType, string $severity): string
    {
        return match ($failureType) {
            'expiry' => 'QUARANTINE_EXPIRED',
            'damage' => 'QUARANTINE_DAMAGED',
            'contamination' => 'QUARANTINE_CONTAMINATED',
            default => 'QUARANTINE_GENERAL',
        } . '_' . strtoupper($severity);
    }
}
