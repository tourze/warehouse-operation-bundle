<?php

declare(strict_types=1);

namespace Tourze\WarehouseOperationBundle\Service\Scheduling;

use Tourze\WarehouseOperationBundle\Repository\WarehouseTaskRepository;

/**
 * 作业员负载均衡服务
 */
final class WorkerLoadBalancer
{
    /**
     * 筛选合格的作业员
     *
     * @param array<int, array<string, mixed>> $availableWorkers
     * @param array<string, mixed> $constraints
     * @return array<int, array<string, mixed>>
     */
    public function filterEligibleWorkers(array $availableWorkers, array $constraints): array
    {
        $maxTasksConfig = $constraints['max_tasks_per_worker'] ?? 10;
        $maxTasksPerWorker = is_int($maxTasksConfig) ? $maxTasksConfig : 10;

        return array_filter($availableWorkers, function (array $worker) use ($maxTasksPerWorker): bool {
            return $this->isWorkerEligible($worker, $maxTasksPerWorker);
        });
    }

    /**
     * 计算工作量得分
     */
    public function calculateWorkloadScore(int $currentWorkload): float
    {
        $maxTasks = 10;

        return max(0, 1 - ($currentWorkload / $maxTasks));
    }

    /**
     * 检查作业员是否符合条件
     *
     * @param array<string, mixed> $worker
     */
    private function isWorkerEligible(array $worker, int $maxTasksPerWorker): bool
    {
        $currentWorkload = is_int($worker['current_workload'] ?? null) ? $worker['current_workload'] : 0;
        $availability = is_string($worker['availability'] ?? null) ? $worker['availability'] : '';

        return $currentWorkload < $maxTasksPerWorker && 'available' === $availability;
    }
}
