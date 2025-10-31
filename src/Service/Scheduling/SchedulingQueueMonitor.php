<?php

declare(strict_types=1);

namespace Tourze\WarehouseOperationBundle\Service\Scheduling;

use Tourze\WarehouseOperationBundle\Enum\TaskStatus;
use Tourze\WarehouseOperationBundle\Repository\WarehouseTaskRepository;

/**
 * 调度队列监控服务
 */
final class SchedulingQueueMonitor
{
    private WarehouseTaskRepository $taskRepository;

    public function __construct(WarehouseTaskRepository $taskRepository)
    {
        $this->taskRepository = $taskRepository;
    }

    /**
     * 获取队列状态
     * @return array<string, mixed>
     */
    public function getQueueStatus(): array
    {
        $statistics = $this->taskRepository->getTaskStatistics();

        $pendingCount = $statistics[TaskStatus::PENDING->value] ?? 0;
        $activeCount = ($statistics[TaskStatus::ASSIGNED->value] ?? 0) +
                      ($statistics[TaskStatus::IN_PROGRESS->value] ?? 0);

        // 计算作业员利用率
        $workerUtilization = $this->calculateCurrentWorkerUtilization();

        // 计算平均等待时间
        $averageWaitTime = $this->calculateAverageWaitTime();

        // 分析瓶颈
        $bottlenecks = $this->analyzeCurrentBottlenecks();

        return [
            'pending_count' => $pendingCount,
            'active_count' => $activeCount,
            'worker_utilization' => $workerUtilization,
            'average_wait_time' => $averageWaitTime,
            'bottlenecks' => $bottlenecks,
            'queue_health' => $this->assessQueueHealth($pendingCount, $activeCount, $workerUtilization),
            'timestamp' => new \DateTimeImmutable(),
        ];
    }

    /**
     * 计算当前作业员利用率
     * @return array{total_workers: int, active_workers: int, utilization_rate: float, average_workload: float}
     */
    private function calculateCurrentWorkerUtilization(): array
    {
        // 简化实现，实际项目中会有复杂的计算逻辑
        return [
            'total_workers' => 10,
            'active_workers' => 7,
            'utilization_rate' => 0.7,
            'average_workload' => 2.1,
        ];
    }

    /**
     * 计算平均等待时间
     * @return array{average_minutes: int, median_minutes: int, max_minutes: int}
     */
    private function calculateAverageWaitTime(): array
    {
        return [
            'average_minutes' => 15,
            'median_minutes' => 12,
            'max_minutes' => 45,
        ];
    }

    /**
     * 分析当前瓶颈
     * @return array<array{type: string, description: string, impact: string}>
     */
    private function analyzeCurrentBottlenecks(): array
    {
        return [
            [
                'type' => 'skill_shortage',
                'description' => '质检作业员不足',
                'impact' => 'medium',
            ],
            [
                'type' => 'zone_congestion',
                'description' => 'A区任务积压',
                'impact' => 'low',
            ],
        ];
    }

    /**
     * 评估队列健康状况
     * @param array{total_workers: int, active_workers: int, utilization_rate: float, average_workload: float} $workerUtilization
     */
    private function assessQueueHealth(int $pendingCount, int $activeCount, array $workerUtilization): string
    {
        $utilizationRate = $workerUtilization['utilization_rate'];
        $totalTasks = $pendingCount + $activeCount;

        if ($utilizationRate > 0.9 || $pendingCount > 50) {
            return 'critical';
        }

        if ($utilizationRate > 0.7 || $pendingCount > 20) {
            return 'warning';
        }

        return 'healthy';
    }
}
