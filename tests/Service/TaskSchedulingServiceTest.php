<?php

namespace Tourze\WarehouseOperationBundle\Tests\Service;

use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\WarehouseOperationBundle\Entity\InboundTask;
use Tourze\WarehouseOperationBundle\Entity\WarehouseTask;
use Tourze\WarehouseOperationBundle\Entity\WorkerSkill;
use Tourze\WarehouseOperationBundle\Enum\TaskStatus;
use Tourze\WarehouseOperationBundle\Enum\TaskType;
use Tourze\WarehouseOperationBundle\Event\TaskAssignedEvent;
use Tourze\WarehouseOperationBundle\Repository\WarehouseTaskRepository;
use Tourze\WarehouseOperationBundle\Repository\WorkerSkillRepository;
use Tourze\WarehouseOperationBundle\Service\TaskSchedulingService;

/**
 * TaskSchedulingService 单元测试
 *
 * @internal
 */
#[CoversClass(TaskSchedulingService::class)]
#[RunTestsInSeparateProcesses]
class TaskSchedulingServiceTest extends AbstractIntegrationTestCase
{
    private WarehouseTaskRepository $taskRepository;

    private WorkerSkillRepository $workerSkillRepository;

    private EventDispatcherInterface $eventDispatcher;

    private TaskSchedulingService $service;

    /**
     * 测试空任务列表的批量调度
     */
    public function testScheduleTaskBatchWithEmptyTasks(): void
    {
        $result = $this->service->scheduleTaskBatch([]);

        self::assertIsArray($result);
        self::assertArrayHasKey('assignments', $result);
        self::assertArrayHasKey('unassigned', $result);
        self::assertArrayHasKey('statistics', $result);
        self::assertArrayHasKey('recommendations', $result);

        // 验证空任务列表结果
        self::assertEmpty($result['assignments']);
        self::assertEmpty($result['unassigned']);

        /** @var array<string, mixed> $statistics */
        $statistics = $result['statistics'];
        self::assertEquals(0, $statistics['total_tasks']);
        self::assertEquals(0, $statistics['assigned_count']);
        self::assertEquals(0.0, $statistics['assignment_rate']);
    }

    /**
     * 测试单个任务的批量调度
     */
    public function testScheduleTaskBatchWithSingleTask(): void
    {
        // 创建测试任务
        $task = $this->createTestTask();

        // 模拟作业员技能数据
        $workerSkill = $this->createTestWorkerSkill();

        $this->workerSkillRepository->expects($this->once())
            ->method('findBy')
            ->with(['isActive' => true])
            ->willReturn([$workerSkill])
        ;

        $this->taskRepository->expects($this->once())
            ->method('count')
            ->willReturn(2) // 模拟当前工作负载
        ;

        // 模拟事件派发
        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(self::isInstanceOf(TaskAssignedEvent::class))
        ;

        $result = $this->service->scheduleTaskBatch([$task]);

        self::assertIsArray($result);
        /** @var array<string, mixed> $statistics */
        $statistics = $result['statistics'];
        self::assertEquals(1, $statistics['total_tasks']);
        self::assertGreaterThan(0, $statistics['processing_time_ms']);
    }

    /**
     * 创建测试任务
     */
    private function createTestTask(int $priority = 50): InboundTask
    {
        $task = new InboundTask();
        $task->setType(TaskType::INBOUND);
        $task->setStatus(TaskStatus::PENDING);
        $task->setPriority($priority);
        $task->setData(['test' => 'data']);

        // 使用setId方法设置ID，模拟已保存的实体
        $task->setId(rand(1, 1000));

        return $task;
    }

    /**
     * 创建测试作业员技能
     */
    private function createTestWorkerSkill(int $workerId = 101, string $name = 'TestWorker'): WorkerSkill
    {
        $skill = new WorkerSkill();
        $skill->setWorkerId($workerId);
        $skill->setWorkerName($name);
        $skill->setSkillCategory('picking');
        $skill->setSkillLevel(7);
        $skill->setSkillScore(80);
        $skill->setIsActive(true);

        // 使用反射设置私有ID属性
        $reflection = new \ReflectionClass($skill);
        $idProperty = $reflection->getProperty('id');
        $idProperty->setAccessible(true);
        $idProperty->setValue($skill, rand(1, 1000));

        return $skill;
    }

