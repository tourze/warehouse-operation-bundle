<?php

declare(strict_types=1);

namespace Tourze\WarehouseOperationBundle\Tests\Service\Scheduling;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\WarehouseOperationBundle\Service\Scheduling\WorkerPerformanceAnalyzer;

/**
 * WorkerPerformanceAnalyzer 单元测试
 *
 * 测试作业员绩效分析服务的功能，包括绩效评分、历史绩效查询等核心逻辑。
 * @internal
 */
#[CoversClass(WorkerPerformanceAnalyzer::class)]
class WorkerPerformanceAnalyzerTest extends TestCase
{
    private WorkerPerformanceAnalyzer $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new WorkerPerformanceAnalyzer();
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Scheduling\WorkerPerformanceAnalyzer::analyzeWorkerPerformance
     */
    public function testAnalyzeWorkerPerformanceReturnsValidStructure(): void
    {
        $workerId = 123;

        $result = $this->service->analyzeWorkerPerformance($workerId);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('efficiency_score', $result);
        $this->assertArrayHasKey('quality_score', $result);
        $this->assertArrayHasKey('reliability_score', $result);
        $this->assertArrayHasKey('overall_performance', $result);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Scheduling\WorkerPerformanceAnalyzer::analyzeWorkerPerformance
     */
    public function testAnalyzeWorkerPerformanceScoresAreFloats(): void
    {
        $workerId = 456;

        $result = $this->service->analyzeWorkerPerformance($workerId);

        $this->assertIsFloat($result['efficiency_score']);
        $this->assertIsFloat($result['quality_score']);
        $this->assertIsFloat($result['reliability_score']);
        $this->assertIsFloat($result['overall_performance']);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Scheduling\WorkerPerformanceAnalyzer::analyzeWorkerPerformance
     */
    public function testAnalyzeWorkerPerformanceScoresInValidRange(): void
    {
        $workerId = 789;

        $result = $this->service->analyzeWorkerPerformance($workerId);

        // 所有分数应该在0到1之间
        $this->assertGreaterThanOrEqual(0.0, $result['efficiency_score']);
        $this->assertLessThanOrEqual(1.0, $result['efficiency_score']);

        $this->assertGreaterThanOrEqual(0.0, $result['quality_score']);
        $this->assertLessThanOrEqual(1.0, $result['quality_score']);

        $this->assertGreaterThanOrEqual(0.0, $result['reliability_score']);
        $this->assertLessThanOrEqual(1.0, $result['reliability_score']);

        $this->assertGreaterThanOrEqual(0.0, $result['overall_performance']);
        $this->assertLessThanOrEqual(1.0, $result['overall_performance']);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Scheduling\WorkerPerformanceAnalyzer::analyzeWorkerPerformance
     */
    public function testAnalyzeWorkerPerformanceWithDifferentWorkerIds(): void
    {
        $result1 = $this->service->analyzeWorkerPerformance(100);
        $result2 = $this->service->analyzeWorkerPerformance(200);

        // 两个作业员的结果结构应该一致
        $this->assertArrayHasKey('efficiency_score', $result1);
        $this->assertArrayHasKey('efficiency_score', $result2);

        $this->assertIsArray($result1);
        $this->assertIsArray($result2);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Scheduling\WorkerPerformanceAnalyzer::getWorkerHistoricalPerformance
     */
    public function testGetWorkerHistoricalPerformanceReturnsValidStructure(): void
    {
        $workerId = 123;
        $taskType = 'picking';

        $result = $this->service->getWorkerHistoricalPerformance($workerId, $taskType);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('completion_rate', $result);
        $this->assertArrayHasKey('average_time', $result);
        $this->assertArrayHasKey('quality_score', $result);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Scheduling\WorkerPerformanceAnalyzer::getWorkerHistoricalPerformance
     */
    public function testGetWorkerHistoricalPerformanceDataTypes(): void
    {
        $workerId = 456;
        $taskType = 'packing';

        $result = $this->service->getWorkerHistoricalPerformance($workerId, $taskType);

        $this->assertIsFloat($result['completion_rate']);
        $this->assertIsInt($result['average_time']);
        $this->assertIsFloat($result['quality_score']);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Scheduling\WorkerPerformanceAnalyzer::getWorkerHistoricalPerformance
     */
    public function testGetWorkerHistoricalPerformanceCompletionRateRange(): void
    {
        $workerId = 789;
        $taskType = 'quality';

        $result = $this->service->getWorkerHistoricalPerformance($workerId, $taskType);

        // 完成率应该在0到1之间
        $this->assertGreaterThanOrEqual(0.0, $result['completion_rate']);
        $this->assertLessThanOrEqual(1.0, $result['completion_rate']);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Scheduling\WorkerPerformanceAnalyzer::getWorkerHistoricalPerformance
     */
    public function testGetWorkerHistoricalPerformanceAverageTimePositive(): void
    {
        $workerId = 101;
        $taskType = 'counting';

        $result = $this->service->getWorkerHistoricalPerformance($workerId, $taskType);

        // 平均时间应该是正数
        $this->assertGreaterThan(0, $result['average_time']);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Scheduling\WorkerPerformanceAnalyzer::getWorkerHistoricalPerformance
     */
    public function testGetWorkerHistoricalPerformanceQualityScoreRange(): void
    {
        $workerId = 202;
        $taskType = 'transfer';

        $result = $this->service->getWorkerHistoricalPerformance($workerId, $taskType);

        // 质量分数应该在0到1之间
        $this->assertGreaterThanOrEqual(0.0, $result['quality_score']);
        $this->assertLessThanOrEqual(1.0, $result['quality_score']);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Scheduling\WorkerPerformanceAnalyzer::getWorkerHistoricalPerformance
     */
    public function testGetWorkerHistoricalPerformanceWithDifferentTaskTypes(): void
    {
        $workerId = 303;

        $result1 = $this->service->getWorkerHistoricalPerformance($workerId, 'picking');
        $result2 = $this->service->getWorkerHistoricalPerformance($workerId, 'packing');
        $result3 = $this->service->getWorkerHistoricalPerformance($workerId, 'quality');

        // 所有任务类型应该返回相同结构
        $this->assertArrayHasKey('completion_rate', $result1);
        $this->assertArrayHasKey('completion_rate', $result2);
        $this->assertArrayHasKey('completion_rate', $result3);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Scheduling\WorkerPerformanceAnalyzer::analyzeWorkerPerformance
     */
    public function testAnalyzeWorkerPerformanceConsistency(): void
    {
        $workerId = 404;

        // 多次调用应该返回一致的结果（因为是简化实现）
        $result1 = $this->service->analyzeWorkerPerformance($workerId);
        $result2 = $this->service->analyzeWorkerPerformance($workerId);

        $this->assertEquals($result1['efficiency_score'], $result2['efficiency_score']);
        $this->assertEquals($result1['quality_score'], $result2['quality_score']);
        $this->assertEquals($result1['reliability_score'], $result2['reliability_score']);
        $this->assertEquals($result1['overall_performance'], $result2['overall_performance']);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Scheduling\WorkerPerformanceAnalyzer::getWorkerHistoricalPerformance
     */
    public function testGetWorkerHistoricalPerformanceConsistency(): void
    {
        $workerId = 505;
        $taskType = 'picking';

        // 多次调用应该返回一致的结果
        $result1 = $this->service->getWorkerHistoricalPerformance($workerId, $taskType);
        $result2 = $this->service->getWorkerHistoricalPerformance($workerId, $taskType);

        $this->assertEquals($result1['completion_rate'], $result2['completion_rate']);
        $this->assertEquals($result1['average_time'], $result2['average_time']);
        $this->assertEquals($result1['quality_score'], $result2['quality_score']);
    }

    public function testServiceConstructorAndBasicFunctionality(): void
    {
        // 验证服务可以正确实例化
        $this->assertInstanceOf(WorkerPerformanceAnalyzer::class, $this->service);

        // 验证基本功能工作正常
        $performanceResult = $this->service->analyzeWorkerPerformance(100);
        $this->assertIsArray($performanceResult);

        $historicalResult = $this->service->getWorkerHistoricalPerformance(100, 'picking');
        $this->assertIsArray($historicalResult);
    }
}
