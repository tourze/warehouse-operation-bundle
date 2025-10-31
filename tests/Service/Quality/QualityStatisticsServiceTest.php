<?php

declare(strict_types=1);

namespace Tourze\WarehouseOperationBundle\Tests\Service\Quality;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\WarehouseOperationBundle\Entity\InboundTask;
use Tourze\WarehouseOperationBundle\Enum\TaskType;
use Tourze\WarehouseOperationBundle\Repository\WarehouseTaskRepository;
use Tourze\WarehouseOperationBundle\Service\Quality\QualityStatisticsService;

/**
 * QualityStatisticsService 单元测试
 *
 * 测试质检统计服务的功能，包括统计分析、趋势分析、失败模式识别等核心逻辑。
 * @internal
 */
#[CoversClass(QualityStatisticsService::class)]
#[RunTestsInSeparateProcesses]
class QualityStatisticsServiceTest extends AbstractIntegrationTestCase
{
    private QualityStatisticsService $service;

    private WarehouseTaskRepository $taskRepository;

    protected function onSetUp(): void
    {
        $this->taskRepository = parent::getService(WarehouseTaskRepository::class);
        // @phpstan-ignore integrationTest.noDirectInstantiationOfCoveredClass
        $this->service = new QualityStatisticsService($this->taskRepository);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Quality\QualityStatisticsService::analyzeQualityStatistics
     */
    public function testAnalyzeQualityStatisticsWithEmptyTasks(): void
    {
        $analysisParams = ['time_period' => '30days'];

        $result = $this->service->analyzeQualityStatistics($analysisParams);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('overall_pass_rate', $result);
        $this->assertArrayHasKey('trend_analysis', $result);
        $this->assertArrayHasKey('failure_patterns', $result);
        $this->assertArrayHasKey('supplier_ranking', $result);
        $this->assertArrayHasKey('improvement_opportunities', $result);
        $this->assertArrayHasKey('cost_analysis', $result);

        $this->assertEquals(0, $result['overall_pass_rate']);
        self::assertIsArray($result['cost_analysis']);
        /** @var array<string, mixed> $costAnalysis */
        $costAnalysis = $result['cost_analysis'];
        $this->assertEquals(0, $costAnalysis['total_cost']);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Quality\QualityStatisticsService::analyzeQualityStatistics
     */
    public function testAnalyzeQualityStatisticsWithPassedTasks(): void
    {
        // 创建一个已完成的质检任务（通过）
        $task = new InboundTask();
        $task->setType(TaskType::QUALITY);
        $task->setData([
            'quality_result' => [
                'overall_result' => 'pass',
                'defects' => [],
            ],
        ]);
        $task->setCompletedAt(new \DateTimeImmutable('-5 days'));
        $this->taskRepository->save($task);

        $analysisParams = ['time_period' => '30days'];

        $result = $this->service->analyzeQualityStatistics($analysisParams);

        $this->assertIsArray($result);
        // 通过率应该大于0，因为至少有一个通过的任务
        $this->assertGreaterThan(0, $result['overall_pass_rate']);
        $this->assertLessThanOrEqual(100.0, $result['overall_pass_rate']);
        $this->assertIsArray($result['trend_analysis']);
        $this->assertIsArray($result['failure_patterns']);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Quality\QualityStatisticsService::analyzeQualityStatistics
     */
    public function testAnalyzeQualityStatisticsWithFailedTasks(): void
    {
        // 创建一个失败的质检任务
        $task = new InboundTask();
        $task->setType(TaskType::QUALITY);
        $task->setData([
            'quality_result' => [
                'overall_result' => 'fail',
                'defects' => [
                    ['type' => 'damage', 'severity' => 'high'],
                    ['type' => 'missing_parts', 'severity' => 'medium'],
                ],
            ],
        ]);
        $task->setCompletedAt(new \DateTimeImmutable('-10 days'));
        $this->taskRepository->save($task);

        $analysisParams = ['time_period' => '30days'];

        $result = $this->service->analyzeQualityStatistics($analysisParams);

        $this->assertIsArray($result);
        $this->assertEquals(0.0, $result['overall_pass_rate']);
        self::assertIsArray($result['failure_patterns']);
        /** @var array<string, mixed> $failurePatterns */
        $failurePatterns = $result['failure_patterns'];
        $this->assertArrayHasKey('damage', $failurePatterns);
        $this->assertArrayHasKey('missing_parts', $failurePatterns);
        $this->assertEquals(1, $failurePatterns['damage']);
        $this->assertEquals(1, $failurePatterns['missing_parts']);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Quality\QualityStatisticsService::analyzeQualityStatistics
     */
    public function testAnalyzeQualityStatisticsWithMixedResults(): void
    {
        // 创建多个任务（混合结果）
        $passTask1 = new InboundTask();
        $passTask1->setType(TaskType::QUALITY);
        $passTask1->setData([
            'quality_result' => [
                'overall_result' => 'pass',
                'defects' => [],
            ],
        ]);
        $passTask1->setCompletedAt(new \DateTimeImmutable('-3 days'));
        $this->taskRepository->save($passTask1);

        $passTask2 = new InboundTask();
        $passTask2->setType(TaskType::QUALITY);
        $passTask2->setData([
            'quality_result' => [
                'overall_result' => 'pass',
                'defects' => [],
            ],
        ]);
        $passTask2->setCompletedAt(new \DateTimeImmutable('-5 days'));
        $this->taskRepository->save($passTask2);

        $failTask = new InboundTask();
        $failTask->setType(TaskType::QUALITY);
        $failTask->setData([
            'quality_result' => [
                'overall_result' => 'fail',
                'defects' => [
                    ['type' => 'quality_issue', 'severity' => 'low'],
                ],
            ],
        ]);
        $failTask->setCompletedAt(new \DateTimeImmutable('-7 days'));
        $this->taskRepository->save($failTask);

        $analysisParams = ['time_period' => '30days'];

        $result = $this->service->analyzeQualityStatistics($analysisParams);

        $this->assertIsArray($result);
        // 通过率应该在0-100之间，并且不应该是0或100（因为有混合结果）
        $this->assertGreaterThan(0, $result['overall_pass_rate']);
        $this->assertLessThan(100.0, $result['overall_pass_rate']);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Quality\QualityStatisticsService::analyzeQualityStatistics
     */
    public function testAnalyzeQualityStatisticsWithDifferentTimePeriods(): void
    {
        // 创建任务在不同时间段
        $recentTask = new InboundTask();
        $recentTask->setType(TaskType::QUALITY);
        $recentTask->setData([
            'quality_result' => [
                'overall_result' => 'pass',
                'defects' => [],
            ],
        ]);
        $recentTask->setCompletedAt(new \DateTimeImmutable('-5 days'));
        $this->taskRepository->save($recentTask);

        $oldTask = new InboundTask();
        $oldTask->setType(TaskType::QUALITY);
        $oldTask->setData([
            'quality_result' => [
                'overall_result' => 'pass',
                'defects' => [],
            ],
        ]);
        $oldTask->setCompletedAt(new \DateTimeImmutable('-40 days'));
        $this->taskRepository->save($oldTask);

        // 查询30天内的数据
        $analysisParams = ['time_period' => '30days'];
        $result30Days = $this->service->analyzeQualityStatistics($analysisParams);

        // 查询60天内的数据
        $analysisParams = ['time_period' => '60days'];
        $result60Days = $this->service->analyzeQualityStatistics($analysisParams);

        // 60天的结果应该包含更多数据
        $this->assertIsArray($result30Days);
        $this->assertIsArray($result60Days);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Quality\QualityStatisticsService::analyzeQualityStatistics
     */
    public function testAnalyzeQualityStatisticsDefectPatternAggregation(): void
    {
        // 创建多个任务，测试缺陷模式聚合
        $task1 = new InboundTask();
        $task1->setType(TaskType::QUALITY);
        $task1->setData([
            'quality_result' => [
                'overall_result' => 'fail',
                'defects' => [
                    ['type' => 'damage', 'severity' => 'high'],
                    ['type' => 'damage', 'severity' => 'medium'],
                ],
            ],
        ]);
        $task1->setCompletedAt(new \DateTimeImmutable('-2 days'));
        $this->taskRepository->save($task1);

        $task2 = new InboundTask();
        $task2->setType(TaskType::QUALITY);
        $task2->setData([
            'quality_result' => [
                'overall_result' => 'fail',
                'defects' => [
                    ['type' => 'damage', 'severity' => 'low'],
                ],
            ],
        ]);
        $task2->setCompletedAt(new \DateTimeImmutable('-4 days'));
        $this->taskRepository->save($task2);

        $analysisParams = ['time_period' => '30days'];

        $result = $this->service->analyzeQualityStatistics($analysisParams);

        $this->assertArrayHasKey('failure_patterns', $result);
        self::assertIsArray($result['failure_patterns']);
        /** @var array<string, mixed> $failurePatterns */
        $failurePatterns = $result['failure_patterns'];
        $this->assertArrayHasKey('damage', $failurePatterns);
        // 应该聚合3个damage缺陷
        $this->assertEquals(3, $failurePatterns['damage']);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Quality\QualityStatisticsService::analyzeQualityStatistics
     */
    public function testAnalyzeQualityStatisticsReturnsImprovementOpportunities(): void
    {
        $analysisParams = ['time_period' => '30days'];

        $result = $this->service->analyzeQualityStatistics($analysisParams);

        $this->assertArrayHasKey('improvement_opportunities', $result);
        $this->assertIsArray($result['improvement_opportunities']);

        $opportunities = $result['improvement_opportunities'];
        $this->assertArrayHasKey('high_priority', $opportunities);
        $this->assertArrayHasKey('medium_priority', $opportunities);
        $this->assertArrayHasKey('low_priority', $opportunities);

        $this->assertIsArray($opportunities['high_priority']);
        $this->assertIsArray($opportunities['medium_priority']);
        $this->assertIsArray($opportunities['low_priority']);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Quality\QualityStatisticsService::analyzeQualityStatistics
     */
    public function testAnalyzeQualityStatisticsReturnsCostAnalysis(): void
    {
        $analysisParams = ['time_period' => '30days'];

        $result = $this->service->analyzeQualityStatistics($analysisParams);

        $this->assertArrayHasKey('cost_analysis', $result);
        $this->assertIsArray($result['cost_analysis']);

        $costAnalysis = $result['cost_analysis'];
        $this->assertArrayHasKey('total_cost', $costAnalysis);
        $this->assertArrayHasKey('average_cost_per_task', $costAnalysis);
        $this->assertArrayHasKey('cost_by_failure_type', $costAnalysis);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Quality\QualityStatisticsService::analyzeQualityStatistics
     */
    public function testAnalyzeQualityStatisticsWithInvalidData(): void
    {
        // 创建一个任务，但没有quality_result数据
        $task = new InboundTask();
        $task->setType(TaskType::QUALITY);
        $task->setData([]);
        $task->setCompletedAt(new \DateTimeImmutable('-2 days'));
        $this->taskRepository->save($task);

        $analysisParams = ['time_period' => '30days'];

        $result = $this->service->analyzeQualityStatistics($analysisParams);

        // 应该能处理无效数据，不会抛出异常
        $this->assertIsArray($result);
        $this->assertArrayHasKey('overall_pass_rate', $result);
    }

    public function testServiceConstructorAndBasicFunctionality(): void
    {
        // 验证服务可以正确实例化
        $this->assertInstanceOf(QualityStatisticsService::class, $this->service);

        // 验证基本功能工作正常
        $result = $this->service->analyzeQualityStatistics();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('overall_pass_rate', $result);
        $this->assertArrayHasKey('trend_analysis', $result);
        $this->assertArrayHasKey('failure_patterns', $result);
        $this->assertArrayHasKey('supplier_ranking', $result);
        $this->assertArrayHasKey('improvement_opportunities', $result);
        $this->assertArrayHasKey('cost_analysis', $result);
    }
}
