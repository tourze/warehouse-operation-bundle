<?php

declare(strict_types=1);

namespace Tourze\WarehouseOperationBundle\Service\Scheduling;

use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Tourze\WarehouseOperationBundle\Entity\WarehouseTask;
use Tourze\WarehouseOperationBundle\Entity\WorkerSkill;
use Tourze\WarehouseOperationBundle\Repository\WorkerSkillRepository;

/**
 * 作业员技能匹配服务
 */
#[WithMonologChannel(channel: 'warehouse_operation')]
final class WorkerSkillMatcher
{
    private WorkerSkillRepository $workerSkillRepository;

    private LoggerInterface $logger;

    /** @var array<string, mixed> 技能权重映射 */
    private array $skillWeights;

    /** @var array<string, mixed> 分配配置 */
    private array $assignmentConfig;

    public function __construct(WorkerSkillRepository $workerSkillRepository, LoggerInterface $logger)
    {
        $this->workerSkillRepository = $workerSkillRepository;
        $this->logger = $logger;

        $this->skillWeights = [
            'picking' => 1.0,
            'packing' => 0.9,
            'quality' => 1.2,
            'counting' => 0.8,
            'equipment' => 1.1,
            'hazardous' => 1.5,
            'cold_storage' => 1.3,
        ];

        $maxTasksEnv = $_ENV['WMS_MAX_TASKS_PER_WORKER'] ?? '10';
        $skillMatchEnv = $_ENV['WMS_SKILL_MATCH_WEIGHT'] ?? '0.4';
        $workloadEnv = $_ENV['WMS_WORKLOAD_WEIGHT'] ?? '0.3';
        $locationEnv = $_ENV['WMS_LOCATION_WEIGHT'] ?? '0.2';
        $performanceEnv = $_ENV['WMS_PERFORMANCE_WEIGHT'] ?? '0.1';

        $this->assignmentConfig = [
            'max_tasks_per_worker' => is_numeric($maxTasksEnv) ? (int) $maxTasksEnv : 10,
            'skill_match_weight' => is_numeric($skillMatchEnv) ? (float) $skillMatchEnv : 0.4,
            'workload_weight' => is_numeric($workloadEnv) ? (float) $workloadEnv : 0.3,
            'location_weight' => is_numeric($locationEnv) ? (float) $locationEnv : 0.2,
            'performance_weight' => is_numeric($performanceEnv) ? (float) $performanceEnv : 0.1,
        ];
    }

    /**
     * 根据技能分配作业员
     *
     * @param array<string, mixed> $options
     * @return array<string, mixed>|null
     */
    public function assignWorkerBySkill(WarehouseTask $task, array $options = []): ?array
    {
        $this->logger->debug('开始技能匹配分配', [
            'task_id' => $task->getId(),
            'task_type' => $task->getType()->value,
            'options' => array_keys($options),
        ]);

        $assignmentWeights = $this->extractAssignmentWeights($options);
        $excludeWorkers = $this->extractExcludeWorkers($options);

        $requiredSkills = $this->extractRequiredSkills($task);
        if (0 === count($requiredSkills)) {
            $this->logSkillDeterminationFailure($task);

            return null;
        }

        $candidateWorkers = $this->findCandidateWorkers($requiredSkills, $excludeWorkers, $task);
        if (0 === count($candidateWorkers)) {
            return null;
        }

        $bestMatch = $this->findBestWorkerMatch(
            $candidateWorkers,
            $task,
            $requiredSkills,
            $assignmentWeights['skill_weight'],
            $assignmentWeights['workload_weight'],
            $assignmentWeights['location_weight'],
            $assignmentWeights['performance_weight']
        );

        $this->logAssignmentResult($task, $bestMatch);

        return $bestMatch;
    }

    /**
     * 计算技能匹配得分
     *
     * @param array<string> $requiredSkills
     * @param array<WorkerSkill> $workerSkills
     */
    public function calculateSkillMatch(array $requiredSkills, array $workerSkills): float
    {
        if (0 === count($requiredSkills)) {
            return 0.8; // 无特殊技能要求时给予基础分数
        }

        $skillMatches = $this->calculateSkillMatches($requiredSkills, $workerSkills);

        return $this->computeFinalSkillScore($skillMatches, $requiredSkills);
    }

    /**
     * 提取分配权重
     *
     * @param array<string, mixed> $options
     * @return array{skill_weight: float, workload_weight: float, location_weight: float, performance_weight: float}
     */
    private function extractAssignmentWeights(array $options): array
    {
        $skillWeight = $this->extractFloatOption($options, 'skill_weight', $this->assignmentConfig['skill_match_weight']);
        $workloadWeight = $this->extractFloatOption($options, 'workload_weight', $this->assignmentConfig['workload_weight']);
        $locationWeight = $this->extractFloatOption($options, 'location_weight', $this->assignmentConfig['location_weight']);
        $performanceWeight = $this->extractFloatOption($options, 'performance_weight', $this->assignmentConfig['performance_weight']);

        return [
            'skill_weight' => $skillWeight,
            'workload_weight' => $workloadWeight,
            'location_weight' => $locationWeight,
            'performance_weight' => $performanceWeight,
        ];
    }

