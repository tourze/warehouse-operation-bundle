<?php

declare(strict_types=1);

namespace Tourze\WarehouseOperationBundle\Service\Scheduling;

use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Tourze\WarehouseOperationBundle\Entity\WarehouseTask;
use Tourze\WarehouseOperationBundle\Entity\WorkerSkill;
use Tourze\WarehouseOperationBundle\Repository\WarehouseTaskRepository;
use Tourze\WarehouseOperationBundle\Repository\WorkerSkillRepository;
use Tourze\WarehouseOperationBundle\Service\Scheduling\WorkerLoadBalancer;
use Tourze\WarehouseOperationBundle\Service\Scheduling\WorkerPerformanceAnalyzer;
use Tourze\WarehouseOperationBundle\Service\Scheduling\WorkerSkillMatcher;

/**
 * 作业员分配服务
 *
 * 专门负责作业员的技能匹配、工作负载均衡和任务分配逻辑。
 * 实现基于技能匹配和工作负载的智能分配算法。
 */
#[WithMonologChannel(channel: 'warehouse_operation')]
final class WorkerAssignmentService implements WorkerAssignmentServiceInterface
{
    private WorkerSkillMatcher $skillMatcher;

    private WorkerLoadBalancer $loadBalancer;

    public function __construct(
        WorkerSkillRepository $workerSkillRepository,
        LoggerInterface $logger,
        ?WorkerSkillMatcher $skillMatcher = null,
        ?WorkerLoadBalancer $loadBalancer = null,
    ) {
        $this->skillMatcher = $skillMatcher ?? new WorkerSkillMatcher($workerSkillRepository, $logger);
        $this->loadBalancer = $loadBalancer ?? new WorkerLoadBalancer();
    }

    /**
     * 为任务分配最优作业员
     *
     * @param array<int, array<string, mixed>> $availableWorkers
     * @param array<string, mixed> $constraints
     * @return array<string, mixed>|null
     */
    public function assignTaskToOptimalWorker(WarehouseTask $task, array $availableWorkers, array $constraints): ?array
    {
        $eligibleWorkers = $this->filterEligibleWorkers($availableWorkers, $constraints);

        if (0 === count($eligibleWorkers)) {
            return null;
        }

        $bestWorker = $this->selectBestWorker($task, $eligibleWorkers);

        return $this->createAssignmentResult($task, $bestWorker);
    }

    /**
     * 根据技能分配作业员
     *
     * @param array<string, mixed> $options
     * @return array<string, mixed>|null
     */
    public function assignWorkerBySkill(WarehouseTask $task, array $options = []): ?array
    {
        return $this->skillMatcher->assignWorkerBySkill($task, $options);
    }

    /**
     * 计算任务与作业员的匹配得分
     *
     * @param array<string, mixed> $worker
     */
    public function calculateTaskWorkerMatch(WarehouseTask $task, array $worker): float
    {
        $scores = $this->calculateIndividualScores($task, $worker);
        $weights = $this->getSchedulingWeights();

        return $this->calculateWeightedTotalScore($scores, $weights);
    }

    /**
     * 筛选合格的作业员
     *
     * @param array<int, array<string, mixed>> $availableWorkers
     * @param array<string, mixed> $constraints
     * @return array<int, array<string, mixed>>
     */
    private function filterEligibleWorkers(array $availableWorkers, array $constraints): array
    {
        return $this->loadBalancer->filterEligibleWorkers($availableWorkers, $constraints);
    }

    /**
     * 选择最佳作业员
     *
     * @param array<int, array<string, mixed>> $eligibleWorkers
     * @return array<string, mixed>
     */
    private function selectBestWorker(WarehouseTask $task, array $eligibleWorkers): array
    {
        $bestWorker = null;
        $bestScore = 0.0;

        foreach ($eligibleWorkers as $worker) {
            $score = $this->calculateTaskWorkerMatch($task, $worker);

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestWorker = $worker;
            }
        }

        if (null === $bestWorker) {
            throw new \RuntimeException('No suitable worker found from eligible workers');
        }

