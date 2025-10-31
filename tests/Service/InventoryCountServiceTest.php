<?php

namespace Tourze\WarehouseOperationBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\WarehouseOperationBundle\Entity\CountPlan;
use Tourze\WarehouseOperationBundle\Entity\CountTask;
use Tourze\WarehouseOperationBundle\Enum\TaskStatus;
use Tourze\WarehouseOperationBundle\Service\InventoryCountService;

/**
 * InventoryCountService 单元测试
 *
 * 测试盘点管理服务的完整功能，包括计划生成、任务执行、差异处理等核心业务逻辑。
 * 验证服务的正确性、边界条件和异常处理。
 * @internal
 */
#[CoversClass(InventoryCountService::class)]
#[RunTestsInSeparateProcesses]
class InventoryCountServiceTest extends AbstractIntegrationTestCase
{
    private InventoryCountService $service;

    protected function onSetUp(): void
    {
        // 使用真实服务进行集成测试
        $this->service = parent::getService(InventoryCountService::class);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\InventoryCountService::generateCountPlan
     */
    public function testGenerateCountPlanWithFullCount(): void
    {
        // 准备数据
        $countType = 'full';
        $criteria = [
            'warehouse_zones' => ['A1', 'A2', 'B1'],
            'product_categories' => ['electronics', 'clothing'],
        ];
        $planOptions = [
            'schedule_date' => '2025-09-05',
            'duration_days' => 7,
        ];

        // 执行测试
        $result = $this->service->generateCountPlan($countType, $criteria, $planOptions);

        // 验证结果
        $this->assertInstanceOf(CountPlan::class, $result);
        $this->assertEquals('full', $result->getCountType());
        // 验证计划名称包含当前日期
        $expectedName = '全盘点计划_' . (new \DateTimeImmutable())->format('Y-m-d');
        $this->assertEquals($expectedName, $result->getName());
        // full类型的默认优先级为90
        $this->assertEquals(90, $result->getPriority());
        $startDate = $result->getStartDate();
        $endDate = $result->getEndDate();
        $this->assertNotNull($startDate);
        $this->assertNotNull($endDate);
        $this->assertEquals('2025-09-05', $startDate->format('Y-m-d'));
        $this->assertEquals('2025-09-12', $endDate->format('Y-m-d'));
        $this->assertEquals('draft', $result->getStatus());
        $this->assertTrue($result->isActive());

        // 验证范围配置
        $scope = $result->getScope();
        $this->assertEquals('full', $scope['count_type']);
        $this->assertEquals(['A1', 'A2', 'B1'], $scope['warehouse_zones']);
        $this->assertEquals(['electronics', 'clothing'], $scope['product_categories']);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\InventoryCountService::generateCountPlan
     */
    public function testGenerateCountPlanWithDefaultOptions(): void
    {
        $countType = 'cycle';
        $criteria = [];
        $planOptions = [];

        $result = $this->service->generateCountPlan($countType, $criteria, $planOptions);

        // 验证默认值
        $this->assertEquals('cycle', $result->getCountType());
        $this->assertEquals(60, $result->getPriority()); // cycle默认优先级
        // 验证开始日期是明天
        $expectedStartDate = (new \DateTime('tomorrow'))->format('Y-m-d');
        $startDate = $result->getStartDate();
        $endDate = $result->getEndDate();
        $this->assertNotNull($startDate);
        $this->assertNotNull($endDate);
        $this->assertEquals($expectedStartDate, $startDate->format('Y-m-d')); // +1天

        // 验证结束日期是开始日期后2天（cycle默认2天）
        $expectedEndDate = (new \DateTime('tomorrow +2 days'))->format('Y-m-d');
        $this->assertEquals($expectedEndDate, $endDate->format('Y-m-d')); // cycle默认2天
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\InventoryCountService::executeCountTask
     */
    public function testExecuteCountTaskWithoutDiscrepancy(): void
    {
        // 准备任务
        $task = new CountTask();
        $task->setTaskType('count');
        $task->setTaskName('Test Count Task');
        $task->setTaskData(['location_code' => 'LOC-001']);

        // 准备盘点数据
        $countData = [
            'system_quantity' => 100,
            'actual_quantity' => 100,
            'location_code' => 'LOC-001',
            'product_info' => ['sku' => 'PROD-001'],
        ];

        $executionContext = [
            'counter_id' => 123,
            'count_method' => 'barcode',
        ];

        // 执行测试
        $result = $this->service->executeCountTask($task, $countData, $executionContext);

        // 验证结果
        $this->assertEquals('completed', $result['task_status']);
        $this->assertEquals(100.0, $result['count_accuracy']);
        $this->assertEmpty($result['discrepancies']);
        $this->assertEquals(['mark_completed'], $result['next_actions']);
        $this->assertArrayHasKey('completion_time', $result);

        // 验证任务状态和数据
        $this->assertEquals(TaskStatus::COMPLETED, $task->getStatus());
        $taskData = $task->getTaskData();
        $this->assertIsArray($taskData);
        $this->assertArrayHasKey('count_result', $taskData);
        $this->assertIsArray($taskData['count_result']);
        $this->assertArrayHasKey('system_quantity', $taskData['count_result']);
        $this->assertEquals(100, $taskData['count_result']['system_quantity']);
        $this->assertArrayHasKey('actual_quantity', $taskData['count_result']);
        $this->assertEquals(100, $taskData['count_result']['actual_quantity']);
        $this->assertArrayHasKey('accuracy', $taskData['count_result']);
        $this->assertEquals(100.0, $taskData['count_result']['accuracy']);
        $this->assertArrayHasKey('counter_id', $taskData['count_result']);
        $this->assertEquals(123, $taskData['count_result']['counter_id']);
        $this->assertArrayHasKey('count_method', $taskData['count_result']);
        $this->assertEquals('barcode', $taskData['count_result']['count_method']);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\InventoryCountService::executeCountTask
     */
    public function testExecuteCountTaskWithDiscrepancy(): void
    {
        // 准备任务
        $task = new CountTask();
        $task->setTaskType('count');
        $task->setTaskName('Test Count Task');
        $task->setTaskData(['location_code' => 'LOC-002']);

        // 准备盘点数据 - 有差异
        $countData = [
            'system_quantity' => 100,
            'actual_quantity' => 95, // 差异5个
            'location_code' => 'LOC-002',
            'product_info' => ['sku' => 'PROD-002'],
        ];

        // 执行测试
        $result = $this->service->executeCountTask($task, $countData);

        // 验证结果
        $this->assertIsArray($result);
        $this->assertArrayHasKey('task_status', $result);
        $this->assertEquals('discrepancy_found', $result['task_status']);
        $this->assertArrayHasKey('count_accuracy', $result);
        $this->assertEquals(95.0, $result['count_accuracy']); // (1 - 5/100) * 100 = 95%
        $this->assertArrayHasKey('discrepancies', $result);
        $this->assertIsArray($result['discrepancies']);
        $this->assertCount(1, $result['discrepancies']);
        $this->assertArrayHasKey('next_actions', $result);
        $this->assertEquals(['auto_adjust_inventory'], $result['next_actions']);

        // 验证差异数据
        $this->assertArrayHasKey(0, $result['discrepancies']);
        $discrepancy = $result['discrepancies'][0];
        $this->assertIsArray($discrepancy);
        $this->assertArrayHasKey('discrepancy_type', $discrepancy);
        $this->assertEquals('quantity', $discrepancy['discrepancy_type']);
        $this->assertArrayHasKey('quantity_difference', $discrepancy);
        $this->assertEquals(-5, $discrepancy['quantity_difference']);
        $this->assertArrayHasKey('system_quantity', $discrepancy);
        $this->assertEquals(100, $discrepancy['system_quantity']);
        $this->assertArrayHasKey('actual_quantity', $discrepancy);
        $this->assertEquals(95, $discrepancy['actual_quantity']);
        $this->assertArrayHasKey('location_code', $discrepancy);
        $this->assertEquals('LOC-002', $discrepancy['location_code']);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\InventoryCountService::executeCountTask
     */
    public function testExecuteCountTaskWithInvalidData(): void
    {
        $task = new CountTask();
        $task->setTaskType('count');

        // 缺少必要字段
        $countData = [
            'actual_quantity' => 50,
            // 缺少 system_quantity
        ];

        // 执行测试
        $result = $this->service->executeCountTask($task, $countData);

        // 验证结果
        $this->assertEquals('pending_review', $result['task_status']);
        $this->assertEquals(0, $result['count_accuracy']);

        /** @var array<int, string> $discrepancies */
        $discrepancies = $result['discrepancies'];
        $this->assertContainsEquals('Missing system_quantity', $discrepancies);
        $this->assertEquals(['data_correction_required'], $result['next_actions']);
        $this->assertEquals(0, $result['completion_time']);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\InventoryCountService::handleDiscrepancy
     */
    public function testHandleDiscrepancyAutoAdjust(): void
    {
        $task = new CountTask();
        $task->setTaskData([]);

        $discrepancyData = [
            'quantity_difference' => 3,
            'value_impact' => 50, // 低于自动调整阈值
        ];

        $handlingOptions = [
            'auto_adjust_threshold' => 100,
        ];

        // 执行测试
        $result = $this->service->handleDiscrepancy($task, $discrepancyData, $handlingOptions);

        // 验证结果
        $this->assertEquals('auto_adjust', $result['handling_action']);
        $this->assertEquals(50, $result['adjustment_amount']);
        $this->assertFalse($result['approval_required']);
        $this->assertEmpty($result['follow_up_tasks']);
        $this->assertTrue($result['notification_sent']);

        // 验证任务数据更新
        $taskData = $task->getTaskData();
        $this->assertIsArray($taskData);
        $this->assertArrayHasKey('discrepancy_handling', $taskData);
        $discrepancyHandling = $taskData['discrepancy_handling'];
        $this->assertIsArray($discrepancyHandling);
        $this->assertEquals('auto_adjust', $discrepancyHandling['handling_action']);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\InventoryCountService::handleDiscrepancy
     */
    public function testHandleDiscrepancySupervisorReview(): void
    {
        $task = new CountTask();
        $task->setTaskData([]);

        $discrepancyData = [
            'quantity_difference' => 15, // 数量差异大
            'value_impact' => 500, // 超过自动调整阈值但低于主管阈值
        ];

        $handlingOptions = [
            'auto_adjust_threshold' => 100,
            'supervisor_threshold' => 1000,
        ];

        // 执行测试
        $result = $this->service->handleDiscrepancy($task, $discrepancyData, $handlingOptions);

        // 验证结果
        $this->assertEquals('supervisor_review', $result['handling_action']);
        $this->assertTrue($result['approval_required']);

        /** @var array<int, string> $followUpTasks */
        $followUpTasks = $result['follow_up_tasks'];
        $this->assertContainsEquals('supervisor_review_required', $followUpTasks);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\InventoryCountService::handleDiscrepancy
     */
    public function testHandleDiscrepancyManagerEscalation(): void
    {
        $task = new CountTask();
        $task->setTaskData([]);

        $discrepancyData = [
            'quantity_difference' => 50,
            'value_impact' => 1500, // 超过主管阈值
        ];

        $handlingOptions = [
            'supervisor_threshold' => 1000,
        ];

        // 执行测试
        $result = $this->service->handleDiscrepancy($task, $discrepancyData, $handlingOptions);

        // 验证结果
        $this->assertEquals('manager_escalation', $result['handling_action']);
        $this->assertTrue($result['approval_required']);

        /** @var array<int, string> $followUpTasks */
        $followUpTasks = $result['follow_up_tasks'];
        $this->assertContainsEquals('manager_approval_required', $followUpTasks);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\InventoryCountService::handleDiscrepancy
     */
    public function testHandleDiscrepancyRecount(): void
    {
        $task = new CountTask();
        $task->setId(123);
        $task->setTaskName('Original Task');
        $task->setPriority(50);
        $task->setTaskData(['location_code' => 'LOC-001']);

        $discrepancyData = [
            'quantity_difference' => 8, // 触发复盘条件
            'value_impact' => 80,
        ];

        // 执行测试
        $result = $this->service->handleDiscrepancy($task, $discrepancyData);

        // 验证结果
        $this->assertEquals('recount', $result['handling_action']);

        /** @var array<int, string> $followUpTasks */
        $followUpTasks = $result['follow_up_tasks'];
        $this->assertContainsEquals('schedule_recount_task', $followUpTasks);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\InventoryCountService::getCountProgress
     */
    public function testGetCountProgress(): void
    {
        // 创建真实的计划并持久化
        $plan = new CountPlan();
        $plan->setCountType('full');
        $plan->setName('测试进度计划');
        $plan->setPriority(80);
        $plan->setStartDate(new \DateTimeImmutable('2025-09-01'));
        $plan->setEndDate(new \DateTimeImmutable('2025-09-07'));
        $plan->setStatus('in_progress');
        $plan->setScope(['test' => true]);

        $entityManager = parent::getEntityManager();
        $entityManager->persist($plan);
        $entityManager->flush(); // 先flush以获取plan ID

        // 创建并保存真实任务
        for ($i = 0; $i < 10; ++$i) {
            $task = new CountTask();
            $task->setCountPlanId($plan->getId());
            $task->setTaskType('count');
            $task->setTaskName("测试任务 {$i}");
            $task->setPriority(50);
            $task->setStatus($i < 6 ? TaskStatus::COMPLETED : ($i < 8 ? TaskStatus::DISCREPANCY_FOUND : TaskStatus::PENDING));
            $task->setTaskData(['count_result' => ['accuracy' => 95.0 + $i]]);
            $entityManager->persist($task);
        }

        $entityManager->flush();

        // 执行测试
        $result = $this->service->getCountProgress($plan);

        // 验证结果
        $this->assertEquals(10, $result['total_tasks']);
        $this->assertEquals(6, $result['completed_tasks']);
        $this->assertEquals(2, $result['pending_tasks']);
        $this->assertEquals(2, $result['discrepancy_tasks']);
        $this->assertEquals(60.0, $result['completion_percentage']); // 6/10 * 100
        $this->assertArrayHasKey('estimated_completion', $result);
        $this->assertArrayHasKey('team_performance', $result);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\InventoryCountService::generateDiscrepancyReport
     */
    public function testGenerateDiscrepancyReport(): void
    {
        // 创建并持久化计划
        $plan = new CountPlan();
        $plan->setCountType('cycle');
        $plan->setName('Test Count Plan');
        $plan->setPriority(60);
        $plan->setStartDate(new \DateTimeImmutable('2025-09-01'));
        $plan->setEndDate(new \DateTimeImmutable('2025-09-03'));
        $plan->setStatus('completed');
        $plan->setScope(['test' => true]);

        $entityManager = parent::getEntityManager();
        $entityManager->persist($plan);
        $entityManager->flush(); // 先flush以获取plan ID

        // 创建并保存任务
        $task1 = $this->createMockTaskWithResult('completed', 98.5);
        $task1->setCountPlanId($plan->getId());
        $entityManager->persist($task1);

        $task2 = $this->createMockTaskWithResult('discrepancy_found', 85.0);
        $task2->setCountPlanId($plan->getId());
        $entityManager->persist($task2);

        $entityManager->flush();

        // 执行测试
        $result = $this->service->generateDiscrepancyReport($plan);

        // 验证结果
        $this->assertArrayHasKey('report_id', $result);
        $expectedPrefix = 'COUNT_REPORT_' . $plan->getId() . '_';
        $reportId = $result['report_id'];
        $fileUrl = $result['file_url'];
        $this->assertIsString($reportId);
        $this->assertIsString($fileUrl);
        $this->assertStringContainsString($expectedPrefix, $reportId);
        $this->assertStringContainsString('/reports/', $fileUrl);
        $this->assertArrayHasKey('summary_statistics', $result);
        $this->assertArrayHasKey('accuracy_analysis', $result);
        $this->assertArrayHasKey('cost_impact_analysis', $result);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\InventoryCountService::analyzeCountResults
     */
    public function testAnalyzeCountResults(): void
    {
        // 创建并持久化真实计划
        $entityManager = parent::getEntityManager();

        $plan1 = $this->createMockPlan(0, 'Plan 1');
        $entityManager->persist($plan1);

        $plan2 = $this->createMockPlan(0, 'Plan 2');
        $entityManager->persist($plan2);

        $entityManager->flush();

        // 创建并保存任务
        $task1 = $this->createMockTaskWithResult('completed', 96.0);
        $task1->setCountPlanId($plan1->getId());
        $entityManager->persist($task1);

        $task2 = $this->createMockTaskWithResult('completed', 94.0);
        $task2->setCountPlanId($plan1->getId());
        $entityManager->persist($task2);

        $task3 = $this->createMockTaskWithResult('discrepancy_found', 88.0);
        $task3->setCountPlanId($plan2->getId());
        $entityManager->persist($task3);

        $entityManager->flush();

        // 获取实际ID并确保非null
        $plan1Id = $plan1->getId();
        $plan2Id = $plan2->getId();
        $this->assertNotNull($plan1Id);
        $this->assertNotNull($plan2Id);
        $planIds = [$plan1Id, $plan2Id];

        // 执行测试
        $result = $this->service->analyzeCountResults($planIds);

        // 验证结果
        $this->assertArrayHasKey('overall_accuracy', $result);
        $this->assertEquals(92.67, $result['overall_accuracy']); // (96+94+88)/3
        $this->assertArrayHasKey('trend_analysis', $result);
        $this->assertArrayHasKey('problem_categories', $result);
        $this->assertArrayHasKey('location_accuracy_ranking', $result);
        $this->assertArrayHasKey('improvement_recommendations', $result);
        $this->assertArrayHasKey('cost_benefit_analysis', $result);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\InventoryCountService::optimizeCountFrequency
     */
    public function testOptimizeCountFrequency(): void
    {
        $optimizationCriteria = [
            'warehouse_zones' => ['A1', 'B1'],
            'product_categories' => ['electronics'],
            'historical_months' => 6,
        ];

        // 执行测试
        $result = $this->service->optimizeCountFrequency($optimizationCriteria);

        // 验证结果
        $this->assertIsArray($result);
        $this->assertArrayHasKey('zone_frequency_recommendations', $result);
        $this->assertArrayHasKey('category_frequency_recommendations', $result);
        $this->assertArrayHasKey('cost_optimization_potential', $result);
        $this->assertArrayHasKey('implementation_plan', $result);

        // 验证频率建议
        /** @var array<string, string> $zoneRecs */
        $zoneRecs = $result['zone_frequency_recommendations'];
        $this->assertArrayHasKey('high_value_zone', $zoneRecs);
        $this->assertEquals('weekly', $zoneRecs['high_value_zone']);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\InventoryCountService::handleCountException
     */
    public function testHandleCountExceptionEquipmentFailure(): void
    {
        $task = new CountTask();
        $task->setTaskType('count');
        $task->setTaskName('Test Exception Task');
        $task->setPriority(50);
        $task->setStatus(TaskStatus::PENDING);
        $task->setTaskData([]);

        $exceptionType = 'equipment_failure';
        $exceptionDetails = ['device_id' => 'SCANNER_001'];

        // 执行测试
        $result = $this->service->handleCountException($task, $exceptionType, $exceptionDetails);

        // 验证结果
        /** @var array<int, string> $recoveryActions */
        $recoveryActions = $result['recovery_actions'];
        $this->assertContainsEquals('switch_to_manual_count', $recoveryActions);
        $this->assertContainsEquals('request_backup_equipment', $recoveryActions);

        /** @var array<int, string> $alternativeProcedures */
        $alternativeProcedures = $result['alternative_procedures'];
        $this->assertContainsEquals('manual_barcode_entry', $alternativeProcedures);
        $this->assertFalse($result['escalation_required']);
        $this->assertArrayHasKey('impact_assessment', $result);

        // 验证任务数据更新
        $taskData = $task->getTaskData();
        $this->assertIsArray($taskData);
        $this->assertArrayHasKey('exception_handling', $taskData);
        /** @var array<string, mixed> $exceptionHandling */
        $exceptionHandling = $taskData['exception_handling'];
        $this->assertIsArray($exceptionHandling);
        $this->assertEquals($exceptionType, $exceptionHandling['exception_type']);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\InventoryCountService::handleCountException
     */
    public function testHandleCountExceptionDataCorruption(): void
    {
        $task = new CountTask();
        $task->setTaskData([]);

        $exceptionType = 'data_corruption';

        // 执行测试
        $result = $this->service->handleCountException($task, $exceptionType);

        // 验证结果
        /** @var array<string> $recoveryActions */
        $recoveryActions = $result['recovery_actions'];
        $this->assertContainsEquals('restore_from_backup', $recoveryActions);
        $this->assertTrue($result['escalation_required']);
        $impactAssessment = $result['impact_assessment'];
        $this->assertIsArray($impactAssessment);
        $this->assertEquals('critical', $impactAssessment['severity_level']);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\InventoryCountService::validateCountDataQuality
     */
    public function testValidateCountDataQualityValid(): void
    {
        $countDataBatch = [
            [
                'system_quantity' => 100,
                'actual_quantity' => 98,
                'location_code' => 'LOC-001',
                'product_info' => ['sku' => 'PROD-001'],
            ],
            [
                'system_quantity' => 50,
                'actual_quantity' => 50,
                'location_code' => 'LOC-002',
                'product_info' => ['sku' => 'PROD-002'],
            ],
        ];

        // 执行测试
        $result = $this->service->validateCountDataQuality($countDataBatch);

        // 验证结果
        $this->assertTrue($result['validation_passed']);
        $this->assertEquals(100, $result['data_quality_score']);
        $this->assertEmpty($result['validation_errors']);
        $this->assertEmpty($result['data_corrections']);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\InventoryCountService::validateCountDataQuality
     */
    public function testValidateCountDataQualityInvalid(): void
    {
        $countDataBatch = [
            [
                // 缺少必填字段
                'actual_quantity' => -5, // 负数
                'location_code' => 'LOC-001',
            ],
            [
                'system_quantity' => 'invalid', // 非数字
                'actual_quantity' => 1000,
                'location_code' => 'LOC-002',
                'product_info' => ['sku' => 'PROD-002'],
            ],
        ];

        // 执行测试
        $result = $this->service->validateCountDataQuality($countDataBatch);

        // 验证结果
        $this->assertFalse($result['validation_passed']);
        $this->assertLessThan(100, $result['data_quality_score']);
        $this->assertNotEmpty($result['validation_errors']);
        /** @var array<string> $validationErrors */
        $validationErrors = $result['validation_errors'];
        $this->assertContainsEquals('Row 0: Missing required field \'system_quantity\'', $validationErrors);
        $this->assertContainsEquals('Row 0: actual_quantity cannot be negative', $validationErrors);
        $this->assertContainsEquals('Row 1: system_quantity must be numeric', $validationErrors);
        $this->assertNotEmpty($result['data_corrections']);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\InventoryCountService::validateCountDataQuality
     */
    public function testValidateCountDataQualityExcessiveDifference(): void
    {
        $countDataBatch = [
            [
                'system_quantity' => 100,
                'actual_quantity' => 400, // 差异400% > 200%阈值
                'location_code' => 'LOC-001',
                'product_info' => ['sku' => 'PROD-001'],
            ],
        ];

        // 执行测试
        $result = $this->service->validateCountDataQuality($countDataBatch);

        // 验证结果
        $this->assertFalse($result['validation_passed']);
        $this->assertLessThan(100, $result['data_quality_score']);
        /** @var array<string> $validationErrors */
        $validationErrors = $result['validation_errors'];
        $this->assertContainsEquals('Row 0: Excessive difference (>200%) may indicate data error', $validationErrors);
    }

    /**
     * 创建带有结果的任务（包含所有必需字段）
     */
    private function createMockTaskWithResult(string $status, float $accuracy): CountTask
    {
        $task = new CountTask();
        $task->setTaskType('count');
        $task->setTaskName('Test Task');
        $task->setPriority(50);

        $taskStatus = match ($status) {
            'completed' => TaskStatus::COMPLETED,
            'discrepancy_found' => TaskStatus::DISCREPANCY_FOUND,
            'pending' => TaskStatus::PENDING,
            default => TaskStatus::PENDING,
        };
        $task->setStatus($taskStatus);
        $task->setTaskData([
            'location_code' => 'LOC-TEST',
            'count_result' => ['accuracy' => $accuracy],
        ]);

        return $task;
    }

    /**
     * 创建计划（包含所有必需字段）
     */
    private function createMockPlan(int $id, string $name): CountPlan
    {
        $plan = new CountPlan();
        if ($id > 0) {
            $plan->setId($id);
        }
        $plan->setCountType('cycle');
        $plan->setName($name);
        $plan->setPriority(60);
        $plan->setStartDate(new \DateTimeImmutable('2025-09-01'));
        $plan->setEndDate(new \DateTimeImmutable('2025-09-03'));
        $plan->setStatus('draft');
        $plan->setScope(['test' => true]);

        return $plan;
    }
}
