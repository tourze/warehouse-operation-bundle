<?php

namespace Tourze\WarehouseOperationBundle\Event;

use Tourze\WarehouseOperationBundle\Entity\WarehouseTask;

/**
 * 任务分配事件
 *
 * 当任务被分配给作业员时触发此事件，包含分配信息和作业员详情。
 * 可用于触发后续处理流程，如通知、调度优化等。
 */
class TaskAssignedEvent extends AbstractTaskEvent
{
    /**
     * @param WarehouseTask $task 任务对象
     * @param int $assignedWorkerId 被分配的作业员ID
     * @param string $assignedBy 分配操作者
     * @param string $assignmentMethod 分配方式
     * @param array<string, mixed> $assignmentData 分配数据
     * @param array<string, mixed> $context 上下文信息
     */
    public function __construct(
        WarehouseTask $task,
        protected readonly int $assignedWorkerId,
        protected readonly string $assignedBy,
        protected readonly string $assignmentMethod = 'manual',
        protected readonly array $assignmentData = [],
        array $context = [],
    ) {
        parent::__construct($task, $context);
    }

    /**
     * 获取被分配的作业员ID
     */
    public function getAssignedWorkerId(): int
    {
        return $this->assignedWorkerId;
    }

    /**
     * 获取分配操作者
     */
    public function getAssignedBy(): string
    {
        return $this->assignedBy;
    }

    /**
     * 获取分配方式
     */
    public function getAssignmentMethod(): string
    {
        return $this->assignmentMethod;
    }

    /**
     * 获取分配数据
     *
     * @return array<string, mixed>
     */
    public function getAssignmentData(): array
    {
        return $this->assignmentData;
    }

    /**
     * 检查是否为自动分配
     */
    public function isAutoAssignment(): bool
    {
        return 'auto' === $this->assignmentMethod;
    }

    /**
     * 检查是否为手动分配
     */
    public function isManualAssignment(): bool
    {
        return 'manual' === $this->assignmentMethod;
    }

    /**
     * 检查是否为技能匹配分配
     */
    public function isSkillBasedAssignment(): bool
    {
        return 'skill_match' === $this->assignmentMethod;
    }

    /**
     * 获取分配原因
     */
    public function getAssignmentReason(): ?string
    {
        $reason = $this->assignmentData['reason'] ?? null;

        return is_string($reason) ? $reason : null;
    }

    /**
     * 获取技能匹配评分
     */
    public function getSkillMatchScore(): ?float
    {
        $score = $this->assignmentData['skill_match_score'] ?? null;

        return is_float($score) || is_int($score) ? (float) $score : null;
    }

    /**
     * 获取预计完成时间
     */
    public function getEstimatedCompletionTime(): ?\DateTimeImmutable
    {
        $time = $this->assignmentData['estimated_completion'] ?? null;

        return $time instanceof \DateTimeImmutable ? $time : null;
    }

    /**
     * 获取优先级调整信息
     *
     * @return array<string, mixed>|null
     */
    public function getPriorityAdjustment(): ?array
    {
        $adjustment = $this->assignmentData['priority_adjustment'] ?? null;

        /** @var array<string, mixed>|null */
        return is_array($adjustment) ? $adjustment : null;
    }
}
