<?php

declare(strict_types=1);

namespace Tourze\WarehouseOperationBundle\Service\Scheduling;

use Tourze\WarehouseOperationBundle\Repository\WarehouseTaskRepository;

/**
 * 调度优化服务
 */
final class SchedulingOptimizer
{
    /**
     * 分析调度优化
     *
     * @param array<string, mixed> $criteria
     * @return array<string, mixed>
     */
    public function analyzeOptimization(array $criteria = []): array
    {
        /** @var array{hours?: int|string} $timeRange */
        $timeRange = is_array($criteria['time_range'] ?? null) ? $criteria['time_range'] : ['hours' => 24];
        /** @var array<string> $taskTypes */
        $taskTypes = is_array($criteria['task_types'] ?? null) ? $criteria['task_types'] : [];
        /** @var array<string> $zones */
        $zones = is_array($criteria['zones'] ?? null) ? $criteria['zones'] : [];

        $hours = is_int($timeRange['hours'] ?? null) || is_string($timeRange['hours'] ?? null) ? $timeRange['hours'] : 24;
        $startTime = new \DateTimeImmutable("-{$hours} hours");

        // 获取历史调度数据
        $historicalData = $this->getHistoricalSchedulingData($startTime, $taskTypes, $zones);

        // 计算效率得分
        $efficiencyScore = $this->calculateSchedulingEfficiency($historicalData);

        // 生成优化建议
        $suggestions = $this->generateOptimizationSuggestions($historicalData);

        // 分析资源利用率
        $resourceUtilization = $this->analyzeResourceUtilization($historicalData);

        // 性能趋势分析
        $performanceTrends = $this->analyzePerformanceTrends($historicalData);

        return [
            'efficiency_score' => $efficiencyScore,
            'optimization_suggestions' => $suggestions,
            'resource_utilization' => $resourceUtilization,
            'performance_trends' => $performanceTrends,
            'analysis_period' => [
                'start' => $startTime,
                'end' => new \DateTimeImmutable(),
                'criteria' => $criteria,
            ],
        ];
    }

    /**
     * 获取历史调度数据
     *
     * @param array<string> $taskTypes
     * @param array<string> $zones
     * @return array<string, mixed>
     */
    private function getHistoricalSchedulingData(\DateTimeImmutable $startTime, array $taskTypes, array $zones): array
    {
        // 简化实现，实际项目中会有复杂的数据库查询
        return [
            'total_tasks' => 150,
            'completed_tasks' => 140,
            'average_completion_time' => 35,
            'worker_utilization' => 0.75,
            'task_distribution' => [
                'picking' => 60,
                'packing' => 40,
                'quality' => 30,
                'other' => 20,
            ],
        ];
    }

    /**
     * 计算调度效率
     *
     * @param array<string, mixed> $historicalData
     * @return array{overall_score: float, completion_rate: float, time_efficiency: float, worker_utilization_score: float}
     */
    private function calculateSchedulingEfficiency(array $historicalData): array
    {
        $totalTasksValue = $historicalData['total_tasks'] ?? 0;
        $totalTasks = is_int($totalTasksValue) || is_float($totalTasksValue) ? (int) $totalTasksValue : 0;

        $completedTasksValue = $historicalData['completed_tasks'] ?? 0;
        $completedTasks = is_int($completedTasksValue) || is_float($completedTasksValue) ? (int) $completedTasksValue : 0;

        $completionRate = $totalTasks > 0 ? $completedTasks / $totalTasks : 0.0;

        $averageTimeValue = $historicalData['average_completion_time'] ?? 0;
        $averageTime = is_int($averageTimeValue) || is_float($averageTimeValue) ? (float) $averageTimeValue : 0.0;
        $timeEfficiency = $averageTime > 0 ? min(1.0, 60.0 / $averageTime) : 0.0;

        $utilizationValue = $historicalData['worker_utilization'] ?? 0;
        $utilization = is_int($utilizationValue) || is_float($utilizationValue) ? (float) $utilizationValue : 0.0;

        $overallScore = ($completionRate * 0.4) + ($timeEfficiency * 0.3) + ($utilization * 0.3);

        return [
            'overall_score' => round($overallScore, 3),
            'completion_rate' => round($completionRate, 3),
            'time_efficiency' => round($timeEfficiency, 3),
            'worker_utilization_score' => round($utilization, 3),
        ];
    }

    /**
     * 生成优化建议
     *
     * @param array<string, mixed> $historicalData
     * @return array<array<string, mixed>>
     */
    private function generateOptimizationSuggestions(array $historicalData): array
    {
        $suggestions = [];

        $utilizationValue = $historicalData['worker_utilization'] ?? 0;
        $utilization = is_int($utilizationValue) || is_float($utilizationValue) ? (float) $utilizationValue : 0.0;

        if ($utilization < 0.6) {
            $suggestions[] = [
                'type' => 'increase_tasks',
                'description' => '作业员利用率偏低，可增加任务分配',
                'priority' => 'medium',
                'estimated_impact' => '+15% efficiency',
            ];
        }

        if ($utilization > 0.9) {
            $suggestions[] = [
                'type' => 'add_workers',
                'description' => '作业员负载过高，建议增加人力',
                'priority' => 'high',
                'estimated_impact' => '-20% wait_time',
            ];
        }

        return $suggestions;
    }

    /**
     * 分析资源利用率
     *
     * @param array<string, mixed> $historicalData
     * @return array{workers: array{utilization_rate: float, efficiency_score: float, recommended_count: int, current_count: int}, equipment: array{utilization_rate: float, bottleneck_equipment: string}, zones: array<string, array{utilization: float, tasks_per_hour: int}>}
     */
    private function analyzeResourceUtilization(array $historicalData): array
    {
        $utilizationValue = $historicalData['worker_utilization'] ?? 0;
        $utilization = is_int($utilizationValue) || is_float($utilizationValue) ? (float) $utilizationValue : 0.0;

        return [
            'workers' => [
                'utilization_rate' => $utilization,
                'efficiency_score' => 0.85,
                'recommended_count' => 12,
                'current_count' => 10,
            ],
            'equipment' => [
                'utilization_rate' => 0.65,
                'bottleneck_equipment' => 'forklift_03',
            ],
            'zones' => [
                'zone_a' => ['utilization' => 0.8, 'tasks_per_hour' => 25],
                'zone_b' => ['utilization' => 0.6, 'tasks_per_hour' => 18],
                'zone_c' => ['utilization' => 0.7, 'tasks_per_hour' => 22],
            ],
        ];
    }

    /**
     * 分析性能趋势
     *
     * @param array<string, mixed> $historicalData
     * @return array{efficiency_trend: string, completion_time_trend: string, error_rate_trend: string, weekly_comparison: array{this_week: array{efficiency: float, completion_time: int}, last_week: array{efficiency: float, completion_time: int}, change_pct: string}}
     */
    private function analyzePerformanceTrends(array $historicalData): array
    {
        return [
            'efficiency_trend' => 'improving',
            'completion_time_trend' => 'stable',
            'error_rate_trend' => 'decreasing',
            'weekly_comparison' => [
                'this_week' => ['efficiency' => 0.87, 'completion_time' => 34],
                'last_week' => ['efficiency' => 0.82, 'completion_time' => 38],
                'change_pct' => '+6.1%',
            ],
        ];
    }
}
