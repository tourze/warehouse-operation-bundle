<?php

namespace Tourze\WarehouseOperationBundle\Event;

use Tourze\WarehouseOperationBundle\Entity\WarehouseTask;

/**
 * 任务事件抽象基类
 *
 * 为所有任务相关事件提供统一的基础结构。
 */
abstract class AbstractTaskEvent
{
    /**
     * @param WarehouseTask $task 任务对象
     * @param array<string, mixed> $context 上下文信息
     */
    public function __construct(
        protected readonly WarehouseTask $task,
        protected readonly array $context = [],
    ) {
    }

    /**
     * 获取任务对象
     */
    public function getTask(): WarehouseTask
    {
        return $this->task;
    }

    /**
     * 获取上下文信息
     *
     * @return array<string, mixed>
     */
    public function getContext(): array
    {
        return $this->context;
    }
}
