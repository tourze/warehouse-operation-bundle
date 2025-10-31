<?php

declare(strict_types=1);

namespace Tourze\WarehouseOperationBundle\Tests\Service\Count;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\WarehouseOperationBundle\Entity\CountTask;
use Tourze\WarehouseOperationBundle\Enum\TaskStatus;
use Tourze\WarehouseOperationBundle\Event\CountDiscrepancyEvent;
use Tourze\WarehouseOperationBundle\Repository\CountTaskRepository;
use Tourze\WarehouseOperationBundle\Service\Count\CountDiscrepancyHandlerService;

/**
 * CountDiscrepancyHandlerService 单元测试
 *
 * 测试盘点差异处理服务的完整功能，包括差异检测、处理策略、复盘任务创建等核心业务逻辑。
 * 验证服务的正确性、处理策略和异常处理。
 * @internal
 */
#[CoversClass(CountDiscrepancyHandlerService::class)]
#[RunTestsInSeparateProcesses]
class CountDiscrepancyHandlerServiceTest extends AbstractIntegrationTestCase
{
    private EventDispatcherInterface $eventDispatcher;

    private CountTaskRepository $countTaskRepository;

    protected function onSetUp(): void
    {
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->countTaskRepository = parent::getService(CountTaskRepository::class);
    }