    /**
     * 提取排除的作业员
     *
     * @param array<string, mixed> $options
     * @return array<int>
     */
    private function extractExcludeWorkers(array $options): array
    {
        $excludeWorkers = $options['exclude_workers'] ?? null;
        if (!is_array($excludeWorkers)) {
            return [];
        }

        return array_filter($excludeWorkers, fn ($worker): bool => is_int($worker));
    }

    /**
     * 提取浮点选项
     *
     * @param array<string, mixed> $options
     */
    private function extractFloatOption(array $options, string $key, mixed $default): float
    {
        $value = $options[$key] ?? $default;

        if (is_numeric($value)) {
            return (float) $value;
        }

        return is_numeric($default) ? (float) $default : 0.0;
    }

    /**
     * 从数组中提取浮点值
     *
     * @param array<string, mixed> $array
     */
    private function extractFloatValue(array $array, string $key, float $default): float
    {
        $value = $array[$key] ?? $default;

        return is_numeric($value) ? (float) $value : $default;
    }

    /**
     * 记录技能确定失败
     */
    private function logSkillDeterminationFailure(WarehouseTask $task): void
    {
        $this->logger->warning('无法确定任务所需技能', ['task_id' => $task->getId()]);
    }

    /**
     * 查找候选作业员
     *
     * @param array<string> $requiredSkills
     * @param array<int> $excludeWorkers
     * @return array<WorkerSkill>
     */
    private function findCandidateWorkers(array $requiredSkills, array $excludeWorkers, WarehouseTask $task): array
    {
        $candidateWorkers = $this->workerSkillRepository->findWorkersBySkills(
            $requiredSkills,
            $excludeWorkers
        );

        if (0 === count($candidateWorkers)) {
            $this->logger->warning('未找到具备相关技能的作业员', [
                'task_id' => $task->getId(),
                'required_skills' => $requiredSkills,
            ]);
        }

        return $candidateWorkers;
    }

