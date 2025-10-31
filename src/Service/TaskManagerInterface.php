<?php

declare(strict_types=1);

namespace Tourze\WarehouseOperationBundle\Service;

use Tourze\WarehouseOperationBundle\Entity\WarehouseTask;
use Tourze\WarehouseOperationBundle\Enum\TaskStatus;
use Tourze\WarehouseOperationBundle\Enum\TaskType;

/**
 * 任务管理核心接口
 *
 * 提供仓库任务的完整生命周期管理功能，包括任务创建、分配、执行、完成等操作。
 */
interface TaskManagerInterface
{
    /**
     * 创建作业任务
     *
     * @param TaskType $type 任务类型
     * @param array<string, mixed> $data 任务数据
     * @return WarehouseTask 创建的任务对象
     */
    public function createTask(TaskType $type, array $data): WarehouseTask;

    /**
     * 分配任务给作业员
     *
     * @param int $taskId 任务ID
     * @param int $workerId 作业员ID
     * @return bool 分配是否成功
     */
    public function assignTask(int $taskId, int $workerId): bool;

    /**
     * 完成任务
     *
     * @param int $taskId 任务ID
     * @param array<string, mixed> $result 完成结果数据
     * @return bool 完成是否成功
     */
    public function completeTask(int $taskId, array $result): bool;

    /**
     * 暂停任务
     *
     * @param int $taskId 任务ID
     * @param string $reason 暂停原因
     * @return bool 暂停是否成功
     */
    public function pauseTask(int $taskId, string $reason): bool;

    /**
     * 恢复任务
     *
     * @param int $taskId 任务ID
     * @return bool 恢复是否成功
     */
    public function resumeTask(int $taskId): bool;

    /**
     * 取消任务
     *
     * @param int $taskId 任务ID
     * @param string $reason 取消原因
     * @return bool 取消是否成功
     */
    public function cancelTask(int $taskId, string $reason): bool;

    /**
     * 查询任务列表
     *
     * @param TaskStatus $status 任务状态
     * @param int|null $limit 限制数量
     * @return array<WarehouseTask> 任务列表
     */
    public function findTasksByStatus(TaskStatus $status, ?int $limit = null): array;

    /**
     * 查询任务执行轨迹
     *
     * @param int $taskId 任务ID
     * @return array<array<string, mixed>> 任务轨迹数据
     */
    public function getTaskTrace(int $taskId): array;
}