    private function getDiscrepancyService(): CountDiscrepancyHandlerService
    {
        // 直接创建服务实例，使用Mock依赖验证行为
        // @phpstan-ignore integrationTest.noDirectInstantiationOfCoveredClass
        return new CountDiscrepancyHandlerService(
            $this->eventDispatcher,
            $this->countTaskRepository
        );
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Count\CountDiscrepancyHandlerService::handleDiscrepancy
     */
    public function testHandleDiscrepancyWithAutoAdjust(): void
    {
        $task = new CountTask();
        $task->setTaskName('自动调整测试任务');
        $task->setTaskType('count');
        $task->setStatus(TaskStatus::PENDING);
        $task->setTaskData([]);

        $discrepancyData = [
            'quantity_difference' => 3,
            'value_impact' => 50, // 低于自动调整阈值
        ];

        $handlingOptions = [
            'auto_adjust_threshold' => 100,
            'supervisor_threshold' => 1000,
        ];

        // 执行测试
        $result = $this->getDiscrepancyService()->handleDiscrepancy($task, $discrepancyData, $handlingOptions);

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
        $this->assertEquals(50, $discrepancyHandling['value_impact']);
        $this->assertArrayHasKey('handling_timestamp', $discrepancyHandling);
        $this->assertFalse($discrepancyHandling['approval_required']);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Count\CountDiscrepancyHandlerService::handleDiscrepancy
     */
    public function testHandleDiscrepancyWithSupervisorReview(): void
    {
        $task = new CountTask();
        $task->setTaskName('主管审核测试任务');
        $task->setTaskType('count');
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
        $result = $this->getDiscrepancyService()->handleDiscrepancy($task, $discrepancyData, $handlingOptions);

        // 验证结果
        $this->assertEquals('supervisor_review', $result['handling_action']);
        $this->assertEquals(500, $result['adjustment_amount']);
        $this->assertTrue($result['approval_required']);
        $followUpTasks = $result['follow_up_tasks'];
        $this->assertIsArray($followUpTasks);
        $this->assertContainsEquals('supervisor_review_required', $followUpTasks);
        $this->assertTrue($result['notification_sent']);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Count\CountDiscrepancyHandlerService::handleDiscrepancy
     */
    public function testHandleDiscrepancyWithManagerEscalation(): void
    {
        $task = new CountTask();
        $task->setTaskName('经理升级测试任务');
        $task->setTaskType('count');
        $task->setTaskData([]);

        $discrepancyData = [
            'quantity_difference' => 50,
            'value_impact' => 1500, // 超过主管阈值
        ];

        $handlingOptions = [
            'supervisor_threshold' => 1000,
        ];

        // 执行测试
        $result = $this->getDiscrepancyService()->handleDiscrepancy($task, $discrepancyData, $handlingOptions);

        // 验证结果
        $this->assertEquals('manager_escalation', $result['handling_action']);
        $this->assertEquals(1500, $result['adjustment_amount']);
        $this->assertTrue($result['approval_required']);
        $followUpTasks = $result['follow_up_tasks'];
        $this->assertIsArray($followUpTasks);
        $this->assertContainsEquals('manager_approval_required', $followUpTasks);
        $this->assertTrue($result['notification_sent']);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Count\CountDiscrepancyHandlerService::handleDiscrepancy
     */
    public function testHandleDiscrepancyWithRecount(): void
    {
        $task = new CountTask();
        $task->setTaskName('复盘测试任务');
        $task->setTaskType('count');
        $task->setPriority(50);
        $task->setTaskData(['location_code' => 'A1-001']);
        $this->countTaskRepository->save($task);

        $taskId = $task->getId();
        $this->assertNotNull($taskId);

        $discrepancyData = [
            'quantity_difference' => 8, // 触发复盘条件
            'value_impact' => 80,
        ];

        // 执行测试
        $result = $this->getDiscrepancyService()->handleDiscrepancy($task, $discrepancyData);

        // 验证结果
        $this->assertEquals('recount', $result['handling_action']);
        $followUpTasks = $result['follow_up_tasks'];
        $this->assertIsArray($followUpTasks);
        $this->assertContainsEquals('schedule_recount_task', $followUpTasks);
        $this->assertTrue($result['notification_sent']);

        // 验证复盘任务创建
        // 由于没有直接的方式查询新创建的复盘任务，我们验证任务数据更新
        $taskData = $task->getTaskData();
        $this->assertIsArray($taskData);
        $this->assertArrayHasKey('discrepancy_handling', $taskData);
        $discrepancyHandling = $taskData['discrepancy_handling'];
        $this->assertIsArray($discrepancyHandling);
        $this->assertEquals('recount', $discrepancyHandling['handling_action']);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Count\CountDiscrepancyHandlerService::checkForDiscrepancies
     */
    public function testCheckForDiscrepanciesWithDifference(): void
    {
        $task = new CountTask();
        $task->setTaskName('差异检查任务');
        $task->setTaskType('count');

        $countData = [
            'system_quantity' => 100,
            'actual_quantity' => 95, // 差异5个
            'location_code' => 'A1-001',
            'product_info' => ['sku' => 'PROD-001', 'name' => '测试商品'],
        ];

        // 模拟事件派发
        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(self::isInstanceOf(CountDiscrepancyEvent::class))
        ;

        // 执行测试
        $result = $this->getDiscrepancyService()->checkForDiscrepancies($task, $countData);

        // 验证结果
        $this->assertCount(1, $result);

        $discrepancy = $result[0];
        $this->assertEquals('quantity', $discrepancy['discrepancy_type']);
        $this->assertEquals(-5, $discrepancy['quantity_difference']);
        $this->assertEquals(100, $discrepancy['system_quantity']);
        $this->assertEquals(95, $discrepancy['actual_quantity']);
        $this->assertEquals('A1-001', $discrepancy['location_code']);
        $this->assertEquals(['sku' => 'PROD-001', 'name' => '测试商品'], $discrepancy['product_info']);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Count\CountDiscrepancyHandlerService::checkForDiscrepancies
     */
    public function testCheckForDiscrepanciesWithNoDifference(): void
    {
        $task = new CountTask();
        $task->setTaskName('无差异检查任务');
        $task->setTaskType('count');

        $countData = [
            'system_quantity' => 100,
            'actual_quantity' => 100, // 无差异
            'location_code' => 'A1-001',
            'product_info' => ['sku' => 'PROD-001'],
        ];

        // 不应该派发事件
        $this->eventDispatcher->expects($this->never())
            ->method('dispatch')
        ;

        // 执行测试
        $result = $this->getDiscrepancyService()->checkForDiscrepancies($task, $countData);

        // 验证结果
        $this->assertEmpty($result);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Count\CountDiscrepancyHandlerService::checkForDiscrepancies
     */
    public function testCheckForDiscrepanciesWithMissingData(): void
    {
        $task = new CountTask();
        $task->setTaskName('缺少数据检查任务');
        $task->setTaskType('count');

        $countData = [
            'system_quantity' => null,
            'actual_quantity' => 50,
            // 缺少其他字段
        ];

        // 执行测试
        $result = $this->getDiscrepancyService()->checkForDiscrepancies($task, $countData);

        // 验证结果 - null != 50，所以应该有差异
        $this->assertCount(1, $result);

        $discrepancy = $result[0];
        $this->assertEquals('quantity', $discrepancy['discrepancy_type']);
        $this->assertEquals(50, $discrepancy['quantity_difference']); // 50 - 0(null转换为0)
        $this->assertEquals(0, $discrepancy['system_quantity']); // null转换为0
        $this->assertEquals(50, $discrepancy['actual_quantity']);
        $this->assertEquals('', $discrepancy['location_code']);
        $this->assertEquals([], $discrepancy['product_info']);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Count\CountDiscrepancyHandlerService::handleDiscrepancy
     */
    public function testHandleDiscrepancyWithDefaultOptions(): void
    {
        $task = new CountTask();
        $task->setTaskName('默认选项测试任务');
        $task->setTaskType('count');
        $task->setTaskData([]);

        $discrepancyData = [
            'quantity_difference' => 2, // 小差异
            'value_impact' => 25, // 小价值影响
        ];

        // 使用默认选项
        $result = $this->getDiscrepancyService()->handleDiscrepancy($task, $discrepancyData);

        // 验证使用默认阈值的结果
        $this->assertEquals('auto_adjust', $result['handling_action']);
        $this->assertFalse($result['approval_required']);
        $this->assertEmpty($result['follow_up_tasks']);
    }

    public function testServiceConstructorAndBasicFunctionality(): void
    {
        // 验证服务可以正确实例化
        $this->assertInstanceOf(CountDiscrepancyHandlerService::class, $this->getDiscrepancyService());

        // 验证基本差异检查功能
        $task = new CountTask();
        $countData = ['system_quantity' => 10, 'actual_quantity' => 10];

        $result = $this->getDiscrepancyService()->checkForDiscrepancies($task, $countData);
        $this->assertIsArray($result);
    }

    /**
     * 测试处理策略决策逻辑的边界条件
     */
    public function testHandlingStrategyBoundaryConditions(): void
    {
        $task = new CountTask();
        $task->setTaskName('边界条件测试');
        $task->setTaskType('count');
        $task->setTaskData([]);

        // 测试恰好达到阈值的情况
        $discrepancyData = [
            'quantity_difference' => 10, // 恰好超过复盘阈值
            'value_impact' => 100, // 恰好达到自动调整阈值
        ];

        $handlingOptions = [
            'auto_adjust_threshold' => 100,
            'supervisor_threshold' => 1000,
        ];

        $result = $this->getDiscrepancyService()->handleDiscrepancy($task, $discrepancyData, $handlingOptions);

        // 数量差异10 > 5，会触发复盘（复盘优先级更高）
        $this->assertEquals('recount', $result['handling_action']);
        $this->assertFalse($result['approval_required']);
    }

    /**
     * 测试边缘情况：零差异但有价值影响
     */
    public function testHandleDiscrepancyWithZeroQuantityButValueImpact(): void
    {
        $task = new CountTask();
        $task->setTaskName('零数量差异测试');
        $task->setTaskType('count');
        $task->setTaskData([]);

        $discrepancyData = [
            'quantity_difference' => 0,
            'value_impact' => 150, // 超过自动调整阈值但无数量差异
        ];

        $handlingOptions = [
            'auto_adjust_threshold' => 100,
        ];

        $result = $this->getDiscrepancyService()->handleDiscrepancy($task, $discrepancyData, $handlingOptions);

        // 价值影响超过阈值，应该需要主管审核
        $this->assertEquals('supervisor_review', $result['handling_action']);
        $this->assertTrue($result['approval_required']);
    }
}