    /**
     * 测试多个任务的批量调度
     */
    public function testScheduleTaskBatchWithMultipleTasks(): void
    {
        // 创建多个测试任务
        $tasks = [
            $this->createTestTask(50), // 中等优先级
            $this->createTestTask(80), // 高优先级
            $this->createTestTask(20), // 低优先级
        ];

        // 模拟作业员技能数据
        $workerSkills = [
            $this->createTestWorkerSkill(101, 'Worker1'),
            $this->createTestWorkerSkill(102, 'Worker2'),
        ];

        $this->workerSkillRepository->expects($this->once())
            ->method('findBy')
            ->with(['isActive' => true])
            ->willReturn($workerSkills)
        ;

        $this->taskRepository->expects($this->any())
            ->method('count')
            ->willReturn(1) // 模拟较低工作负载
        ;

        $this->eventDispatcher->expects($this->atLeast(1))
            ->method('dispatch')
        ;

        $result = $this->service->scheduleTaskBatch($tasks);

        /** @var array<string, mixed> $statistics */
        $statistics = $result['statistics'];
        self::assertEquals(3, $statistics['total_tasks']);
        self::assertGreaterThanOrEqual(0, $statistics['assigned_count']);
        self::assertLessThanOrEqual(3, $statistics['assigned_count']);
    }

    /**
     * 测试带约束条件的任务调度
     */
    public function testScheduleTaskBatchWithConstraints(): void
    {
        $task = $this->createTestTask();
        $constraints = [
            'max_tasks_per_worker' => 5,
            'worker_availability' => [101 => 'available'],
            'exclude_workers' => [102],
        ];

        $workerSkill = $this->createTestWorkerSkill();
        $this->workerSkillRepository->expects($this->once())
            ->method('findBy')
            ->willReturn([$workerSkill])
        ;

        $this->taskRepository->expects($this->once())
            ->method('count')
            ->willReturn(3)
        ;

        $result = $this->service->scheduleTaskBatch([$task], $constraints);

        self::assertIsArray($result);
        self::assertArrayHasKey('statistics', $result);
    }

    /**
     * 测试优先级重新计算
     */
    public function testRecalculatePriorities(): void
    {
        $tasks = [$this->createTestTask(30)];

        $this->taskRepository->expects($this->once())
            ->method('findByStatus')
            ->with(TaskStatus::PENDING, 100)
            ->willReturn($tasks)
        ;

        // 不期待save和flush调用，因为在简单测试中优先级可能不会改变
        $this->taskRepository->expects($this->any())
            ->method('save')
        ;

        $context = [
            'trigger_reason' => 'manual',
            'priority_factors' => ['urgency' => 0.5],
        ];

        $result = $this->service->recalculatePriorities($context);

        self::assertIsArray($result);
        self::assertArrayHasKey('updated_count', $result);
        self::assertArrayHasKey('priority_changes', $result);
        self::assertArrayHasKey('trigger_reason', $result);
        self::assertEquals('manual', $result['trigger_reason']);
        self::assertIsInt($result['updated_count']);
        self::assertIsArray($result['priority_changes']);
    }

    /**
     * 测试基于技能的作业员分配
     */
    public function testAssignWorkerBySkill(): void
    {
        $task = $this->createTestTask();

        // 设置任务需要picking技能
        $task->setData(['task_type' => 'outbound']);

        $workerSkill = $this->createTestWorkerSkill();
        $workerSkill->setSkillCategory('picking');
        $workerSkill->setSkillLevel(8);
        $workerSkill->setSkillScore(85);

        $this->workerSkillRepository->expects($this->once())
            ->method('findWorkersBySkills')
            ->willReturn([$workerSkill])
        ;

        $this->taskRepository->expects($this->once())
            ->method('count')
            ->willReturn(2)
        ;

        $result = $this->service->assignWorkerBySkill($task);

        self::assertIsArray($result);
        self::assertArrayHasKey('worker_id', $result);
        self::assertArrayHasKey('match_score', $result);
        self::assertArrayHasKey('assignment_reason', $result);
        self::assertArrayHasKey('skill_analysis', $result);
        self::assertEquals(101, $result['worker_id']);
        self::assertGreaterThan(0, $result['match_score']);
    }

    /**
     * 测试无匹配技能的作业员分配
     */
    public function testAssignWorkerBySkillWithNoMatchingSkills(): void
    {
        $task = $this->createTestTask();

        $this->workerSkillRepository->expects($this->once())
            ->method('findWorkersBySkills')
            ->willReturn([])
        ;

        $result = $this->service->assignWorkerBySkill($task);

        self::assertNull($result);
    }

