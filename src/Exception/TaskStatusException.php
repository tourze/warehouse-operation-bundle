<?php

namespace Tourze\WarehouseOperationBundle\Exception;

class TaskStatusException extends WarehouseOperationException
{
    public static function cannotPerformAction(int $taskId, string $currentStatus, string $action): self
    {
        return new self(
            "Cannot perform action '{$action}' on task {$taskId} with status '{$currentStatus}'",
            0,
            null,
            [
                'task_id' => $taskId,
                'current_status' => $currentStatus,
                'action' => $action,
            ]
        );
    }
}
