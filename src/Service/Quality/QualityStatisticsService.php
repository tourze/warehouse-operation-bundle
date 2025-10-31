<?php

declare(strict_types=1);

namespace Tourze\WarehouseOperationBundle\Service\Quality;

use Tourze\WarehouseOperationBundle\Repository\WarehouseTaskRepository;

/**
 * 质检统计服务
 */
final class QualityStatisticsService
{
    private WarehouseTaskRepository $qualityTaskRepository;

    public function __construct(WarehouseTaskRepository $qualityTaskRepository)
    {
        $this->qualityTaskRepository = $qualityTaskRepository;
    }

    /**
     * 质检数据统计分析
     *
     * @param array<string, mixed> $analysisParams
     * @return array<string, mixed>
     */
    public function analyzeQualityStatistics(array $analysisParams = []): array
    {
        $timeConfig = $this->extractTimeConfig($analysisParams);
        $timePeriodRaw = $timeConfig['time_period'] ?? '30days';
        $timePeriod = is_string($timePeriodRaw) ? $timePeriodRaw : '30days';
        $tasks = $this->findTasksByTimePeriod($timePeriod);

        if (0 === count($tasks)) {
            return $this->createEmptyStatisticsResult();
        }

        return $this->buildComprehensiveStatistics($tasks);
    }

    /**
     * 提取时间配置
     *
     * @param array<string, mixed> $analysisParams
     * @return array<string, mixed>
     */
    private function extractTimeConfig(array $analysisParams): array
    {
        $timePeriodRaw = $analysisParams['time_period'] ?? '30days';
        $timePeriod = is_string($timePeriodRaw) ? $timePeriodRaw : '30days';

        return ['time_period' => $timePeriod];
    }

    /**
     * 根据时间段查找任务
     *
     * @return array<object>
     */
    private function findTasksByTimePeriod(string $timePeriod): array
    {
        $startDate = new \DateTimeImmutable("-{$timePeriod}");

        $qb = $this->qualityTaskRepository->createQueryBuilder('qt')
            ->where('qt.completedAt >= :start_date')
            ->setParameter('start_date', $startDate)
        ;

        $tasksRaw = $qb->getQuery()->getResult();

        return array_filter((array) $tasksRaw, fn ($task): bool => is_object($task));
    }

    /**
     * 创建空统计结果
     *
     * @return array<string, mixed>
     */
    private function createEmptyStatisticsResult(): array
    {
        return [
            'overall_pass_rate' => 0,
            'trend_analysis' => [],
            'failure_patterns' => [],
            'supplier_ranking' => [],
            'improvement_opportunities' => [],
            'cost_analysis' => ['total_cost' => 0],
        ];
    }

    /**
     * 构建综合统计数据
     *
     * @param array<object> $tasks
     * @return array<string, mixed>
     */
    private function buildComprehensiveStatistics(array $tasks): array
    {
        return [
            'overall_pass_rate' => $this->calculateOverallPassRate($tasks),
            'trend_analysis' => $this->analyzeTrends($tasks),
            'failure_patterns' => $this->analyzeFailurePatterns($tasks),
            'supplier_ranking' => $this->calculateSupplierRanking($tasks),
            'improvement_opportunities' => $this->identifyImprovementOpportunities($tasks),
            'cost_analysis' => $this->analyzeCosts($tasks),
        ];
    }

    /**
     * 计算总体通过率
     *
     * @param array<object> $tasks
     */
    private function calculateOverallPassRate(array $tasks): float
    {
        if (0 === count($tasks)) {
            return 0.0;
        }

        $passCount = 0;
        foreach ($tasks as $task) {
            if (!method_exists($task, 'getData')) {
                continue;
            }

            $taskData = $task->getData();
            if (!is_array($taskData)) {
                continue;
            }

            $qualityResultRaw = $taskData['quality_result'] ?? [];
            $qualityResult = is_array($qualityResultRaw) ? $qualityResultRaw : [];
            $result = $qualityResult['overall_result'] ?? 'unknown';
            if ('pass' === $result) {
                ++$passCount;
            }
        }

        return round($passCount / count($tasks) * 100, 2);
    }

    /**
     * 分析趋势
     *
     * @param array<object> $tasks
     * @return array<string, mixed>
     */
    private function analyzeTrends(array $tasks): array
    {
        // 简化的趋势分析实现
        return [
            'trend_direction' => 'stable',
            'monthly_pass_rates' => [],
            'improvement_rate' => 0,
        ];
    }

    /**
     * 分析失败模式
     *
     * @param array<object> $tasks
     * @return array<string, mixed>
     */
    private function analyzeFailurePatterns(array $tasks): array
    {
        $patterns = [];

        foreach ($tasks as $task) {
            $defects = $this->extractDefectsFromTask($task);
            $patterns = $this->aggregateDefectPatterns($defects, $patterns);
        }

        arsort($patterns);

        return array_slice($patterns, 0, 10, true);
    }

    /**
     * 从任务中提取缺陷
     *
     * @return array<string, mixed>
     */
    private function extractDefectsFromTask(mixed $task): array
    {
        if (!is_object($task) || !method_exists($task, 'getData')) {
            return [];
        }

        $taskData = $task->getData();
        if (!is_array($taskData)) {
            return [];
        }

        $qualityResultRaw = $taskData['quality_result'] ?? [];
        $qualityResult = is_array($qualityResultRaw) ? $qualityResultRaw : [];
        $defectsRaw = $qualityResult['defects'] ?? [];

        /** @var array<string, mixed> */
        return is_array($defectsRaw) ? $defectsRaw : [];
    }

    /**
     * 聚合缺陷模式
     *
     * @param array<string, mixed> $defects
     * @param array<string, int> $patterns
     * @return array<string, int>
     */
    private function aggregateDefectPatterns(array $defects, array $patterns): array
    {
        foreach ($defects as $defect) {
            if (!is_array($defect)) {
                continue;
            }

            $typeRaw = $defect['type'] ?? 'unknown';
            $type = is_string($typeRaw) ? $typeRaw : 'unknown';
            if (!isset($patterns[$type])) {
                $patterns[$type] = 0;
            }
            ++$patterns[$type];
        }

        return $patterns;
    }

    /**
     * 计算供应商排名
     *
     * @param array<object> $tasks
     * @return array<string, mixed>
     */
    private function calculateSupplierRanking(array $tasks): array
    {
        // 简化实现，实际项目中会根据供应商ID进行统计
        return [];
    }

    /**
     * 识别改进机会
     *
     * @param array<object> $tasks
     * @return array<string, mixed>
     */
    private function identifyImprovementOpportunities(array $tasks): array
    {
        return [
            'high_priority' => ['加强入库前预检', '优化质检标准'],
            'medium_priority' => ['培训质检人员', '更新检测设备'],
            'low_priority' => ['完善记录流程'],
        ];
    }

    /**
     * 分析成本
     *
     * @param array<object> $tasks
     * @return array<string, mixed>
     */
    private function analyzeCosts(array $tasks): array
    {
        return [
            'total_cost' => 0,
            'average_cost_per_task' => 0,
            'cost_by_failure_type' => [],
        ];
    }
}
