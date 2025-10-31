<?php

namespace Tourze\WarehouseOperationBundle\Event;

use Tourze\WarehouseOperationBundle\Entity\WarehouseTask;

/**
 * 任务创建事件
 *
 * 当新任务被创建时触发此事件，包含创建上下文和任务初始化信息。
 * 可用于触发后续处理流程，如任务调度、通知等。
 */
class TaskCreatedEvent extends AbstractTaskEvent
{
    /**
     * @param WarehouseTask $task 任务对象
     * @param string $createdBy 创建者
     * @param string $source 创建源
     * @param array<string, mixed> $creationData 创建数据
     * @param array<string, mixed> $context 上下文信息
     */
    public function __construct(
        WarehouseTask $task,
        protected readonly string $createdBy,
        protected readonly string $source = 'system',
        protected readonly array $creationData = [],
        array $context = [],
    ) {
        parent::__construct($task, $context);
    }

    /**
     * 获取任务创建者
     */
    public function getCreatedBy(): string
    {
        return $this->createdBy;
    }

    /**
     * 获取创建源
     */
    public function getSource(): string
    {
        return $this->source;
    }

    /**
     * 获取创建数据
     *
     * @return array<string, mixed>
     */
    public function getCreationData(): array
    {
        return $this->creationData;
    }

    /**
     * 检查是否为系统自动创建
     */
    public function isSystemCreated(): bool
    {
        return 'system' === $this->source;
    }

    /**
     * 检查是否为用户手动创建
     */
    public function isManualCreated(): bool
    {
        return 'manual' === $this->source;
    }

    /**
     * 检查是否为API创建
     */
    public function isApiCreated(): bool
    {
        return 'api' === $this->source;
    }

    /**
     * 获取创建原因
     */
    public function getCreationReason(): ?string
    {
        $reason = $this->creationData['reason'] ?? null;

        return is_string($reason) ? $reason : null;
    }

    /**
     * 获取批次信息
     *
     * @return array<string, mixed>|null
     */
    public function getBatchInfo(): ?array
    {
        $batch = $this->creationData['batch'] ?? null;

        /** @var array<string, mixed>|null */
        return is_array($batch) ? $batch : null;
    }
}
