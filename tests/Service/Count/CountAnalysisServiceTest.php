<?php

declare(strict_types=1);

namespace Tourze\WarehouseOperationBundle\Tests\Service\Count;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\WarehouseOperationBundle\Entity\CountPlan;
use Tourze\WarehouseOperationBundle\Entity\CountTask;
use Tourze\WarehouseOperationBundle\Enum\TaskStatus;
use Tourze\WarehouseOperationBundle\Repository\CountPlanRepository;
use Tourze\WarehouseOperationBundle\Repository\CountTaskRepository;
use Tourze\WarehouseOperationBundle\Service\Count\CountAnalysisService;

/**
 * CountAnalysisService 单元测试
 *
 * 测试盘点分析服务的完整功能，包括进度分析、差异报告、结果分析等核心业务逻辑。
 * 验证服务的正确性、计算准确性和异常处理。
 * @internal
 */
#[CoversClass(CountAnalysisService::class)]
#[RunTestsInSeparateProcesses]
class CountAnalysisServiceTest extends AbstractIntegrationTestCase
{
    private CountAnalysisService $service;

    private CountPlanRepository $countPlanRepository;

    private CountTaskRepository $countTaskRepository;

    protected function onSetUp(): void
    {
        $this->service = parent::getService(CountAnalysisService::class);
        $this->countPlanRepository = parent::getService(CountPlanRepository::class);
        $this->countTaskRepository = parent::getService(CountTaskRepository::class);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Count\CountAnalysisService::getCountProgress
     */
    public function testGetCountProgressWithValidPlan(): void
    {
        // 创建测试计划
        $plan = new CountPlan();
        $plan->setName('测试盘点计划');
        $plan->setCountType('cycle');
        $plan->setStartDate(new \DateTimeImmutable('2025-09-01'));
        $plan->setEndDate(new \DateTimeImmutable('2025-09-03'));
        $plan->setStatus('in_progress');
        $plan->setIsActive(true);
        $this->countPlanRepository->save($plan);

        $planId = $plan->getId();
        $this->assertNotNull($planId);

        // 创建测试任务
        $tasks = [];
        for ($i = 0; $i < 10; ++$i) {
            $task = new CountTask();
            $task->setTaskName("任务_{$i}");
            $task->setTaskType('count');
            $task->setPriority(50);
            $task->setCountPlanId($planId);

            // 设置不同状态的任务
            if ($i < 6) {
                $task->setStatus(TaskStatus::COMPLETED);
                $task->setTaskData(['count_result' => ['accuracy' => 95.0 + $i]]);
            } elseif ($i < 8) {
                $task->setStatus(TaskStatus::DISCREPANCY_FOUND);
                $task->setTaskData(['count_result' => ['accuracy' => 85.0]]);
            } else {
                $task->setStatus(TaskStatus::PENDING);
            }

            $this->countTaskRepository->save($task, false);
            $tasks[] = $task;
        }

        parent::getEntityManager()->flush();

        // 执行测试
        $result = $this->service->getCountProgress($plan);

        // 验证结果
        $this->assertArrayHasKey('total_tasks', $result);
        $this->assertArrayHasKey('completed_tasks', $result);
        $this->assertArrayHasKey('pending_tasks', $result);
        $this->assertArrayHasKey('discrepancy_tasks', $result);
        $this->assertArrayHasKey('completion_percentage', $result);
        $this->assertArrayHasKey('estimated_completion', $result);
        $this->assertArrayHasKey('team_performance', $result);

        $this->assertIsInt($result['total_tasks']);
        $this->assertIsInt($result['completed_tasks']);
        $this->assertIsInt($result['pending_tasks']);
        $this->assertIsInt($result['discrepancy_tasks']);
        $this->assertIsFloat($result['completion_percentage']);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Count\CountAnalysisService::getCountProgress
     */
    public function testGetCountProgressWithPlanWithoutId(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('CountPlan must have an ID');

        $plan = new CountPlan();
        $this->service->getCountProgress($plan);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Count\CountAnalysisService::generateDiscrepancyReport
     */
    public function testGenerateDiscrepancyReportSuccess(): void
    {
        // 创建测试计划
        $plan = new CountPlan();
        $plan->setName('差异报告测试计划');
        $plan->setCountType('full');
        $plan->setStatus('completed');
        $this->countPlanRepository->save($plan);

        $planId = $plan->getId();
        $this->assertNotNull($planId);

        // 创建有差异的测试任务
        $task1 = new CountTask();
        $task1->setTaskName('差异任务1');
        $task1->setTaskType('count');
        $task1->setCountPlanId($planId);
        $task1->setStatus(TaskStatus::DISCREPANCY_FOUND);
        $task1->setTaskData([
            'count_result' => ['accuracy' => 85.0],
            'discrepancy_handling' => ['value_impact' => 100],
        ]);

        $task2 = new CountTask();
        $task2->setTaskName('正常任务');
        $task2->setTaskType('count');
        $task2->setCountPlanId($planId);
        $task2->setStatus(TaskStatus::COMPLETED);
        $task2->setTaskData(['count_result' => ['accuracy' => 98.5]]);

        $this->countTaskRepository->save($task1, false);
        $this->countTaskRepository->save($task2, false);
        parent::getEntityManager()->flush();

        // 执行测试
        $result = $this->service->generateDiscrepancyReport($plan);

        // 验证结果
        $this->assertArrayHasKey('report_id', $result);
        $this->assertArrayHasKey('file_url', $result);
        $this->assertArrayHasKey('summary_statistics', $result);
        $this->assertArrayHasKey('accuracy_analysis', $result);
        $this->assertArrayHasKey('cost_impact_analysis', $result);

        $reportId = $result['report_id'];
        $this->assertIsString($reportId);
        $this->assertStringContainsString('COUNT_REPORT_', $reportId);

        $fileUrl = $result['file_url'];
        $this->assertIsString($fileUrl);
        $this->assertStringContainsString('/reports/', $fileUrl);

        $this->assertIsArray($result['summary_statistics']);
        $this->assertIsArray($result['accuracy_analysis']);
        $this->assertIsArray($result['cost_impact_analysis']);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Count\CountAnalysisService::analyzeCountResults
     */
    public function testAnalyzeCountResultsWithMultiplePlans(): void
    {
        // 创建多个测试计划
        $plan1 = new CountPlan();
        $plan1->setName('分析计划1');
        $plan1->setCountType('cycle');
        $this->countPlanRepository->save($plan1, false);

        $plan2 = new CountPlan();
        $plan2->setName('分析计划2');
        $plan2->setCountType('abc');
        $this->countPlanRepository->save($plan2, false);

        parent::getEntityManager()->flush();

        $plan1Id = $plan1->getId();
        $plan2Id = $plan2->getId();
        $this->assertNotNull($plan1Id);
        $this->assertNotNull($plan2Id);

        $planIds = [$plan1Id, $plan2Id];

        // 为计划创建任务
        foreach ($planIds as $index => $planId) {
            $task = new CountTask();
            $task->setTaskName("分析任务_{$index}");
            $task->setTaskType('count');
            $task->setCountPlanId($planId);
            $task->setStatus(TaskStatus::COMPLETED);
            $task->setTaskData(['count_result' => ['accuracy' => 92.0 + $index]]);
            $this->countTaskRepository->save($task, false);
        }

        parent::getEntityManager()->flush();

        // 执行测试
        $result = $this->service->analyzeCountResults($planIds);

        // 验证结果
        $this->assertArrayHasKey('overall_accuracy', $result);
        $this->assertArrayHasKey('trend_analysis', $result);
        $this->assertArrayHasKey('problem_categories', $result);
        $this->assertArrayHasKey('location_accuracy_ranking', $result);
        $this->assertArrayHasKey('improvement_recommendations', $result);
        $this->assertArrayHasKey('cost_benefit_analysis', $result);

        $this->assertIsFloat($result['overall_accuracy']);
        $this->assertIsArray($result['trend_analysis']);
        $this->assertIsArray($result['problem_categories']);
        $this->assertIsArray($result['location_accuracy_ranking']);
        $this->assertIsArray($result['improvement_recommendations']);
        $this->assertIsArray($result['cost_benefit_analysis']);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Count\CountAnalysisService::analyzeCountResults
     */
    public function testAnalyzeCountResultsWithCustomParams(): void
    {
        $plan = new CountPlan();
        $plan->setName('自定义参数测试计划');
        $plan->setCountType('spot');
        $this->countPlanRepository->save($plan);

        $analysisParams = [
            'time_range' => 60,
            'accuracy_threshold' => 98.0,
        ];

        $planId = $plan->getId();
        $this->assertNotNull($planId);

        // 执行测试
        $result = $this->service->analyzeCountResults([$planId], $analysisParams);

        // 验证结果包含所有必需字段
        $this->assertArrayHasKey('overall_accuracy', $result);
        $this->assertArrayHasKey('trend_analysis', $result);
        $this->assertArrayHasKey('problem_categories', $result);
        $this->assertArrayHasKey('location_accuracy_ranking', $result);
        $this->assertArrayHasKey('improvement_recommendations', $result);
        $this->assertArrayHasKey('cost_benefit_analysis', $result);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Count\CountAnalysisService::optimizeCountFrequency
     */
    public function testOptimizeCountFrequencyWithCriteria(): void
    {
        $optimizationCriteria = [
            'warehouse_zones' => ['A1', 'B1', 'C1'],
            'product_categories' => ['electronics', 'clothing'],
            'historical_months' => 6,
        ];

        // 执行测试
        $result = $this->service->optimizeCountFrequency($optimizationCriteria);

        // 验证结果
        $this->assertArrayHasKey('zone_frequency_recommendations', $result);
        $this->assertArrayHasKey('category_frequency_recommendations', $result);
        $this->assertArrayHasKey('cost_optimization_potential', $result);
        $this->assertArrayHasKey('implementation_plan', $result);

        // 验证频率建议
        $zoneRecs = $result['zone_frequency_recommendations'];
        $this->assertIsArray($zoneRecs);
        $this->assertArrayHasKey('high_value_zone', $zoneRecs);
        $this->assertEquals('weekly', $zoneRecs['high_value_zone']);

        $categoryRecs = $result['category_frequency_recommendations'];
        $this->assertIsArray($categoryRecs);
        $this->assertArrayHasKey('electronics', $categoryRecs);
        $this->assertEquals('bi-weekly', $categoryRecs['electronics']);

        // 验证成本优化
        $costOptimization = $result['cost_optimization_potential'];
        $this->assertIsArray($costOptimization);
        $this->assertArrayHasKey('current_annual_cost', $costOptimization);
        $this->assertArrayHasKey('optimized_annual_cost', $costOptimization);
        $this->assertArrayHasKey('potential_savings', $costOptimization);
        $this->assertArrayHasKey('savings_percentage', $costOptimization);

        // 验证实施计划
        $implementationPlan = $result['implementation_plan'];
        $this->assertIsArray($implementationPlan);
        $this->assertArrayHasKey('phase1', $implementationPlan);
        $this->assertArrayHasKey('estimated_implementation_time', $implementationPlan);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Count\CountAnalysisService::optimizeCountFrequency
     */
    public function testOptimizeCountFrequencyWithDefaultCriteria(): void
    {
        // 使用默认条件
        $result = $this->service->optimizeCountFrequency([]);

        // 验证结果仍然包含所有必需字段
        $this->assertArrayHasKey('zone_frequency_recommendations', $result);
        $this->assertArrayHasKey('category_frequency_recommendations', $result);
        $this->assertArrayHasKey('cost_optimization_potential', $result);
        $this->assertArrayHasKey('implementation_plan', $result);
    }

    public function testServiceConstructorAndDependencies(): void
    {
        // 验证服务可以正确实例化
        $this->assertInstanceOf(CountAnalysisService::class, $this->service);

        // 验证依赖注入正常工作
        $this->assertInstanceOf(CountPlanRepository::class, $this->countPlanRepository);
        $this->assertInstanceOf(CountTaskRepository::class, $this->countTaskRepository);
    }
}
