<?php

declare(strict_types=1);

namespace Tourze\WarehouseOperationBundle\Service\Scheduling;

use Tourze\WarehouseOperationBundle\Entity\WarehouseTask;

/**
 * 作业员分配服务接口
 */
interface WorkerAssignmentServiceInterface
{
    /**
     * 分配任务给最优工人
     *
     * @param WarehouseTask $task
     * @param array<int, array<string, mixed>> $availableWorkers
     * @param array<string, mixed> $constraints
     * @return array<string, mixed>|null
     */
    public function assignTaskToOptimalWorker(WarehouseTask $task, array $availableWorkers, array $constraints): ?array;

    /**
     * 基于技能分配工人
     *
     * @param WarehouseTask $task
     * @param array<string, mixed> $options
     * @return array<string, mixed>|null
     */
    public function assignWorkerBySkill(WarehouseTask $task, array $options = []): ?array;

    /**
     * 计算任务与工人的匹配度
     *
     * @param WarehouseTask $task
     * @param array<string, mixed> $worker
     * @return float
     */
    public function calculateTaskWorkerMatch(WarehouseTask $task, array $worker): float;
}
