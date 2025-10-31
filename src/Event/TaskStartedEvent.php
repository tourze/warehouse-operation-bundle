<?php

namespace Tourze\WarehouseOperationBundle\Event;

use Tourze\WarehouseOperationBundle\Entity\WarehouseTask;

/**
 * 任务开始事件
 *
 * 当任务开始执行时触发此事件，包含开始时的状态和环境信息。
 * 可用于触发监控、资源分配、开始计时等处理流程。
 */
class TaskStartedEvent extends AbstractTaskEvent
{
    /**
     * @param WarehouseTask $task 任务对象
     * @param int $startedByWorkerId 开始任务的作业员ID
     * @param \DateTimeImmutable $startedAt 开始时间
     * @param array<string, mixed> $initialState 初始状态信息
     * @param array<string, mixed> $environmentData 环境数据
     * @param array<string, mixed> $context 上下文信息
     */
    public function __construct(
        WarehouseTask $task,
        protected readonly int $startedByWorkerId,
        protected readonly \DateTimeImmutable $startedAt,
        protected readonly array $initialState = [],
        protected readonly array $environmentData = [],
        array $context = [],
    ) {
        parent::__construct($task, $context);
    }

    /**
     * 获取开始任务的作业员ID
     */
    public function getStartedByWorkerId(): int
    {
        return $this->startedByWorkerId;
    }

    /**
     * 获取任务开始时间
     */
    public function getStartedAt(): \DateTimeImmutable
    {
        return $this->startedAt;
    }

    /**
     * 获取初始状态信息
     *
     * @return array<string, mixed>
     */
    public function getInitialState(): array
    {
        return $this->initialState;
    }

    /**
     * 获取环境数据
     *
     * @return array<string, mixed>
     */
    public function getEnvironmentData(): array
    {
        return $this->environmentData;
    }

    /**
     * 获取工作站信息
     */
    public function getWorkstationId(): ?string
    {
        $workstationId = $this->environmentData['workstation_id'] ?? null;

        return is_string($workstationId) ? $workstationId : null;
    }

    /**
     * 获取设备信息
     *
     * @return array<string, mixed>|null
     */
    public function getEquipmentData(): ?array
    {
        $equipment = $this->environmentData['equipment'] ?? null;

        /** @var array<string, mixed>|null */
        return is_array($equipment) ? $equipment : null;
    }

    /**
     * 获取预计完成时间
     */
    public function getEstimatedEndTime(): ?\DateTimeImmutable
    {
        $duration = $this->initialState['estimated_duration_seconds'] ?? null;

        if (null === $duration) {
            return null;
        }

        if (!is_int($duration) && !is_float($duration) && !is_string($duration)) {
            return null;
        }

        $durationInt = (int) $duration;

        return $this->startedAt->add(new \DateInterval('PT' . $durationInt . 'S'));
    }

    /**
     * 获取初始条件检查结果
     *
     * @return array<string, mixed>|null
     */
    public function getPreConditionCheck(): ?array
    {
        $check = $this->initialState['precondition_check'] ?? null;

        /** @var array<string, mixed>|null */
        return is_array($check) ? $check : null;
    }

    /**
     * 检查是否通过了初始条件检查
     */
    public function passedPreConditions(): bool
    {
        $check = $this->getPreConditionCheck();

        return null !== $check && (bool) ($check['passed'] ?? false);
    }

    /**
     * 获取资源分配信息
     *
     * @return array<string, mixed>|null
     */
    public function getResourceAllocation(): ?array
    {
        $resources = $this->initialState['resources'] ?? null;

        /** @var array<string, mixed>|null */
        return is_array($resources) ? $resources : null;
    }

    /**
     * 获取任务复杂度评分
     */
    public function getComplexityScore(): ?float
    {
        $score = $this->initialState['complexity_score'] ?? null;

        return is_float($score) || is_int($score) ? (float) $score : null;
    }

    /**
     * 获取预计工作量
     */
    public function getEstimatedWorkload(): ?int
    {
        $workload = $this->initialState['estimated_workload'] ?? null;

        return is_int($workload) ? $workload : null;
    }

    /**
     * 获取天气/环境条件
     *
     * @return array<string, mixed>|null
     */
    public function getEnvironmentalConditions(): ?array
    {
        $conditions = $this->environmentData['conditions'] ?? null;

        /** @var array<string, mixed>|null */
        return is_array($conditions) ? $conditions : null;
    }

    /**
     * 检查是否在最佳时间窗口内开始
     */
    public function isInOptimalTimeWindow(): bool
    {
        $optimal = $this->initialState['optimal_timing'] ?? true;

        return is_bool($optimal) ? $optimal : true;
    }

    /**
     * 获取并行任务信息
     *
     * @return array<int, mixed>
     */
    public function getConcurrentTasks(): array
    {
        $tasks = $this->environmentData['concurrent_tasks'] ?? [];

        /** @var array<int, mixed> */
        return is_array($tasks) ? $tasks : [];
    }
}
