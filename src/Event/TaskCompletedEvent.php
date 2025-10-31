<?php

namespace Tourze\WarehouseOperationBundle\Event;

use Tourze\WarehouseOperationBundle\Entity\WarehouseTask;

/**
 * 任务完成事件
 *
 * 当任务完成时触发此事件，包含完成结果和性能指标信息。
 * 可用于触发后续处理流程，如统计分析、质量评估等。
 */
class TaskCompletedEvent extends AbstractTaskEvent
{
    /**
     * @param WarehouseTask $task 任务对象
     * @param int $completedByWorkerId 完成任务的作业员ID
     * @param \DateTimeImmutable $completedAt 完成时间
     * @param array<string, mixed> $completionResult 完成结果
     * @param array<string, mixed> $performanceMetrics 性能指标
     * @param array<string, mixed> $context 上下文信息
     */
    public function __construct(
        WarehouseTask $task,
        protected readonly int $completedByWorkerId,
        protected readonly \DateTimeImmutable $completedAt,
        protected readonly array $completionResult = [],
        protected readonly array $performanceMetrics = [],
        array $context = [],
    ) {
        parent::__construct($task, $context);
    }

    /**
     * 获取完成任务的作业员ID
     */
    public function getCompletedByWorkerId(): int
    {
        return $this->completedByWorkerId;
    }

    /**
     * 获取完成时间
     */
    public function getCompletedAt(): \DateTimeImmutable
    {
        return $this->completedAt;
    }

    /**
     * 获取完成结果
     *
     * @return array<string, mixed>
     */
    public function getCompletionResult(): array
    {
        return $this->completionResult;
    }

    /**
     * 获取性能指标
     *
     * @return array<string, mixed>
     */
    public function getPerformanceMetrics(): array
    {
        return $this->performanceMetrics;
    }

    /**
     * 获取任务持续时间（秒）
     */
    public function getTaskDuration(): ?int
    {
        $duration = $this->performanceMetrics['duration_seconds'] ?? null;

        return is_int($duration) ? $duration : null;
    }

    /**
     * 获取任务效率评分
     */
    public function getEfficiencyScore(): ?float
    {
        $score = $this->performanceMetrics['efficiency_score'] ?? null;

        return is_float($score) || is_int($score) ? (float) $score : null;
    }

    /**
     * 获取质量评分
     */
    public function getQualityScore(): ?float
    {
        $score = $this->performanceMetrics['quality_score'] ?? null;

        return is_float($score) || is_int($score) ? (float) $score : null;
    }

    /**
     * 检查任务是否按时完成
     */
    public function isCompletedOnTime(): bool
    {
        $onTime = $this->performanceMetrics['on_time'] ?? true;

        return is_bool($onTime) ? $onTime : true;
    }

    /**
     * 检查任务是否有质量问题
     */
    public function hasQualityIssues(): bool
    {
        $hasIssues = $this->completionResult['quality_issues'] ?? false;

        return is_bool($hasIssues) ? $hasIssues : false;
    }

    /**
     * 获取异常信息
     *
     * @return array<int, mixed>
     */
    public function getExceptions(): array
    {
        $exceptions = $this->completionResult['exceptions'] ?? [];

        /** @var array<int, mixed> */
        return is_array($exceptions) ? $exceptions : [];
    }

    /**
     * 获取实际处理数量
     */
    public function getActualQuantity(): ?int
    {
        $quantity = $this->completionResult['actual_quantity'] ?? null;

        return is_int($quantity) ? $quantity : null;
    }

    /**
     * 获取目标数量
     */
    public function getTargetQuantity(): ?int
    {
        $quantity = $this->completionResult['target_quantity'] ?? null;

        return is_int($quantity) ? $quantity : null;
    }

    /**
     * 计算完成率
     */
    public function getCompletionRate(): ?float
    {
        $actual = $this->getActualQuantity();
        $target = $this->getTargetQuantity();

        if (null === $actual || null === $target || 0 === $target) {
            return null;
        }

        return $actual / $target;
    }

    /**
     * 获取位置移动信息
     *
     * @return array<string, mixed>|null
     */
    public function getMovementData(): ?array
    {
        $movement = $this->performanceMetrics['movement'] ?? null;

        /** @var array<string, mixed>|null */
        return is_array($movement) ? $movement : null;
    }

    /**
     * 检查是否超出预期时间
     */
    public function isOverdue(): bool
    {
        $overdue = $this->performanceMetrics['overdue'] ?? false;

        return is_bool($overdue) ? $overdue : false;
    }
}
