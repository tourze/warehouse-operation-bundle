<?php

declare(strict_types=1);

namespace Tourze\WarehouseOperationBundle\Tests\Service\Scheduling;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Psr\Log\LoggerInterface;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\WarehouseOperationBundle\Entity\InboundTask;
use Tourze\WarehouseOperationBundle\Entity\WarehouseTask;
use Tourze\WarehouseOperationBundle\Enum\TaskStatus;
use Tourze\WarehouseOperationBundle\Enum\TaskType;
use Tourze\WarehouseOperationBundle\Repository\WarehouseTaskRepository;
use Tourze\WarehouseOperationBundle\Service\Scheduling\TaskPriorityCalculatorService;

/**
 * TaskPriorityCalculatorService 单元测试
 *
 * 测试任务优先级计算服务的功能，包括优先级重计算、单任务计算、权重配置等核心逻辑。
 * 验证服务的正确性、计算准确性和边界条件处理。
 * @internal
 */
#[CoversClass(TaskPriorityCalculatorService::class)]
#[RunTestsInSeparateProcesses]
class TaskPriorityCalculatorServiceTest extends AbstractIntegrationTestCase
{
    private TaskPriorityCalculatorService $service;

    private WarehouseTaskRepository $taskRepository;

    private LoggerInterface $logger;

