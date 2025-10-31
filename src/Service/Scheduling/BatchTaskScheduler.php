<?php

declare(strict_types=1);

namespace Tourze\WarehouseOperationBundle\Service\Scheduling;

use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Tourze\WarehouseOperationBundle\Entity\WarehouseTask;
use Tourze\WarehouseOperationBundle\Repository\WarehouseTaskRepository;
use Tourze\WarehouseOperationBundle\Service\Scheduling\WorkerAssignmentService;

/**
 * 批量任务调度服务
 */
#[WithMonologChannel(channel: 'warehouse_operation')]
final class BatchTaskScheduler
{
    private WorkerAssignmentServiceInterface $workerAssignmentService;

    private LoggerInterface $logger;

    public function __construct(
        WorkerAssignmentServiceInterface $workerAssignmentService,
        LoggerInterface $logger,
    ) {
        $this->workerAssignmentService = $workerAssignmentService;
        $this->logger = $logger;
    }

    /**
     * 批量调度任务
     *
     * @param array<mixed> $pendingTasks
     * @param array<string, mixed> $constraints
     * @return array<string, mixed>
     */
    public function scheduleTaskBatch(array $pendingTasks, array $constraints = []): array
    {
        $this->logBatchSchedulingStart($pendingTasks, $constraints);

        if (0 === count($pendingTasks)) {
            return $this->createEmptySchedulingResult();
        }

        $batchContext = $this->initializeBatchContext();
        $availableWorkers = $this->getAvailableWorkers($constraints);
        $sortedTasks = $this->sortTasksByPriority($pendingTasks);

        $assignmentResult = $this->executeBatchAssignment($sortedTasks, $availableWorkers, $constraints);
        $statistics = $this->generateBatchStatistics($batchContext['start_time'], count($pendingTasks), $assignmentResult);
        $recommendations = $this->generateOptimizationRecommendations($assignmentResult['assignments'], $assignmentResult['unassigned'], $assignmentResult['available_workers']);

        $this->logBatchSchedulingComplete($statistics);

        return $this->formatBatchSchedulingResult($assignmentResult, $statistics, $recommendations);
    }

    /**
     * 记录批量调度开始
     *
     * @param array<mixed> $pendingTasks
     * @param array<string, mixed> $constraints
     */
    private function logBatchSchedulingStart(array $pendingTasks, array $constraints): void
    {
        $this->logger->info('开始批量任务调度', [
            'task_count' => count($pendingTasks),
            'constraints' => array_keys($constraints),
        ]);
    }

    /**
     * 创建空的调度结果
     *
     * @return array<string, mixed>
     */
    private function createEmptySchedulingResult(): array
    {
        return [
            'assignments' => [],
            'unassigned' => [],
            'statistics' => [
                'total_tasks' => 0,
                'assigned_count' => 0,
                'unassigned_count' => 0,
                'assignment_rate' => 0.0,
                'processing_time_ms' => 0,
            ],
            'recommendations' => [],
        ];
    }

    /**
     * 初始化批量处理上下文
     *
     * @return array{start_time: float}
     */
    private function initializeBatchContext(): array
    {
        return [
            'start_time' => microtime(true),
        ];
    }

    /**
     * 获取可用作业员
     *
     * @param array<string, mixed> $constraints
     * @return array<int, array<string, mixed>>
     */
    private function getAvailableWorkers(array $constraints): array
    {
        // 简化实现，实际项目中会有复杂的查询逻辑
        return [
            1 => ['worker_id' => 1, 'name' => 'Worker 1', 'current_workload' => 2, 'availability' => 'available'],
            2 => ['worker_id' => 2, 'name' => 'Worker 2', 'current_workload' => 1, 'availability' => 'available'],
        ];
    }

    /**
     * 按优先级排序任务
     *
     * @param array<mixed> $pendingTasks
     * @return array<mixed>
     */
    private function sortTasksByPriority(array $pendingTasks): array
    {
        usort($pendingTasks, function ($a, $b) {
            // 确保 $a 和 $b 是对象且有 getPriority 方法
            if (!is_object($a) || !method_exists($a, 'getPriority')) {
                return 1;
            }
            if (!is_object($b) || !method_exists($b, 'getPriority')) {
                return -1;
            }

            return $b->getPriority() <=> $a->getPriority();
        });

        return $pendingTasks;
    }