    /**
     * 记录分配结果
     *
     * @param array<string, mixed>|null $bestMatch
     */
    private function logAssignmentResult(WarehouseTask $task, ?array $bestMatch): void
    {
        if (null !== $bestMatch) {
            $this->logger->info('技能匹配分配成功', [
                'task_id' => $task->getId(),
                'worker_id' => $bestMatch['worker_id'],
                'match_score' => $bestMatch['match_score'],
            ]);
        }
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
     * 计算技能匹配结果
     *
     * @param array<string> $requiredSkills
     * @param array<WorkerSkill> $workerSkills
     * @return array{total_score: float, matched_count: int}
     */
    private function calculateSkillMatches(array $requiredSkills, array $workerSkills): array
    {
        $totalScore = 0.0;
        $matchedCount = 0;

        foreach ($requiredSkills as $requiredSkill) {
            $matchScore = $this->findBestSkillMatch($requiredSkill, $workerSkills);

            if ($matchScore > 0.0) {
                $totalScore += $matchScore;
                ++$matchedCount;
            }
        }

        return [
            'total_score' => $totalScore,
            'matched_count' => $matchedCount,
        ];
    }

    /**
     * 查找最佳技能匹配
     *
     * @param array<WorkerSkill> $workerSkills
     */
    private function findBestSkillMatch(string $requiredSkill, array $workerSkills): float
    {
        $bestMatch = 0.0;

        foreach ($workerSkills as $skill) {
            $matchScore = $this->calculateSingleSkillMatch($requiredSkill, $skill);

            if ($matchScore > $bestMatch) {
                $bestMatch = $matchScore;
            }
        }

        return $bestMatch;
    }

    /**
     * 计算单个技能匹配分数
     */
    private function calculateSingleSkillMatch(string $requiredSkill, WorkerSkill $skill): float
    {
        $skillCategory = $skill->getSkillCategory();

        if ($skillCategory !== $requiredSkill) {
            return 0.0;
        }

        $skillLevel = $skill->getSkillLevel();
        $skillScore = $skill->getSkillScore();
        $skillWeight = $this->extractFloatValue($this->skillWeights, $requiredSkill, 1.0);

        $levelScore = $skillLevel / 10.0;
        $scoreMultiplier = $skillScore / 100.0;

        return $skillWeight * $levelScore * $scoreMultiplier;
    }

    /**
     * 计算最终技能分数
     *
     * @param array{total_score: float, matched_count: int} $skillMatches
     * @param array<string> $requiredSkills
     */
    private function computeFinalSkillScore(array $skillMatches, array $requiredSkills): float
    {
        $requiredCount = count($requiredSkills);
        $averageScore = $skillMatches['total_score'] / max(1, $requiredCount);
        $coverageRatio = $skillMatches['matched_count'] / max(1, $requiredCount);

        return $averageScore * $coverageRatio;
    }

    /**
     * 寻找最佳作业员匹配
     *
     * @param array<WorkerSkill> $candidateWorkers
     * @param array<string> $requiredSkills
     * @return array<string, mixed>|null
     */
    private function findBestWorkerMatch(
        array $candidateWorkers,
        WarehouseTask $task,
        array $requiredSkills,
        float $skillWeight,
        float $workloadWeight,
        float $locationWeight,
        float $performanceWeight,
    ): ?array {
        $bestMatch = null;
        $bestScore = 0.0;

        foreach ($candidateWorkers as $workerSkill) {
            $workerId = $workerSkill->getWorkerId();

            $skillScore = $this->calculateTaskSkillMatch($task, $workerSkill);
            $workloadScore = $this->calculateWorkloadScore($workerId);
            $locationScore = $this->calculateLocationScore($task, $workerId);
            $performanceScore = $this->calculatePerformanceScore($workerId, $task->getType());

            $totalScore = ($skillScore * $skillWeight) +
                         ($workloadScore * $workloadWeight) +
                         ($locationScore * $locationWeight) +
                         ($performanceScore * $performanceWeight);

            $this->logger->debug('作业员匹配得分计算', [
                'worker_id' => $workerId,
                'skill_score' => round($skillScore, 3),
                'workload_score' => round($workloadScore, 3),
                'location_score' => round($locationScore, 3),
                'performance_score' => round($performanceScore, 3),
                'total_score' => round($totalScore, 3),
            ]);

            if ($totalScore > $bestScore) {
                $bestScore = $totalScore;
                $bestMatch = [
                    'worker_id' => $workerId,
                    'worker_name' => $workerSkill->getWorkerName(),
                    'match_score' => round($totalScore, 3),
                    'assignment_reason' => $this->generateAssignmentReason($skillScore, $workloadScore, $locationScore, $performanceScore),
                    'estimated_completion' => $this->estimateCompletionTime($task, $workerSkill),
                ];
            }
        }

        return $bestMatch;
    }

    private function calculateTaskSkillMatch(WarehouseTask $task, WorkerSkill $workerSkill): float
    {
        $requiredSkills = $this->extractRequiredSkills($task);
        $workerCategory = $workerSkill->getSkillCategory();

        if (in_array($workerCategory, $requiredSkills, true)) {
            return $workerSkill->getSkillScore() / 100;
        }

        return 0.3; // 基础匹配分数
    }

    private function calculateWorkloadScore(int $workerId): float
    {
        $currentLoad = $this->getCurrentWorkerWorkload($workerId);
        $maxLoad = $this->assignmentConfig['max_tasks_per_worker'];
        $maxLoadValue = is_int($maxLoad) ? $maxLoad : 10;

        return max(0, 1 - ($currentLoad / $maxLoadValue));
    }

    private function calculateLocationScore(WarehouseTask $task, int $workerId): float
    {
        // 简化实现，后续可以基于实际位置计算
        return 0.8;
    }

    private function calculatePerformanceScore(int $workerId, mixed $taskType): float
    {
        // 简化实现，后续可以基于历史绩效数据
        return 0.7;
    }

    private function getCurrentWorkerWorkload(int $workerId): int
    {
        // 这里应该有实际的数据库查询，暂时返回简化值
        return 2;
    }

    private function generateAssignmentReason(float $skillScore, float $workloadScore, float $locationScore, float $performanceScore): string
    {
        $maxScore = max($skillScore, $workloadScore, $locationScore, $performanceScore);

        if ($maxScore === $skillScore) {
            return '技能匹配度最高';
        }
        if ($maxScore === $workloadScore) {
            return '工作负载最适合';
        }
        if ($maxScore === $locationScore) {
            return '位置距离最近';
        }

        return '历史绩效最佳';
    }

    /**
     * @return array<string, mixed>
     */
    private function estimateCompletionTime(WarehouseTask $task, WorkerSkill $workerSkill): array
    {
        $baseTime = 60;
        $skillMultiplier = $workerSkill->getSkillLevel() / 10;

        $estimatedMinutes = (int) round($baseTime / max(0.5, $skillMultiplier));

        return [
            'estimated_minutes' => $estimatedMinutes,
            'completion_time' => (new \DateTimeImmutable())->modify("+{$estimatedMinutes} minutes"),
            'confidence_level' => 0.75,
        ];
    }
}
