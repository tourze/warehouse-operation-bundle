<?php

declare(strict_types=1);

namespace Tourze\WarehouseOperationBundle\Tests\Service\Scheduling;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\WarehouseOperationBundle\Service\Scheduling\SchedulingOptimizer;

/**
 * SchedulingOptimizer 单元测试
 *
 * 测试调度优化服务的功能，包括效率分析、优化建议生成、资源利用率分析等核心逻辑。
 * @internal
 */
#[CoversClass(SchedulingOptimizer::class)]
class SchedulingOptimizerTest extends TestCase
{
    private SchedulingOptimizer $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new SchedulingOptimizer();
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Scheduling\SchedulingOptimizer::analyzeOptimization
     */
    public function testAnalyzeOptimizationWithDefaultCriteria(): void
    {
        $result = $this->service->analyzeOptimization();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('efficiency_score', $result);
        $this->assertArrayHasKey('optimization_suggestions', $result);
        $this->assertArrayHasKey('resource_utilization', $result);
        $this->assertArrayHasKey('performance_trends', $result);
        $this->assertArrayHasKey('analysis_period', $result);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Scheduling\SchedulingOptimizer::analyzeOptimization
     */
    public function testAnalyzeOptimizationWithCustomTimePeriod(): void
    {
        $criteria = [
            'time_range' => ['hours' => 48],
        ];

        $result = $this->service->analyzeOptimization($criteria);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('analysis_period', $result);

        $analysisPeriod = $result['analysis_period'];
        self::assertIsArray($analysisPeriod);
        /** @var array<string, mixed> $analysisPeriod */
        $this->assertArrayHasKey('start', $analysisPeriod);
        $this->assertArrayHasKey('end', $analysisPeriod);
        $this->assertArrayHasKey('criteria', $analysisPeriod);

        $this->assertInstanceOf(\DateTimeImmutable::class, $analysisPeriod['start']);
        $this->assertInstanceOf(\DateTimeImmutable::class, $analysisPeriod['end']);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Scheduling\SchedulingOptimizer::analyzeOptimization
     */
    public function testAnalyzeOptimizationWithTaskTypesFilter(): void
    {
        $criteria = [
            'task_types' => ['picking', 'packing'],
        ];

        $result = $this->service->analyzeOptimization($criteria);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('analysis_period', $result);
        $analysisPeriod = $result['analysis_period'];
        self::assertIsArray($analysisPeriod);
        /** @var array<string, mixed> $analysisPeriod */
        $this->assertEquals($criteria, $analysisPeriod['criteria']);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Scheduling\SchedulingOptimizer::analyzeOptimization
     */
    public function testAnalyzeOptimizationWithZonesFilter(): void
    {
        $criteria = [
            'zones' => ['zone_a', 'zone_b'],
        ];

        $result = $this->service->analyzeOptimization($criteria);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('analysis_period', $result);
        $analysisPeriod = $result['analysis_period'];
        self::assertIsArray($analysisPeriod);
        /** @var array<string, mixed> $analysisPeriod */
        $this->assertEquals($criteria, $analysisPeriod['criteria']);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Scheduling\SchedulingOptimizer::analyzeOptimization
     */
    public function testAnalyzeOptimizationEfficiencyScoreStructure(): void
    {
        $result = $this->service->analyzeOptimization();

        $this->assertArrayHasKey('efficiency_score', $result);
        $efficiencyScore = $result['efficiency_score'];

        $this->assertIsArray($efficiencyScore);
        $this->assertArrayHasKey('overall_score', $efficiencyScore);
        $this->assertArrayHasKey('completion_rate', $efficiencyScore);
        $this->assertArrayHasKey('time_efficiency', $efficiencyScore);
        $this->assertArrayHasKey('worker_utilization_score', $efficiencyScore);

        $this->assertIsFloat($efficiencyScore['overall_score']);
        $this->assertIsFloat($efficiencyScore['completion_rate']);
        $this->assertIsFloat($efficiencyScore['time_efficiency']);
        $this->assertIsFloat($efficiencyScore['worker_utilization_score']);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Scheduling\SchedulingOptimizer::analyzeOptimization
     */
    public function testAnalyzeOptimizationEfficiencyScoreCalculation(): void
    {
        $result = $this->service->analyzeOptimization();

        $this->assertArrayHasKey('efficiency_score', $result);
        $efficiencyScore = $result['efficiency_score'];
        self::assertIsArray($efficiencyScore);
        /** @var array<string, mixed> $efficiencyScore */

        // 验证分数范围
        $this->assertGreaterThanOrEqual(0, $efficiencyScore['overall_score']);
        $this->assertLessThanOrEqual(1, $efficiencyScore['overall_score']);

        $this->assertGreaterThanOrEqual(0, $efficiencyScore['completion_rate']);
        $this->assertLessThanOrEqual(1, $efficiencyScore['completion_rate']);

        $this->assertGreaterThanOrEqual(0, $efficiencyScore['time_efficiency']);
        $this->assertLessThanOrEqual(1, $efficiencyScore['time_efficiency']);

        $this->assertGreaterThanOrEqual(0, $efficiencyScore['worker_utilization_score']);
        $this->assertLessThanOrEqual(1, $efficiencyScore['worker_utilization_score']);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Scheduling\SchedulingOptimizer::analyzeOptimization
     */
    public function testAnalyzeOptimizationSuggestionsStructure(): void
    {
        $result = $this->service->analyzeOptimization();

        $this->assertArrayHasKey('optimization_suggestions', $result);
        $suggestions = $result['optimization_suggestions'];

        $this->assertIsArray($suggestions);

        if (count($suggestions) > 0) {
            self::assertIsArray($suggestions);
            /** @var array<int, array<string, mixed>> $suggestions */
            $suggestion = $suggestions[0];
            self::assertIsArray($suggestion);
            /** @var array<string, mixed> $suggestion */
            $this->assertArrayHasKey('type', $suggestion);
            $this->assertArrayHasKey('description', $suggestion);
            $this->assertArrayHasKey('priority', $suggestion);
            $this->assertArrayHasKey('estimated_impact', $suggestion);
        }
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Scheduling\SchedulingOptimizer::analyzeOptimization
     */
    public function testAnalyzeOptimizationResourceUtilizationStructure(): void
    {
        $result = $this->service->analyzeOptimization();

        $this->assertArrayHasKey('resource_utilization', $result);
        $resourceUtilization = $result['resource_utilization'];

        $this->assertIsArray($resourceUtilization);
        $this->assertArrayHasKey('workers', $resourceUtilization);
        $this->assertArrayHasKey('equipment', $resourceUtilization);
        $this->assertArrayHasKey('zones', $resourceUtilization);

        // 验证workers结构
        $workers = $resourceUtilization['workers'];
        $this->assertArrayHasKey('utilization_rate', $workers);
        $this->assertArrayHasKey('efficiency_score', $workers);
        $this->assertArrayHasKey('recommended_count', $workers);
        $this->assertArrayHasKey('current_count', $workers);

        // 验证equipment结构
        $equipment = $resourceUtilization['equipment'];
        $this->assertArrayHasKey('utilization_rate', $equipment);
        $this->assertArrayHasKey('bottleneck_equipment', $equipment);

        // 验证zones结构
        $zones = $resourceUtilization['zones'];
        $this->assertIsArray($zones);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Scheduling\SchedulingOptimizer::analyzeOptimization
     */
    public function testAnalyzeOptimizationPerformanceTrendsStructure(): void
    {
        $result = $this->service->analyzeOptimization();

        $this->assertArrayHasKey('performance_trends', $result);
        $performanceTrends = $result['performance_trends'];

        $this->assertIsArray($performanceTrends);
        $this->assertArrayHasKey('efficiency_trend', $performanceTrends);
        $this->assertArrayHasKey('completion_time_trend', $performanceTrends);
        $this->assertArrayHasKey('error_rate_trend', $performanceTrends);
        $this->assertArrayHasKey('weekly_comparison', $performanceTrends);

        // 验证weekly_comparison结构
        $weeklyComparison = $performanceTrends['weekly_comparison'];
        $this->assertArrayHasKey('this_week', $weeklyComparison);
        $this->assertArrayHasKey('last_week', $weeklyComparison);
        $this->assertArrayHasKey('change_pct', $weeklyComparison);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Scheduling\SchedulingOptimizer::analyzeOptimization
     */
    public function testAnalyzeOptimizationHandlesInvalidCriteria(): void
    {
        $criteria = [
            'time_range' => 'invalid',
            'task_types' => 'not_an_array',
            'zones' => null,
        ];

        $result = $this->service->analyzeOptimization($criteria);

        // 应该能处理无效数据，不会抛出异常
        $this->assertIsArray($result);
        $this->assertArrayHasKey('efficiency_score', $result);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Scheduling\SchedulingOptimizer::analyzeOptimization
     */
    public function testAnalyzeOptimizationWithStringTimeRange(): void
    {
        $criteria = [
            'time_range' => ['hours' => '72'],
        ];

        $result = $this->service->analyzeOptimization($criteria);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('analysis_period', $result);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Scheduling\SchedulingOptimizer::analyzeOptimization
     */
    public function testAnalyzeOptimizationGeneratesCorrectSuggestionPriorities(): void
    {
        $result = $this->service->analyzeOptimization();

        $this->assertArrayHasKey('optimization_suggestions', $result);
        $suggestions = $result['optimization_suggestions'];

        // 至少要验证返回的是数组
        $this->assertIsArray($suggestions);

        if (count($suggestions) > 0) {
            self::assertIsIterable($suggestions);
            foreach ($suggestions as $suggestion) {
                self::assertIsArray($suggestion);
                /** @var array<string, mixed> $suggestion */
                $this->assertContains($suggestion['priority'], ['high', 'medium', 'low']);
            }
        } else {
            // 如果没有建议，也应该是有效的空数组
            $this->assertEmpty($suggestions);
        }
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Scheduling\SchedulingOptimizer::analyzeOptimization
     */
    public function testAnalyzeOptimizationZoneUtilizationData(): void
    {
        $result = $this->service->analyzeOptimization();

        $this->assertArrayHasKey('resource_utilization', $result);
        $resourceUtilization = $result['resource_utilization'];
        self::assertIsArray($resourceUtilization);
        /** @var array<string, mixed> $resourceUtilization */
        $this->assertArrayHasKey('zones', $resourceUtilization);
        $zones = $resourceUtilization['zones'];
        self::assertIsIterable($zones);

        foreach ($zones as $zoneName => $zoneData) {
            $this->assertIsString($zoneName);
            self::assertIsArray($zoneData);
            /** @var array<string, mixed> $zoneData */
            $this->assertArrayHasKey('utilization', $zoneData);
            $this->assertArrayHasKey('tasks_per_hour', $zoneData);
            $this->assertIsFloat($zoneData['utilization']);
            $this->assertIsInt($zoneData['tasks_per_hour']);
        }
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Scheduling\SchedulingOptimizer::analyzeOptimization
     */
    public function testAnalyzeOptimizationPerformanceTrendDirections(): void
    {
        $result = $this->service->analyzeOptimization();

        $this->assertArrayHasKey('performance_trends', $result);
        $performanceTrends = $result['performance_trends'];
        self::assertIsArray($performanceTrends);
        /** @var array<string, mixed> $performanceTrends */

        $validTrends = ['improving', 'stable', 'declining', 'decreasing'];
        $this->assertContains($performanceTrends['efficiency_trend'], $validTrends);
        $this->assertContains($performanceTrends['completion_time_trend'], $validTrends);
        $this->assertContains($performanceTrends['error_rate_trend'], $validTrends);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Scheduling\SchedulingOptimizer::analyzeOptimization
     */
    public function testAnalyzeOptimizationWeeklyComparisonValues(): void
    {
        $result = $this->service->analyzeOptimization();

        $this->assertArrayHasKey('performance_trends', $result);
        $performanceTrends = $result['performance_trends'];
        self::assertIsArray($performanceTrends);
        /** @var array<string, mixed> $performanceTrends */
        $this->assertArrayHasKey('weekly_comparison', $performanceTrends);
        $weeklyComparison = $performanceTrends['weekly_comparison'];
        self::assertIsArray($weeklyComparison);
        /** @var array<string, mixed> $weeklyComparison */

        $this->assertArrayHasKey('this_week', $weeklyComparison);
        $thisWeek = $weeklyComparison['this_week'];
        $this->assertIsArray($thisWeek);
        $this->assertArrayHasKey('last_week', $weeklyComparison);
        $lastWeek = $weeklyComparison['last_week'];
        $this->assertIsArray($lastWeek);
        $this->assertIsString($weeklyComparison['change_pct']);

        $this->assertArrayHasKey('efficiency', $thisWeek);
        $this->assertArrayHasKey('completion_time', $thisWeek);
        $this->assertArrayHasKey('efficiency', $lastWeek);
        $this->assertArrayHasKey('completion_time', $lastWeek);
    }

    public function testServiceConstructorAndBasicFunctionality(): void
    {
        // 验证服务可以正确实例化
        $this->assertInstanceOf(SchedulingOptimizer::class, $this->service);

        // 验证基本功能工作正常
        $result = $this->service->analyzeOptimization();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('efficiency_score', $result);
        $this->assertArrayHasKey('optimization_suggestions', $result);
        $this->assertArrayHasKey('resource_utilization', $result);
        $this->assertArrayHasKey('performance_trends', $result);
        $this->assertArrayHasKey('analysis_period', $result);
    }
}