    /**
     * 执行批量分配
     *
     * @param array<mixed> $sortedTasks
     * @param array<int, array<string, mixed>> $availableWorkers
     * @param array<string, mixed> $constraints
     * @return array{available_workers: array<int, array<string, mixed>>, assignments: array<array<string, mixed>>, unassigned: array<mixed>}
     */
    private function executeBatchAssignment(array $sortedTasks, array $availableWorkers, array $constraints): array
    {
        [$availableWorkers, $assignments, $unassigned] = $this->processTaskAssignments($sortedTasks, $availableWorkers, $constraints);

        return [
            'available_workers' => $availableWorkers,
            'assignments' => $assignments,
            'unassigned' => $unassigned,
        ];
    }

    /**
     * 处理任务分配
     *
     * @param array<mixed> $sortedTasks
     * @param array<int, array<string, mixed>> $availableWorkers
     * @param array<string, mixed> $constraints
     * @return array{array<int, array<string, mixed>>, array<array<string, mixed>>, array<mixed>}
     */
    private function processTaskAssignments(array $sortedTasks, array $availableWorkers, array $constraints): array
    {
        /** @var array<array<string, mixed>> $assignments */
        $assignments = [];
        /** @var array<mixed> $unassigned */
        $unassigned = [];

        foreach ($sortedTasks as $task) {
            if (!$this->isValidTask($task)) {
                $unassigned[] = $task;
                continue;
            }

            /** @var WarehouseTask $task */
            $result = $this->attemptTaskAssignment($task, $availableWorkers, $constraints);

            if ($this->isAssignmentSuccessful($result)) {
                /** @var array<string, mixed> $result */
                $assignments[] = $result;
                $availableWorkers = $this->updateWorkerWorkload($availableWorkers, $result);
            } else {
                $unassigned[] = $task;
            }
        }

        return [$availableWorkers, $assignments, $unassigned];
    }

    /**
     * 验证任务是否有效
     */
    private function isValidTask(mixed $task): bool
    {
        return $task instanceof WarehouseTask;
    }

    /**
     * 尝试分配任务
     *
     * @param array<int, array<string, mixed>> $availableWorkers
     * @param array<string, mixed> $constraints
     * @return mixed
     */
    private function attemptTaskAssignment(WarehouseTask $task, array $availableWorkers, array $constraints): mixed
    {
        return $this->workerAssignmentService->assignTaskToOptimalWorker($task, $availableWorkers, $constraints);
    }

    /**
     * 检查分配是否成功
     */
    private function isAssignmentSuccessful(mixed $result): bool
    {
        return is_array($result) && count($result) > 0;
    }

    /**
     * 更新作业员工作量
     *
     * @param array<int, array<string, mixed>> $availableWorkers
     * @param array<string, mixed> $assignmentResult
     * @return array<int, array<string, mixed>>
     */
    private function updateWorkerWorkload(array $availableWorkers, array $assignmentResult): array
    {
        $workerIdRaw = $assignmentResult['worker_id'] ?? null;

        if (!is_int($workerIdRaw) || !isset($availableWorkers[$workerIdRaw])) {
            return $availableWorkers;
        }

        $currentWorkload = $availableWorkers[$workerIdRaw]['current_workload'] ?? 0;
        $availableWorkers[$workerIdRaw]['current_workload'] = is_int($currentWorkload) ? $currentWorkload + 1 : 1;

        return $availableWorkers;
    }

