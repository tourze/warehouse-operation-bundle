<?php

declare(strict_types=1);

namespace Tourze\WarehouseOperationBundle\Tests\Service\Count;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\WarehouseOperationBundle\Entity\CountPlan;
use Tourze\WarehouseOperationBundle\Service\Count\CountPlanGeneratorService;

/**
 * CountPlanGeneratorService 单元测试
 *
 * 测试盘点计划生成服务的完整功能，包括计划生成、配置设置、资源估算等核心业务逻辑。
 * 验证服务的正确性、参数处理和异常处理。
 * @internal
 */
#[CoversClass(CountPlanGeneratorService::class)]
#[RunTestsInSeparateProcesses]
class CountPlanGeneratorServiceTest extends AbstractIntegrationTestCase
{
    private CountPlanGeneratorService $service;

    protected function onSetUp(): void
    {
        $this->service = parent::getService(CountPlanGeneratorService::class);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Count\CountPlanGeneratorService::generatePlan
     */
    public function testGeneratePlanWithFullCountType(): void
    {
        $countType = 'full';
        $criteria = [
            'warehouse_zones' => ['A1', 'A2', 'B1'],
            'product_categories' => ['electronics', 'clothing'],
            'value_threshold' => 1000,
            'accuracy_requirement' => 98.0,
        ];
        $planOptions = [
            'schedule_date' => '2025-09-05',
            'duration_days' => 7,
            'team_assignment' => 'manual',
        ];

        // 执行测试
        $result = $this->service->generatePlan($countType, $criteria, $planOptions);

        // 验证结果
        $this->assertInstanceOf(CountPlan::class, $result);
        $this->assertNotNull($result->getId());

        // 验证基本信息
        $this->assertEquals('full', $result->getCountType());
        $this->assertStringContainsString('全盘点计划_', $result->getName());
        $description = $result->getDescription();
        $this->assertNotNull($description);
        $this->assertStringContainsString('盘点类型: full', $description);
        $this->assertEquals(90, $result->getPriority()); // full类型默认优先级

        // 验证调度信息
        $startDate = $result->getStartDate();
        $endDate = $result->getEndDate();
        $this->assertNotNull($startDate);
        $this->assertNotNull($endDate);
        $this->assertEquals('2025-09-05', $startDate->format('Y-m-d'));
        $this->assertEquals('2025-09-12', $endDate->format('Y-m-d')); // +7天

        // 验证范围配置
        $scope = $result->getScope();
        $this->assertEquals('full', $scope['count_type']);
        $this->assertEquals(['A1', 'A2', 'B1'], $scope['warehouse_zones']);
        $this->assertEquals(['electronics', 'clothing'], $scope['product_categories']);
        $this->assertEquals(1000, $scope['value_threshold']);
        $this->assertEquals(98.0, $scope['accuracy_requirement']);

        // 验证调度配置
        $schedule = $result->getSchedule();
        $this->assertIsArray($schedule);
        $this->assertEquals('manual', $schedule['team_assignment']);
        $workHours = $schedule['work_hours'];
        $this->assertIsArray($workHours);
        $this->assertEquals('08:00', $workHours['start_time']);
        $this->assertEquals('18:00', $workHours['end_time']);
        $this->assertArrayHasKey('estimated_task_count', $schedule);
        $this->assertArrayHasKey('resource_requirements', $schedule);

        // 验证状态
        $this->assertEquals('draft', $result->getStatus());
        $this->assertTrue($result->isActive());
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Count\CountPlanGeneratorService::generatePlan
     */
    public function testGeneratePlanWithCycleCountType(): void
    {
        $countType = 'cycle';
        $criteria = [];
        $planOptions = [];

        // 执行测试
        $result = $this->service->generatePlan($countType, $criteria, $planOptions);

        // 验证结果
        $this->assertInstanceOf(CountPlan::class, $result);
        $this->assertEquals('cycle', $result->getCountType());
        $this->assertEquals(60, $result->getPriority()); // cycle默认优先级
        $this->assertStringContainsString('循环盘点计划_', $result->getName());

        // 验证默认调度（明天开始，持续2天）
        $expectedStartDate = (new \DateTimeImmutable('+1 day'))->format('Y-m-d');
        $startDate = $result->getStartDate();
        $this->assertNotNull($startDate);
        $this->assertEquals($expectedStartDate, $startDate->format('Y-m-d'));

        $expectedEndDate = (new \DateTimeImmutable('+3 days'))->format('Y-m-d'); // +1天开始 +2天持续
        $endDate = $result->getEndDate();
        $this->assertNotNull($endDate);
        $this->assertEquals($expectedEndDate, $endDate->format('Y-m-d'));

        // 验证空范围配置
        $scope = $result->getScope();
        $this->assertEquals([], $scope['warehouse_zones']);
        $this->assertEquals([], $scope['product_categories']);
        $this->assertEquals(95.0, $scope['accuracy_requirement']); // 默认值
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Count\CountPlanGeneratorService::generatePlan
     */
    public function testGeneratePlanWithSpotCountType(): void
    {
        $countType = 'spot';
        $criteria = [
            'warehouse_zones' => ['A1'],
            'last_count_days' => 30,
        ];
        $planOptions = [
            'duration_days' => 1,
        ];

        // 执行测试
        $result = $this->service->generatePlan($countType, $criteria, $planOptions);

        // 验证结果
        $this->assertEquals('spot', $result->getCountType());
        $this->assertEquals(70, $result->getPriority()); // spot默认优先级
        $this->assertStringContainsString('抽盘点计划_', $result->getName());

        // 验证持续时间
        $startDate = $result->getStartDate();
        $endDate = $result->getEndDate();
        $this->assertNotNull($startDate);
        $this->assertNotNull($endDate);
        $diff = $startDate->diff($endDate);
        $this->assertEquals(1, $diff->days); // 1天持续

        // 验证描述包含区域信息
        $description = $result->getDescription();
        $this->assertNotNull($description);
        $this->assertStringContainsString('盘点区域: A1', $description);

        // 验证范围配置
        $scope = $result->getScope();
        $this->assertEquals(30, $scope['last_count_days']);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Count\CountPlanGeneratorService::generatePlan
     */
    public function testGeneratePlanWithAbcCountType(): void
    {
        $countType = 'abc';
        $criteria = [
            'product_categories' => ['electronics'],
            'inventory_turnover' => ['high', 'medium'],
        ];

        // 执行测试
        $result = $this->service->generatePlan($countType, $criteria);

        // 验证结果
        $this->assertEquals('abc', $result->getCountType());
        $this->assertEquals(80, $result->getPriority());
        $this->assertStringContainsString('ABC盘点计划_', $result->getName());

        // ABC类型默认3天
        $startDate = $result->getStartDate();
        $endDate = $result->getEndDate();
        $this->assertNotNull($startDate);
        $this->assertNotNull($endDate);
        $diff = $startDate->diff($endDate);
        $this->assertEquals(3, $diff->days);

        // 验证描述包含商品类别
        $description = $result->getDescription();
        $this->assertNotNull($description);
        $this->assertStringContainsString('商品类别: electronics', $description);

        // 验证范围配置
        $scope = $result->getScope();
        $this->assertEquals(['high', 'medium'], $scope['inventory_turnover']);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Count\CountPlanGeneratorService::generatePlan
     */
    public function testGeneratePlanWithRandomCountType(): void
    {
        $countType = 'random';
        $criteria = [];

        // 执行测试
        $result = $this->service->generatePlan($countType, $criteria);

        // 验证结果
        $this->assertEquals('random', $result->getCountType());
        $this->assertEquals(40, $result->getPriority()); // random最低优先级
        $this->assertStringContainsString('随机盘点计划_', $result->getName());

        // random类型默认1天
        $startDate = $result->getStartDate();
        $endDate = $result->getEndDate();
        $this->assertNotNull($startDate);
        $this->assertNotNull($endDate);
        $diff = $startDate->diff($endDate);
        $this->assertEquals(1, $diff->days);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Count\CountPlanGeneratorService::generatePlan
     */
    public function testGeneratePlanWithUnknownCountType(): void
    {
        $countType = 'unknown_type';
        $criteria = [];

        // 执行测试
        $result = $this->service->generatePlan($countType, $criteria);

        // 验证使用默认值
        $this->assertEquals('unknown_type', $result->getCountType());
        $this->assertEquals(50, $result->getPriority()); // 默认优先级
        $this->assertStringContainsString('unknown_type点计划_', $result->getName());

        // 默认持续时间2天
        $startDate = $result->getStartDate();
        $endDate = $result->getEndDate();
        $this->assertNotNull($startDate);
        $this->assertNotNull($endDate);
        $diff = $startDate->diff($endDate);
        $this->assertEquals(2, $diff->days);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Count\CountPlanGeneratorService::generatePlan
     */
    public function testGeneratePlanWithCustomScheduleDate(): void
    {
        $customDate = '2025-12-25';
        $planOptions = [
            'schedule_date' => $customDate,
            'duration_days' => 5,
        ];

        $result = $this->service->generatePlan('full', [], $planOptions);

        // 验证自定义日期
        $startDate = $result->getStartDate();
        $endDate = $result->getEndDate();
        $this->assertNotNull($startDate);
        $this->assertNotNull($endDate);
        $this->assertEquals($customDate, $startDate->format('Y-m-d'));
        $this->assertEquals('2025-12-30', $endDate->format('Y-m-d')); // +5天
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Count\CountPlanGeneratorService::generatePlan
     */
    public function testGeneratePlanTaskCountEstimation(): void
    {
        // 测试区域影响任务数量估算
        $criteria1 = ['warehouse_zones' => ['A1', 'A2', 'A3', 'A4', 'A5']]; // 5个区域
        $result1 = $this->service->generatePlan('full', $criteria1);
        $schedule1 = $result1->getSchedule();

        $criteria2 = ['warehouse_zones' => ['A1']]; // 1个区域
        $result2 = $this->service->generatePlan('full', $criteria2);
        $schedule2 = $result2->getSchedule();

        // 更多区域应该有更多任务
        $this->assertGreaterThan($schedule2['estimated_task_count'], $schedule1['estimated_task_count']);

        // 验证不同盘点类型的任务数量差异
        $fullResult = $this->service->generatePlan('full', []);
        $spotResult = $this->service->generatePlan('spot', []);

        $fullSchedule = $fullResult->getSchedule();
        $spotSchedule = $spotResult->getSchedule();

        // full盘点应该比spot盘点有更多任务
        $this->assertGreaterThan($spotSchedule['estimated_task_count'], $fullSchedule['estimated_task_count']);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Count\CountPlanGeneratorService::generatePlan
     */
    public function testGeneratePlanResourceRequirements(): void
    {
        $fullResult = $this->service->generatePlan('full', []);
        $cycleResult = $this->service->generatePlan('cycle', []);

        $fullSchedule = $fullResult->getSchedule();
        $cycleSchedule = $cycleResult->getSchedule();

        // 验证资源需求结构
        $this->assertArrayHasKey('resource_requirements', $fullSchedule);
        $fullResourceReq = $fullSchedule['resource_requirements'];
        $this->assertIsArray($fullResourceReq);
        $this->assertArrayHasKey('personnel_count', $fullResourceReq);
        $this->assertArrayHasKey('equipment_needed', $fullResourceReq);
        $this->assertArrayHasKey('estimated_hours', $fullResourceReq);

        // full盘点需要更多人员
        $this->assertEquals(10, $fullResourceReq['personnel_count']);
        $cycleResourceReq = $cycleSchedule['resource_requirements'];
        $this->assertIsArray($cycleResourceReq);
        $this->assertEquals(5, $cycleResourceReq['personnel_count']);

        // 验证设备需求
        $equipment = $fullResourceReq['equipment_needed'];
        $this->assertIsArray($equipment);
        $this->assertContainsEquals('barcode_scanner', $equipment);
        $this->assertContainsEquals('tablet', $equipment);
        $this->assertContainsEquals('printer', $equipment);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Count\CountPlanGeneratorService::generatePlan
     */
    public function testGeneratePlanWorkHours(): void
    {
        $result = $this->service->generatePlan('cycle', []);
        $schedule = $result->getSchedule();
        $this->assertIsArray($schedule);

        // 验证工作时间配置
        $workHours = $schedule['work_hours'];
        $this->assertIsArray($workHours);
        $this->assertEquals('08:00', $workHours['start_time']);
        $this->assertEquals('18:00', $workHours['end_time']);
        $this->assertEquals(60, $workHours['break_duration']);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Count\CountPlanGeneratorService::generatePlan
     */
    public function testGeneratePlanWithComplexCriteria(): void
    {
        $criteria = [
            'warehouse_zones' => ['A1', 'A2'],
            'product_categories' => ['electronics', 'books', 'clothing'],
            'value_threshold' => 5000,
            'last_count_days' => 90,
            'inventory_turnover' => ['high'],
            'accuracy_requirement' => 99.5,
        ];

        $planOptions = [
            'schedule_date' => '2025-10-01',
            'duration_days' => 4,
            'team_assignment' => 'automatic',
        ];

        $result = $this->service->generatePlan('abc', $criteria, $planOptions);

        // 验证复杂条件正确设置
        $scope = $result->getScope();
        $this->assertEquals(['A1', 'A2'], $scope['warehouse_zones']);
        $this->assertEquals(['electronics', 'books', 'clothing'], $scope['product_categories']);
        $this->assertEquals(5000, $scope['value_threshold']);
        $this->assertEquals(90, $scope['last_count_days']);
        $this->assertEquals(['high'], $scope['inventory_turnover']);
        $this->assertEquals(99.5, $scope['accuracy_requirement']);

        // 验证描述包含多个信息
        $description = $result->getDescription();
        $this->assertNotNull($description);
        $this->assertStringContainsString('盘点区域: A1, A2', $description);
        $this->assertStringContainsString('商品类别: electronics, books, clothing', $description);

        // 验证调度配置
        $schedule = $result->getSchedule();
        $this->assertEquals('automatic', $schedule['team_assignment']);
    }

    public function testServiceConstructorAndBasicFunctionality(): void
    {
        // 验证服务可以正确实例化
        $this->assertInstanceOf(CountPlanGeneratorService::class, $this->service);

        // 验证基本功能工作正常
        $result = $this->service->generatePlan('cycle', []);
        $this->assertInstanceOf(CountPlan::class, $result);
        $this->assertNotNull($result->getId());
    }

    /**
     * 测试计划名称生成逻辑
     */
    public function testPlanNameGeneration(): void
    {
        $testCases = [
            ['full', '全盘'],
            ['cycle', '循环盘'],
            ['abc', 'ABC盘'],
            ['spot', '抽盘'],
            ['random', '随机盘'],
            ['custom', 'custom'], // 未知类型使用原值
        ];

        foreach ($testCases as [$type, $expectedPrefix]) {
            $result = $this->service->generatePlan($type, []);
            $this->assertStringContainsString($expectedPrefix, $result->getName());
            $this->assertStringContainsString('点计划_', $result->getName());
            $this->assertStringContainsString((new \DateTimeImmutable())->format('Y-m-d'), $result->getName());
        }
    }

    /**
     * 测试边界条件：零区域任务数量估算
     */
    public function testTaskCountEstimationWithNoZones(): void
    {
        $result = $this->service->generatePlan('full', []);
        $schedule = $result->getSchedule();

        // 无区域时应该使用基础任务数量
        $this->assertEquals(1000, $schedule['estimated_task_count']); // full类型基础数量

        $result = $this->service->generatePlan('spot', []);
        $schedule = $result->getSchedule();
        $this->assertEquals(50, $schedule['estimated_task_count']); // spot类型基础数量
    }
}