    /**
     * 测试调度队列状态获取
     */
    public function testGetSchedulingQueueStatus(): void
    {
        $statistics = [
            TaskStatus::PENDING->value => 10,
            TaskStatus::ASSIGNED->value => 5,
            TaskStatus::IN_PROGRESS->value => 3,
        ];

        $this->taskRepository->expects($this->once())
            ->method('getTaskStatistics')
            ->willReturn($statistics)
        ;

        $this->workerSkillRepository->expects($this->once())
            ->method('count')
            ->with(['isActive' => true])
            ->willReturn(5)
        ;

        $this->taskRepository->expects($this->once())
            ->method('findByStatus')
            ->with(TaskStatus::PENDING, 100)
            ->willReturn([])
        ;

        $this->taskRepository->expects($this->once())
            ->method('countByStatus')
            ->with(TaskStatus::PENDING)
            ->willReturn(10)
        ;

        $mockQueryBuilder = $this->createMock(QueryBuilder::class);
        $mockQuery = $this->createMock(Query::class);

        $mockQueryBuilder->method('getQuery')->willReturn($mockQuery);
        $mockQuery->method('getScalarResult')->willReturn([['assignedWorker' => 101], ['assignedWorker' => 102]]);

        $this->taskRepository->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($mockQueryBuilder)
        ;

        $result = $this->service->getSchedulingQueueStatus();

        self::assertIsArray($result);
        self::assertArrayHasKey('pending_count', $result);
        self::assertArrayHasKey('active_count', $result);
        self::assertArrayHasKey('worker_utilization', $result);
        self::assertArrayHasKey('queue_health', $result);
        self::assertEquals(10, $result['pending_count']);
        self::assertEquals(8, $result['active_count']); // 5 + 3
    }

    /**
     * 测试调度优化分析
     */
    public function testAnalyzeSchedulingOptimization(): void
    {
        $criteria = [
            'time_range' => ['hours' => 12],
            'task_types' => ['inbound', 'outbound'],
        ];

        $result = $this->service->analyzeSchedulingOptimization($criteria);

        self::assertIsArray($result);
        self::assertArrayHasKey('efficiency_score', $result);
        self::assertArrayHasKey('optimization_suggestions', $result);
        self::assertArrayHasKey('resource_utilization', $result);
        self::assertArrayHasKey('performance_trends', $result);
        self::assertArrayHasKey('analysis_period', $result);

        /** @var array<string, mixed> $efficiencyScore */
        $efficiencyScore = $result['efficiency_score'];

        // 验证分析周期
        /** @var array<string, mixed> $analysisPeriod */
        $analysisPeriod = $result['analysis_period'];
        self::assertEquals($criteria, $analysisPeriod['criteria']);
    }

    /**
     * 测试紧急任务插入处理
     */
    public function testHandleUrgentTaskInsertion(): void
    {
        $urgentTask = $this->createTestTask(100);
        $urgencyLevel = [
            'priority' => 95,
            'max_delay_minutes' => 15,
            'preempt_allowed' => false,
        ];

        // 模拟无法直接分配
        $this->workerSkillRepository->expects($this->once())
            ->method('findWorkersBySkills')
            ->willReturn([])
        ;

        $result = $this->service->handleUrgentTaskInsertion($urgentTask, $urgencyLevel);

        self::assertIsArray($result);
        self::assertArrayHasKey('assigned', $result);
        self::assertArrayHasKey('impact_analysis', $result);
        self::assertFalse($result['assigned']);
        self::assertEquals(95, $urgentTask->getPriority());
    }

    /**
     * 测试批量任务重新分配
     */
    public function testBatchReassignTasks(): void
    {
        $affectedTaskIds = [1, 2, 3];
        $reason = 'worker_unavailable';

        $tasks = [
            $this->createTestTask(50),
            $this->createTestTask(60),
            $this->createTestTask(70),
        ];

        $this->taskRepository->expects($this->once())
            ->method('findBy')
            ->with(['id' => $affectedTaskIds])
            ->willReturn($tasks)
        ;

        // 模拟重新分配失败
        $this->workerSkillRepository->expects($this->atLeast(1))
            ->method('findWorkersBySkills')
            ->willReturn([])
        ;

        // 不需要mock getEntityManager，实际测试中不会调用flush

        $result = $this->service->batchReassignTasks($affectedTaskIds, $reason);

        self::assertIsArray($result);
        self::assertArrayHasKey('successful_reassignments', $result);
        self::assertArrayHasKey('failed_reassignments', $result);
        self::assertArrayHasKey('new_assignments', $result);
        self::assertArrayHasKey('estimated_delay', $result);
    }

    /**
     * 测试空任务ID列表的批量重新分配
     */
    public function testBatchReassignTasksWithEmptyIds(): void
    {
        $result = $this->service->batchReassignTasks([], 'test');

        self::assertEquals(0, $result['successful_reassignments']);
        self::assertEmpty($result['failed_reassignments']);
        self::assertEmpty($result['new_assignments']);
        self::assertEquals(0, $result['estimated_delay']);
    }

    protected function onSetUp(): void
    {
        $this->taskRepository = $this->createMock(WarehouseTaskRepository::class);
        $this->workerSkillRepository = $this->createMock(WorkerSkillRepository::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->service = parent::getService(TaskSchedulingService::class);
    }
}
