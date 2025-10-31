<?php

namespace Tourze\WarehouseOperationBundle\Exception;

class TaskNotFoundException extends WarehouseOperationException
{
    public static function forTaskId(int $taskId): self
    {
        return new self(
            "Task with ID {$taskId} not found",
            0,
            null,
            ['task_id' => $taskId]
        );
    }
}