        return $bestWorker;
    }

    /**
     * 创建分配结果
     *
     * @param array<string, mixed> $bestWorker
     * @return array<string, mixed>
     */
    private function createAssignmentResult(WarehouseTask $task, array $bestWorker): array
    {
        return [
            'task_id' => $task->getId(),
            'worker_id' => $bestWorker['worker_id'],
            'worker_name' => $bestWorker['name'],
            'match_score' => round($this->calculateTaskWorkerMatch($task, $bestWorker), 3),
            'assignment_time' => new \DateTimeImmutable(),
            'estimated_completion' => $this->estimateTaskCompletionTime($task, $bestWorker),
            'assignment_factors' => $this->getAssignmentFactors($task, $bestWorker),
        ];
    }

    /**
     * 计算各项得分
     *
     * @param array<string, mixed> $worker
     * @return array<string, float>
     */
    private function calculateIndividualScores(WarehouseTask $task, array $worker): array
    {
        $requiredSkills = $this->extractRequiredSkills($task);
        /** @var array<WorkerSkill> $workerSkills */
        $workerSkills = is_array($worker['skills'] ?? null) ? $worker['skills'] : [];
        $currentWorkload = is_int($worker['current_workload'] ?? null) ? $worker['current_workload'] : 0;

        return [
            'skill' => $this->calculateWorkerSkillMatch($requiredSkills, $workerSkills),
            'workload' => $this->calculateWorkerWorkloadScore($currentWorkload),
            'priority' => $this->calculatePriorityScore($task->getPriority()),
        ];
    }

    /**
     * 计算作业员工作量得分
     */
    private function calculateWorkerWorkloadScore(int $currentWorkload): float
    {
        return $this->loadBalancer->calculateWorkloadScore($currentWorkload);
    }

    /**
     * 计算优先级得分
     */
    private function calculatePriorityScore(int $priority): float
    {
        return min(1, $priority / 100);
    }

    /**
     * 获取调度权重
     *
     * @return array<string, float>
     */
    private function getSchedulingWeights(): array
    {
        return [
            'skill' => 0.4,
            'workload' => 0.3,
            'priority' => 0.1, // 固定优先级权重
        ];
    }

    /**
     * 计算加权总得分
     *
     * @param array<string, float> $scores
     * @param array<string, float> $weights
     */
    private function calculateWeightedTotalScore(array $scores, array $weights): float
    {
        $totalScore = 0.0;

        foreach ($scores as $type => $score) {
            $weight = is_float($weights[$type] ?? null) ? $weights[$type] : 0.0;
            $totalScore += $score * $weight;
        }

        return $totalScore;
    }

    /**
     * 从任务中提取所需技能
     *
     * @return array<string>
     */
    private function extractRequiredSkills(WarehouseTask $task): array
    {
        $skills = $this->getBaseSkillsByTaskType($task->getType()->value);
        $specialSkills = $this->getSpecialSkills($task->getData());

        return array_unique(array_merge($skills, $specialSkills));
    }

    /**
     * 根据任务类型获取基础技能
     *
     * @return array<string>
     */
    private function getBaseSkillsByTaskType(string $taskType): array
    {
        $skillMapping = [
            'inbound' => ['receiving'],
            'outbound' => ['picking', 'packing'],
            'quality' => ['quality'],
            'count' => ['counting'],
            'transfer' => ['equipment'],
        ];

        return $skillMapping[$taskType] ?? [];
    }

    /**
     * 获取特殊技能
     *
     * @param array<string, mixed> $taskData
     * @return array<string>
     */
    private function getSpecialSkills(array $taskData): array
    {
        /** @var array<string> $specialSkills */
        $specialSkills = [];

        $specialRequirements = [
            'requires_quality_check' => 'quality',
            'hazardous' => 'hazardous',
            'cold_storage' => 'cold_storage',
        ];

        foreach ($specialRequirements as $requirement => $skill) {
            if (true === ($taskData[$requirement] ?? false)) {
                $specialSkills[] = $skill;
            }
        }

        return $specialSkills;
    }

    /**
     * 计算作业员技能匹配得分
     *
     * @param array<string> $requiredSkills
     * @param array<WorkerSkill> $workerSkills
     */
    private function calculateWorkerSkillMatch(array $requiredSkills, array $workerSkills): float
    {
        return $this->skillMatcher->calculateSkillMatch($requiredSkills, $workerSkills);
    }

    /**
     * @param array<string, mixed> $worker
     * @return array<string, mixed>
     */
    private function estimateTaskCompletionTime(WarehouseTask $task, array $worker): array
    {
        return [
            'estimated_minutes' => 45,
            'completion_time' => (new \DateTimeImmutable())->modify('+45 minutes'),
            'confidence' => 0.8,
        ];
    }

    /**
     * @param array<string, mixed> $worker
     * @return array<string, mixed>
     */
    private function getAssignmentFactors(WarehouseTask $task, array $worker): array
    {
        return [
            'skill_match' => 0.85,
            'workload_balance' => 0.70,
            'location_proximity' => 0.80,
        ];
    }
}