    /**
     * 生成批量统计信息
     *
     * @param array{assignments: array<array<string, mixed>>, unassigned: array<mixed>, available_workers: array<int, array<string, mixed>>} $assignmentResult
     * @return array<string, mixed>
     */
    private function generateBatchStatistics(float $startTime, int $totalTasks, array $assignmentResult): array
    {
        $processingTime = (microtime(true) - $startTime) * 1000;
        $assignedCount = count($assignmentResult['assignments']);
        $unassignedCount = count($assignmentResult['unassigned']);

        return [
            'total_tasks' => $totalTasks,
            'assigned_count' => $assignedCount,
            'unassigned_count' => $unassignedCount,
            'assignment_rate' => $totalTasks > 0 ? round($assignedCount / $totalTasks, 3) : 0.0,
            'processing_time_ms' => round($processingTime, 2),
            'average_match_score' => $this->calculateAverageMatchScore($assignmentResult['assignments']),
            'worker_utilization' => $this->calculateWorkerUtilization($assignmentResult['available_workers'], $assignmentResult['assignments']),
        ];
    }

    /**
     * 计算平均匹配分数
     *
     * @param array<array<string, mixed>> $assignments
     */
    private function calculateAverageMatchScore(array $assignments): float
    {
        if (0 === count($assignments)) {
            return 0.0;
        }

        $totalScore = array_sum(array_column($assignments, 'match_score'));

        return round($totalScore / count($assignments), 3);
    }

    /**
     * 计算作业员利用率
     *
     * @param array<int, array<string, mixed>> $availableWorkers
     * @param array<array<string, mixed>> $assignments
     * @return array<int, array{assigned: bool, workload_before: int, workload_after: int}>
     */
    private function calculateWorkerUtilization(array $availableWorkers, array $assignments): array
    {
        /** @var array<int, array{assigned: bool, workload_before: int, workload_after: int}> $utilization */
        $utilization = [];
        $assignedWorkers = array_column($assignments, 'worker_id');

        foreach ($availableWorkers as $worker) {
            $workerIdRaw = $worker['worker_id'] ?? null;
            if (!is_int($workerIdRaw)) {
                continue;
            }
            $currentWorkloadRaw = $worker['current_workload'] ?? 0;
            $currentWorkload = is_int($currentWorkloadRaw) ? $currentWorkloadRaw : 0;

            $isAssigned = in_array($workerIdRaw, $assignedWorkers, true);
            $utilization[$workerIdRaw] = [
                'assigned' => $isAssigned,
                'workload_before' => $currentWorkload - ($isAssigned ? 1 : 0),
                'workload_after' => $currentWorkload,
            ];
        }

        return $utilization;
    }

    /**
     * 生成优化建议
     *
     * @param array<array<string, mixed>> $assignments
     * @param array<mixed> $unassigned
     * @param array<int, array<string, mixed>> $availableWorkers
     * @return array<array<string, mixed>>
     */
    private function generateOptimizationRecommendations(array $assignments, array $unassigned, array $availableWorkers): array
    {
        /** @var array<array{type: string, description: string, priority: string, impact: string}> $recommendations */
        $recommendations = [];

        if (count($unassigned) > 0) {
            $recommendations[] = [
                'type' => 'increase_workers',
                'description' => '增加可用作业员数量',
                'priority' => 'high',
            ];
        }

        $unassignedRate = count($unassigned) / (count($assignments) + count($unassigned));
        if ($unassignedRate > 0.3) {
            $recommendations[] = [
                'type' => 'adjust_priorities',
                'description' => '调整任务优先级分配策略',
                'priority' => 'medium',
            ];
        }

        return $recommendations;
    }

    /**
     * 记录批量调度完成
     *
     * @param array<string, mixed> $statistics
     */
    private function logBatchSchedulingComplete(array $statistics): void
    {
        $this->logger->info('批量任务调度完成', $statistics);
    }

    /**
     * 格式化批量调度结果
     *
     * @param array{assignments: array<array<string, mixed>>, unassigned: array<mixed>, available_workers: array<int, array<string, mixed>>} $assignmentResult
     * @param array<string, mixed> $statistics
     * @param array<array<string, mixed>> $recommendations
     * @return array<string, mixed>
     */
    private function formatBatchSchedulingResult(array $assignmentResult, array $statistics, array $recommendations): array
    {
        return [
            'assignments' => $assignmentResult['assignments'],
            'unassigned' => $assignmentResult['unassigned'],
            'statistics' => $statistics,
            'recommendations' => $recommendations,
        ];
    }
}