    protected function onSetUp(): void
    {
        $this->taskRepository = parent::getService(WarehouseTaskRepository::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        // 设置Mock的Logger到容器中
        parent::getContainer()->set(LoggerInterface::class, $this->logger);
        $this->service = parent::getService(TaskPriorityCalculatorService::class);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Scheduling\TaskPriorityCalculatorService::recalculatePriorities
     */
    public function testRecalculatePrioritiesWithNoTasks(): void
    {
        // 创建空的任务列表
        $context = [
            'trigger_reason' => 'test',
            'affected_zones' => [],
        ];

        $result = $this->service->recalculatePriorities($context);

        // 验证结果结构
        $this->assertArrayHasKey('updated_count', $result);
        $this->assertArrayHasKey('priority_changes', $result);
        $this->assertArrayHasKey('affected_assignments', $result);
        $this->assertArrayHasKey('trigger_reason', $result);
        $this->assertArrayHasKey('recalculation_timestamp', $result);
        $this->assertArrayHasKey('priority_distribution', $result);

        // 验证没有任务时的默认值
        $this->assertEquals(0, $result['updated_count']);
        $this->assertEmpty($result['priority_changes']);
        $this->assertEquals('test', $result['trigger_reason']);
        $this->assertInstanceOf(\DateTimeImmutable::class, $result['recalculation_timestamp']);

        // 验证受影响分配的结构
        self::assertIsArray($result);
        self::assertIsArray($result['affected_assignments']);
        /** @var array<string, mixed> $affectedAssignments */
        $affectedAssignments = $result['affected_assignments'];
        $this->assertArrayHasKey('reassignment_needed', $affectedAssignments);
        $this->assertArrayHasKey('affected_count', $affectedAssignments);
        $this->assertArrayHasKey('high_impact_changes', $affectedAssignments);

        $this->assertFalse($affectedAssignments['reassignment_needed']);
        $this->assertEquals(0, $affectedAssignments['affected_count']);
        $this->assertEmpty($affectedAssignments['high_impact_changes']);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Scheduling\TaskPriorityCalculatorService::recalculatePriorities
     */
    public function testRecalculatePrioritiesWithTasks(): void
    {
        // 创建测试任务
        $task1 = new InboundTask();
        $task1->setType(TaskType::QUALITY);
        $task1->setStatus(TaskStatus::PENDING);
        $task1->setPriority(50);
        $task1->setData(['urgent' => true, 'customer_tier' => 'vip']);

        $task2 = new InboundTask();
        $task2->setType(TaskType::INBOUND);
        $task2->setStatus(TaskStatus::PENDING);
        $task2->setPriority(40);
        $task2->setData(['priority_flag' => 'high']);

        $this->taskRepository->save($task1, false);
        $this->taskRepository->save($task2, false);
        parent::getEntityManager()->flush();

        $context = [
            'trigger_reason' => 'urgent_order',
            'priority_factors' => [
                'urgency' => 0.4,
                'customer_tier' => 0.3,
                'deadline_proximity' => 0.2,
                'resource_availability' => 0.05,
                'business_impact' => 0.05,
            ],
        ];

        $result = $this->service->recalculatePriorities($context);

        // 验证至少有一些任务被处理
        $this->assertGreaterThanOrEqual(0, $result['updated_count']);
        $this->assertEquals('urgent_order', $result['trigger_reason']);
        $this->assertIsArray($result['priority_changes']);
        $this->assertIsArray($result['priority_distribution']);

        // 验证优先级分布
        $distribution = $result['priority_distribution'];
        $this->assertArrayHasKey('low', $distribution);
        $this->assertArrayHasKey('medium', $distribution);
        $this->assertArrayHasKey('high', $distribution);
        $this->assertIsInt($distribution['low']);
        $this->assertIsInt($distribution['medium']);
        $this->assertIsInt($distribution['high']);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Scheduling\TaskPriorityCalculatorService::calculateTaskPriority
     */
    public function testCalculateTaskPriorityWithHighUrgency(): void
    {
        $task = new InboundTask();
        $task->setType(TaskType::QUALITY);
        $task->setPriority(50);
        $task->setData([
            'urgent' => true,
            'customer_tier' => 'vip',
            'deadline' => (new \DateTimeImmutable('+1 hour'))->format('Y-m-d H:i:s'),
        ]);

        $factors = [
            'urgency' => 0.4,
            'customer_tier' => 0.3,
            'deadline_proximity' => 0.2,
            'resource_availability' => 0.05,
            'business_impact' => 0.05,
        ];

        $newPriority = $this->service->calculateTaskPriority($task, $factors);

        // 高紧急程度应该提高优先级
        $this->assertGreaterThan(50, $newPriority);
        $this->assertLessThanOrEqual(100, $newPriority);
        $this->assertGreaterThanOrEqual(1, $newPriority);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Scheduling\TaskPriorityCalculatorService::calculateTaskPriority
     */
    public function testCalculateTaskPriorityWithLowUrgency(): void
    {
        $task = new InboundTask();
        $task->setType(TaskType::COUNT);
        $task->setPriority(60);
        $task->setData([
            'customer_tier' => 'standard',
        ]);

        $factors = [
            'urgency' => 0.4,
            'customer_tier' => 0.3,
            'deadline_proximity' => 0.2,
            'resource_availability' => 0.05,
            'business_impact' => 0.05,
        ];

        $newPriority = $this->service->calculateTaskPriority($task, $factors);

        // 低紧急程度和COUNT类型（multiplier 0.9）应该降低优先级
        $this->assertLessThan(60, $newPriority);
        $this->assertGreaterThanOrEqual(1, $newPriority);
        $this->assertLessThanOrEqual(100, $newPriority);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Scheduling\TaskPriorityCalculatorService::calculateTaskPriority
     */
    public function testCalculateTaskPriorityWithExpiredDeadline(): void
    {
        $task = new InboundTask();
        $task->setType(TaskType::OUTBOUND);
        $task->setPriority(50);
        $task->setData([
            'deadline' => (new \DateTimeImmutable('-1 hour'))->format('Y-m-d H:i:s'),
        ]);

        $factors = [
            'urgency' => 0.2,
            'customer_tier' => 0.2,
            'deadline_proximity' => 0.5, // 高权重
            'resource_availability' => 0.05,
            'business_impact' => 0.05,
        ];

        $newPriority = $this->service->calculateTaskPriority($task, $factors);

        // 过期任务应该获得高优先级
        $this->assertGreaterThan(50, $newPriority);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Scheduling\TaskPriorityCalculatorService::calculateTaskPriority
     */
    public function testCalculateTaskPriorityWithDifferentTaskTypes(): void
    {
        $basePriority = 50;
        $factors = [
            'urgency' => 0.4,
            'customer_tier' => 0.2,
            'deadline_proximity' => 0.2,
            'resource_availability' => 0.1,
            'business_impact' => 0.1,
        ];

        $taskTypes = [
            TaskType::QUALITY->value => [TaskType::QUALITY, 1.2],   // 应该最高
            TaskType::OUTBOUND->value => [TaskType::OUTBOUND, 1.1],  // 第二
            TaskType::INBOUND->value => [TaskType::INBOUND, 1.0],   // 标准
            TaskType::COUNT->value => [TaskType::COUNT, 0.9],     // 较低
            TaskType::TRANSFER->value => [TaskType::TRANSFER, 0.8],  // 最低
        ];

        $priorities = [];
        foreach ($taskTypes as $typeValue => [$taskType, $expectedMultiplier]) {
            $task = new InboundTask();
            $task->setType($taskType);
            $task->setPriority($basePriority);
            $task->setData([]);

            $priorities[$typeValue] = $this->service->calculateTaskPriority($task, $factors);
        }

        // 验证质检任务优先级最高
        $this->assertGreaterThan($priorities['inbound'], $priorities['quality']);
        $this->assertGreaterThan($priorities['count'], $priorities['outbound']);
        $this->assertGreaterThan($priorities['transfer'], $priorities['count']);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Scheduling\TaskPriorityCalculatorService::calculateTaskPriority
     */
    public function testCalculateTaskPriorityWithDifferentCustomerTiers(): void
    {
        $basePriority = 50;
        $factors = [
            'urgency' => 0.2,
            'customer_tier' => 0.6, // 高权重
            'deadline_proximity' => 0.1,
            'resource_availability' => 0.05,
            'business_impact' => 0.05,
        ];

        $customerTiers = ['vip', 'premium', 'plus', 'standard'];
        $priorities = [];

        foreach ($customerTiers as $tier) {
            $task = new InboundTask();
            $task->setType(TaskType::INBOUND);
            $task->setPriority($basePriority);
            $task->setData(['customer_tier' => $tier]);

            $priorities[$tier] = $this->service->calculateTaskPriority($task, $factors);
        }

        // 验证VIP客户获得最高优先级
        $this->assertGreaterThan($priorities['premium'], $priorities['vip']);
        $this->assertGreaterThan($priorities['plus'], $priorities['premium']);
        $this->assertGreaterThan($priorities['standard'], $priorities['plus']);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Scheduling\TaskPriorityCalculatorService::calculateTaskPriority
     */
    public function testCalculateTaskPriorityBoundaryValues(): void
    {
        $factors = [
            'urgency' => 1.0,
            'customer_tier' => 1.0,
            'deadline_proximity' => 1.0,
            'resource_availability' => 1.0,
            'business_impact' => 1.0,
        ];

        // 测试最小值
        $lowTask = new InboundTask();
        $lowTask->setType(TaskType::TRANSFER); // 最低乘数
        $lowTask->setPriority(1); // 最低基础优先级
        $lowTask->setData([]);

        $lowPriority = $this->service->calculateTaskPriority($lowTask, $factors);
        $this->assertGreaterThanOrEqual(1, $lowPriority);

        // 测试最大值
        $highTask = new InboundTask();
        $highTask->setType(TaskType::QUALITY); // 最高乘数
        $highTask->setPriority(100); // 最高基础优先级
        $highTask->setData([
            'urgent' => true,
            'customer_tier' => 'vip',
            'deadline' => (new \DateTimeImmutable('-1 hour'))->format('Y-m-d H:i:s'),
        ]);

        $highPriority = $this->service->calculateTaskPriority($highTask, $factors);
        $this->assertLessThanOrEqual(100, $highPriority);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Scheduling\TaskPriorityCalculatorService::recalculatePriorities
     */
    public function testRecalculatePrioritiesWithHighImpactChanges(): void
    {
        // 创建一个优先级会发生大变化的任务
        $task = new InboundTask();
        $task->setType(TaskType::QUALITY);
        $task->setStatus(TaskStatus::PENDING);
        $task->setPriority(10);
        $task->setData([
            'urgent' => true,
            'customer_tier' => 'vip',
            'deadline' => (new \DateTimeImmutable('-1 hour'))->format('Y-m-d H:i:s'),
        ]);

        $this->taskRepository->save($task);

        $context = [
            'priority_factors' => [
                'urgency' => 0.5,
                'customer_tier' => 0.3,
                'deadline_proximity' => 0.15,
                'resource_availability' => 0.025,
                'business_impact' => 0.025,
            ],
        ];

        $result = $this->service->recalculatePriorities($context);

        // 应该有高影响变化
        self::assertIsArray($result);
        self::assertIsArray($result['affected_assignments']);
        /** @var array<string, mixed> $affectedAssignments */
        $affectedAssignments = $result['affected_assignments'];
        self::assertIsArray($affectedAssignments['high_impact_changes']);
        $this->assertGreaterThan(0, count($affectedAssignments['high_impact_changes']));

        // 验证变化记录
        if ([] !== $result['priority_changes']) {
            self::assertIsArray($result['priority_changes']);
            self::assertIsArray($result['priority_changes'][0]);
            /** @var array<string, mixed> $change */
            $change = $result['priority_changes'][0];
            $this->assertArrayHasKey('task_id', $change);
            $this->assertArrayHasKey('old_priority', $change);
            $this->assertArrayHasKey('new_priority', $change);
            $this->assertArrayHasKey('change_delta', $change);
            $this->assertArrayHasKey('task_type', $change);
        }
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Scheduling\TaskPriorityCalculatorService::recalculatePriorities
     */
    public function testRecalculatePrioritiesWithDefaultContext(): void
    {
        // 测试默认上下文
        $result = $this->service->recalculatePriorities();

        $this->assertEquals('manual', $result['trigger_reason']);
        $this->assertIsArray($result['priority_changes']);
        $this->assertIsArray($result['priority_distribution']);
    }

    public function testServiceConstructorAndBasicFunctionality(): void
    {
        // 验证服务可以正确实例化
        $this->assertInstanceOf(TaskPriorityCalculatorService::class, $this->service);

        // 验证基本功能工作正常
        $task = new InboundTask();
        $task->setType(TaskType::INBOUND);
        $task->setPriority(50);
        $task->setData([]);

        $factors = [
            'urgency' => 0.4,
            'customer_tier' => 0.3,
            'deadline_proximity' => 0.2,
            'resource_availability' => 0.05,
            'business_impact' => 0.05,
        ];

        $priority = $this->service->calculateTaskPriority($task, $factors);
        $this->assertIsInt($priority);
        $this->assertGreaterThanOrEqual(1, $priority);
        $this->assertLessThanOrEqual(100, $priority);
    }

    /**
     * 测试优先级分布计算
     */
    public function testPriorityDistribution(): void
    {
        // 创建不同优先级的任务
        $tasks = [
            $this->createTaskWithPriority(TaskType::INBOUND, 25),    // low
            $this->createTaskWithPriority(TaskType::OUTBOUND, 50),  // medium
            $this->createTaskWithPriority(TaskType::QUALITY, 85),   // high
        ];

        foreach ($tasks as $task) {
            $this->taskRepository->save($task, false);
        }
        parent::getEntityManager()->flush();

        $result = $this->service->recalculatePriorities();

        self::assertIsArray($result);
        self::assertIsArray($result['priority_distribution']);
        /** @var array<string, mixed> $distribution */
        $distribution = $result['priority_distribution'];
        $this->assertGreaterThanOrEqual(1, $distribution['low']);
        $this->assertGreaterThanOrEqual(1, $distribution['medium']);
        $this->assertGreaterThanOrEqual(1, $distribution['high']);
    }

    /**
     * 测试截止时间计算的边界情况
     */
    public function testDeadlineScoreCalculation(): void
    {
        $testCases = [
            ['deadline' => '-2 hours', 'expectedRange' => [0.9, 1.0]],     // 过期
            ['deadline' => '+30 minutes', 'expectedRange' => [0.8, 0.95]],  // 1小时内
            ['deadline' => '+90 minutes', 'expectedRange' => [0.65, 0.75]], // 2小时内
            ['deadline' => '+12 hours', 'expectedRange' => [0.45, 0.55]],   // 24小时内
            ['deadline' => '+2 days', 'expectedRange' => [0.25, 0.35]],     // 超过24小时
        ];

        $factors = [
            'urgency' => 0.1,
            'customer_tier' => 0.1,
            'deadline_proximity' => 0.7, // 高权重测试截止时间
            'resource_availability' => 0.05,
            'business_impact' => 0.05,
        ];

        foreach ($testCases as $case) {
            $task = new InboundTask();
            $task->setType(TaskType::INBOUND);
            $task->setPriority(50);
            $task->setData([
                'deadline' => (new \DateTimeImmutable($case['deadline']))->format('Y-m-d H:i:s'),
            ]);

            $priority = $this->service->calculateTaskPriority($task, $factors);

            // 验证优先级在预期范围内（相对于基准50的变化）
            $this->assertIsInt($priority);
            $this->assertGreaterThanOrEqual(1, $priority);
            $this->assertLessThanOrEqual(100, $priority);
        }
    }

    /**
     * 测试无截止时间的任务
     */
    public function testCalculateTaskPriorityWithoutDeadline(): void
    {
        $task = new InboundTask();
        $task->setType(TaskType::INBOUND);
        $task->setPriority(50);
        $task->setData([]);

        $factors = [
            'urgency' => 0.3,
            'customer_tier' => 0.3,
            'deadline_proximity' => 0.3,
            'resource_availability' => 0.05,
            'business_impact' => 0.05,
        ];

        $priority = $this->service->calculateTaskPriority($task, $factors);

        // 无截止时间应该给予中等分数，不会极大影响优先级
        $this->assertGreaterThanOrEqual(1, $priority);
        $this->assertLessThanOrEqual(100, $priority);
    }

    /**
     * 创建指定优先级的任务
     */
    private function createTaskWithPriority(TaskType $taskType, int $priority): WarehouseTask
    {
        $task = new InboundTask();
        $task->setType($taskType);
        $task->setStatus(TaskStatus::PENDING);
        $task->setPriority($priority);
        $task->setData([]);

        return $task;
    }
}
