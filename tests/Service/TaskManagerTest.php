<?php

namespace Tourze\WarehouseOperationBundle\Tests\Service;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\WarehouseOperationBundle\Entity\InboundTask;
use Tourze\WarehouseOperationBundle\Entity\OutboundTask;
use Tourze\WarehouseOperationBundle\Entity\WarehouseTask;
use Tourze\WarehouseOperationBundle\Enum\TaskStatus;
use Tourze\WarehouseOperationBundle\Enum\TaskType;
use Tourze\WarehouseOperationBundle\Event\TaskAssignedEvent;
use Tourze\WarehouseOperationBundle\Event\TaskCompletedEvent;
use Tourze\WarehouseOperationBundle\Event\TaskCreatedEvent;
use Tourze\WarehouseOperationBundle\Exception\TaskNotFoundException;
use Tourze\WarehouseOperationBundle\Exception\TaskStatusException;
use Tourze\WarehouseOperationBundle\Repository\WarehouseTaskRepository;
use Tourze\WarehouseOperationBundle\Service\ConfigService;
use Tourze\WarehouseOperationBundle\Service\TaskManager;
use Tourze\WarehouseOperationBundle\Service\TaskSchedulingServiceInterface;

/**
 * @internal
 */
#[CoversClass(TaskManager::class)]
#[RunTestsInSeparateProcesses]
class TaskManagerTest extends AbstractIntegrationTestCase
{
    private TaskManager $taskManager;

    private EventDispatcherInterface&MockObject $eventDispatcher;

    private WarehouseTaskRepository&MockObject $taskRepository;

    private ConfigService&MockObject $configService;

    private TaskSchedulingServiceInterface&MockObject $schedulingService;

    private LoggerInterface&MockObject $logger;

    protected function onSetUp(): void
    {
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->taskRepository = $this->createMock(WarehouseTaskRepository::class);
        $this->configService = $this->createMock(ConfigService::class);
        $this->schedulingService = $this->createMock(TaskSchedulingServiceInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->taskManager = parent::getService(TaskManager::class);
    }

    public function testCreateTaskWithInboundTypeShouldCreateInboundTask(): void
    {
        $data = [
            'purchase_order_id' => 'PO123',
            'supplier_id' => 456,
            'expected_quantity' => 100,
            'location_id' => 789,
        ];

        // 按结果断言，不对 EntityManager 调用进行期望设置

        $this->eventDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with(self::isInstanceOf(TaskCreatedEvent::class))
        ;

        $task = $this->taskManager->createTask(TaskType::INBOUND, $data);

        $this->assertInstanceOf(InboundTask::class, $task);
        $this->assertSame(TaskType::INBOUND, $task->getType());
        $this->assertSame(TaskStatus::PENDING, $task->getStatus());
        $this->assertEquals($data, $task->getData());
    }

    public function testCreateTaskWithOutboundTypeShouldCreateOutboundTask(): void
    {
        $data = [
            'sales_order_id' => 'SO456',
            'customer_id' => 789,
            'required_quantity' => 50,
            'location_id' => 123,
        ];

        // 按结果断言，不对 EntityManager 调用进行期望设置

        $this->eventDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with(self::isInstanceOf(TaskCreatedEvent::class))
        ;

        $task = $this->taskManager->createTask(TaskType::OUTBOUND, $data);

        $this->assertInstanceOf(OutboundTask::class, $task);
        $this->assertSame(TaskType::OUTBOUND, $task->getType());
        $this->assertSame(TaskStatus::PENDING, $task->getStatus());
        $this->assertEquals($data, $task->getData());
    }

    public function testAssignTaskShouldUpdateTaskStatusAndTriggerEvent(): void
    {
        $task = new InboundTask();
        $task->setStatus(TaskStatus::PENDING);

        $this->taskRepository
            ->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($task)
        ;

        // 按结果断言，不对 EntityManager 调用进行期望设置

        $this->eventDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with(self::isInstanceOf(TaskAssignedEvent::class))
        ;

        $result = $this->taskManager->assignTask(1, 123);

        $this->assertTrue($result);
        $this->assertSame(TaskStatus::ASSIGNED, $task->getStatus());
        $this->assertSame(123, $task->getAssignedWorker());
    }

    public function testAssignTaskWithInvalidTaskIdShouldThrowException(): void
    {
        $this->taskRepository
            ->expects($this->once())
            ->method('find')
            ->with(999)
            ->willReturn(null)
        ;

        $this->expectException(TaskNotFoundException::class);
        $this->expectExceptionMessage('任务未找到: 999');

        $this->taskManager->assignTask(999, 123);
    }

    public function testAssignTaskWithInvalidStatusShouldThrowException(): void
    {
        $task = new InboundTask();
        $task->setStatus(TaskStatus::COMPLETED);

        $this->taskRepository
            ->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($task)
        ;

        $this->expectException(TaskStatusException::class);
        $this->expectExceptionMessage('任务状态不允许分配: COMPLETED');

        $this->taskManager->assignTask(1, 123);
    }

    public function testCompleteTaskShouldUpdateStatusAndTriggerEvent(): void
    {
        $task = new InboundTask();
        $task->setStatus(TaskStatus::IN_PROGRESS);

        $result = ['processed_quantity' => 100, 'quality_pass' => true];

        $this->taskRepository
            ->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($task)
        ;

        // 按结果断言，不对 EntityManager 调用进行期望设置

        $this->eventDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with(self::isInstanceOf(TaskCompletedEvent::class))
        ;

        $success = $this->taskManager->completeTask(1, $result);

        $this->assertTrue($success);
        $this->assertSame(TaskStatus::COMPLETED, $task->getStatus());
        $this->assertEquals($result, $task->getData());
    }

    public function testPauseTaskShouldUpdateStatusWithReason(): void
    {
        $task = new InboundTask();
        $task->setStatus(TaskStatus::IN_PROGRESS);

        $this->taskRepository
            ->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($task)
        ;

        // 按结果断言，不对 EntityManager 调用进行期望设置

        $result = $this->taskManager->pauseTask(1, '设备故障');

        $this->assertTrue($result);
        $this->assertSame(TaskStatus::PAUSED, $task->getStatus());
        $this->assertSame('设备故障', $task->getNotes());
    }

    public function testResumeTaskShouldRestorePreviousStatus(): void
    {
        $task = new InboundTask();
        $task->setStatus(TaskStatus::PAUSED);
        $task->setData(['previous_status' => TaskStatus::IN_PROGRESS->value]);

        $this->taskRepository
            ->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($task)
        ;

        // 按结果断言，不对 EntityManager 调用进行期望设置

        $result = $this->taskManager->resumeTask(1);

        $this->assertTrue($result);
        $this->assertSame(TaskStatus::IN_PROGRESS, $task->getStatus());
    }

    public function testCancelTaskShouldUpdateStatusWithReason(): void
    {
        $task = new InboundTask();
        $task->setStatus(TaskStatus::PENDING);

        $this->taskRepository
            ->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($task)
        ;

        // 按结果断言，不对 EntityManager 调用进行期望设置

        $result = $this->taskManager->cancelTask(1, '订单取消');

        $this->assertTrue($result);
        $this->assertSame(TaskStatus::CANCELLED, $task->getStatus());
        $this->assertSame('订单取消', $task->getNotes());
    }

    public function testFindTasksByStatusShouldReturnTaskArray(): void
    {
        $tasks = [new InboundTask(), new OutboundTask()];

        $this->taskRepository
            ->expects($this->once())
            ->method('findByStatus')
            ->with(TaskStatus::PENDING, 10)
            ->willReturn($tasks)
        ;

        $result = $this->taskManager->findTasksByStatus(TaskStatus::PENDING, 10);

        $this->assertEquals($tasks, $result);
    }

    public function testGetTaskTraceShouldReturnTraceArray(): void
    {
        $task = new InboundTask();
        $traceData = [
            ['action' => 'created', 'timestamp' => '2025-08-27 10:00:00'],
            ['action' => 'assigned', 'timestamp' => '2025-08-27 10:05:00'],
        ];

        $this->taskRepository
            ->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($task)
        ;

        $this->taskRepository
            ->expects($this->once())
            ->method('getTaskTrace')
            ->with(1)
            ->willReturn($traceData)
        ;

        $result = $this->taskManager->getTaskTrace(1);

        $this->assertEquals($traceData, $result);
    }

    public function testFindTimeoutTasksShouldReturnTaskArray(): void
    {
        $tasks = [new InboundTask(), new OutboundTask()];

        $this->configService
            ->expects($this->once())
            ->method('getTaskTimeout')
            ->willReturn(60) // 60 分钟超时
        ;

        $this->taskRepository
            ->expects($this->once())
            ->method('findTimeoutTasks')
            ->with(
                self::callback(function (\DateTime $date) {
                    // 验证传入的日期大约是60分钟前
                    $now = new \DateTime();
                    $diff = $now->getTimestamp() - $date->getTimestamp();

                    return $diff >= 3590 && $diff <= 3610; // 允许10秒误差
                }),
                null,
                null
            )
            ->willReturn($tasks)
        ;

        $result = $this->taskManager->findTimeoutTasks();

        $this->assertEquals($tasks, $result);
    }

    public function testFindTimeoutTasksWithLimitShouldPassLimit(): void
    {
        $tasks = [new InboundTask()];
        $limit = 5;

        $this->configService
            ->expects($this->once())
            ->method('getTaskTimeout')
            ->willReturn(30)
        ;

        $this->taskRepository
            ->expects($this->once())
            ->method('findTimeoutTasks')
            ->with(
                self::isInstanceOf(\DateTime::class),
                null,
                $limit
            )
            ->willReturn($tasks)
        ;

        $result = $this->taskManager->findTimeoutTasks($limit);

        $this->assertEquals($tasks, $result);
    }

    /**
     * 测试智能批量任务分配功能
     */
    public function testAssignTasksIntelligentlyWithSchedulingService(): void
    {
        $pendingTask = new InboundTask();
        $pendingTask->setType(TaskType::INBOUND);
        $pendingTask->setStatus(TaskStatus::PENDING);
        $pendingTask->setPriority(50);

        $tasks = [$pendingTask];
        $constraints = ['max_tasks_per_worker' => 5];

        $this->taskRepository->expects($this->once())
            ->method('findByStatus')
            ->with(TaskStatus::PENDING, null)
            ->willReturn($tasks)
        ;

        $schedulingResult = [
            'assignments' => [
                ['task_id' => 1, 'worker_id' => 101, 'match_score' => 85.5],
            ],
            'unassigned' => [],
            'statistics' => [
                'total_tasks' => 1,
                'assigned_count' => 1,
                'assignment_rate' => 1.0,
                'processing_time_ms' => 150,
            ],
            'recommendations' => ['message' => '分配成功'],
        ];

        $this->schedulingService->expects($this->once())
            ->method('scheduleTaskBatch')
            ->with($tasks, $constraints)
            ->willReturn($schedulingResult)
        ;

        // Mock task retrieval for assignment application
        $this->taskRepository->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($pendingTask)
        ;

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(self::isInstanceOf(TaskAssignedEvent::class))
        ;

        // 按结果断言，不对 EntityManager 调用进行期望设置

        $this->logger->expects($this->once())
            ->method('info')
            ->with('智能任务分配完成', self::isArray())
        ;

        $result = $this->taskManager->assignTasksIntelligently($constraints);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('assignments', $result);
        $this->assertArrayHasKey('statistics', $result);
        self::assertIsArray($result['statistics']);
        $this->assertArrayHasKey('assigned_count', $result['statistics']);
        $this->assertSame(1, $result['statistics']['assigned_count']);
        $this->assertSame(TaskStatus::ASSIGNED, $pendingTask->getStatus());
        $this->assertSame(101, $pendingTask->getAssignedWorker());
    }

    /**
     * 测试无调度服务时的基础模式回退
     */
    public function testAssignTasksIntelligentlyWithoutSchedulingService(): void
    {
        $taskManager = parent::getService(TaskManager::class);

        $pendingTask = new InboundTask();
        $pendingTask->setType(TaskType::INBOUND);
        $pendingTask->setStatus(TaskStatus::PENDING);
        $pendingTask->setPriority(50);

        $tasks = [$pendingTask];

        $this->taskRepository->expects($this->once())
            ->method('findByStatus')
            ->with(TaskStatus::PENDING, 50)
            ->willReturn($tasks)
        ;

        $this->logger->expects($this->once())
            ->method('warning')
            ->with('任务调度服务未配置，回退到基础分配模式')
        ;

        $result = $taskManager->assignTasksIntelligently();

        $this->assertIsArray($result);
        $this->assertEmpty($result['assignments']);
        $this->assertSame($tasks, $result['unassigned']);
        self::assertIsArray($result['statistics']);
        $this->assertArrayHasKey('assigned_count', $result['statistics']);
        $this->assertSame(0, $result['statistics']['assigned_count']);
    }

    /**
     * 测试任务优先级重算功能
     */
    public function testRecalculateTaskPriorities(): void
    {
        $context = ['trigger_reason' => 'workload_change', 'priority_factors' => ['urgency' => 0.7]];
        $expectedResult = [
            'updated_count' => 5,
            'priority_changes' => [
                1 => ['old' => 50, 'new' => 65],
                2 => ['old' => 30, 'new' => 40],
            ],
        ];

        $this->schedulingService->expects($this->once())
            ->method('recalculatePriorities')
            ->with($context)
            ->willReturn($expectedResult)
        ;

        $this->logger->expects($this->once())
            ->method('info')
            ->with('任务优先级重算完成', self::isArray())
        ;

        $result = $this->taskManager->recalculateTaskPriorities($context);

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * 测试基于技能的作业员分配
     */
    public function testAssignWorkerBySkill(): void
    {
        $taskId = 123;
        $task = new InboundTask();
        $task->setType(TaskType::INBOUND);
        $task->setStatus(TaskStatus::PENDING);
        $task->setPriority(50);

        $assignmentResult = [
            'worker_id' => 101,
            'match_score' => 87.5,
            'assignment_reason' => 'Best skill match',
            'skill_analysis' => ['required' => ['picking'], 'matched' => ['picking']],
        ];

        $this->taskRepository->expects($this->once())
            ->method('find')
            ->with($taskId)
            ->willReturn($task)
        ;

        $this->schedulingService->expects($this->once())
            ->method('assignWorkerBySkill')
            ->with($task)
            ->willReturn($assignmentResult)
        ;

        // 按结果断言，不对 EntityManager 调用进行期望设置

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(self::isInstanceOf(TaskAssignedEvent::class))
        ;

        $this->logger->expects($this->once())
            ->method('info')
            ->with('技能匹配分配成功', self::isArray())
        ;

        $result = $this->taskManager->assignWorkerBySkill($taskId);

        $this->assertEquals($assignmentResult, $result);
        $this->assertSame(TaskStatus::ASSIGNED, $task->getStatus());
        $this->assertSame(101, $task->getAssignedWorker());
        $this->assertInstanceOf(\DateTimeImmutable::class, $task->getAssignedAt());
    }

    /**
     * 测试无匹配技能时的处理
     */
    public function testAssignWorkerBySkillWithNoMatch(): void
    {
        $taskId = 123;
        $task = new InboundTask();
        $task->setType(TaskType::INBOUND);
        $task->setStatus(TaskStatus::PENDING);

        $this->taskRepository->expects($this->once())
            ->method('find')
            ->with($taskId)
            ->willReturn($task)
        ;

        $this->schedulingService->expects($this->once())
            ->method('assignWorkerBySkill')
            ->with($task)
            ->willReturn(null)
        ;

        $this->logger->expects($this->once())
            ->method('info')
            ->with('未找到合适的技能匹配作业员', ['task_id' => $taskId])
        ;

        $result = $this->taskManager->assignWorkerBySkill($taskId);

        $this->assertNull($result);
    }

    /**
     * 测试获取调度队列状态
     */
    public function testGetSchedulingQueueStatus(): void
    {
        $expectedStatus = [
            'pending_count' => 15,
            'active_count' => 8,
            'worker_utilization' => [101 => 0.8, 102 => 0.6],
            'queue_health' => 'healthy',
        ];

        $this->schedulingService->expects($this->once())
            ->method('getSchedulingQueueStatus')
            ->willReturn($expectedStatus)
        ;

        $result = $this->taskManager->getSchedulingQueueStatus();

        $this->assertEquals($expectedStatus, $result);
    }

    /**
     * 测试无调度服务时的基础队列状态
     */
    public function testGetSchedulingQueueStatusWithoutService(): void
    {
        $taskManager = parent::getService(TaskManager::class);

        // 简化测试：直接mock返回值映射
        $this->taskRepository->method('countByStatus')
            ->willReturnCallback(function (TaskStatus $status) {
                return match ($status) {
                    TaskStatus::PENDING => 10,
                    TaskStatus::ASSIGNED => 3,
                    TaskStatus::IN_PROGRESS => 2,
                    default => 0,
                };
            })
        ;

        $result = $taskManager->getSchedulingQueueStatus();

        $this->assertSame(10, $result['pending_count']);
        $this->assertSame(5, $result['active_count']); // 3 + 2
        $this->assertSame('healthy', $result['queue_health']);
        $this->assertArrayHasKey('message', $result);
    }

    /**
     * 测试紧急任务处理
     */
    public function testHandleUrgentTask(): void
    {
        $taskId = 456;
        $urgencyLevel = ['priority' => 95, 'max_delay_minutes' => 15];

        $task = new InboundTask();
        $task->setType(TaskType::INBOUND);
        $task->setStatus(TaskStatus::PENDING);
        $task->setPriority(50);

        $expectedResult = [
            'assigned' => true,
            'impact_analysis' => ['displaced_tasks' => 2],
        ];

        $this->taskRepository->expects($this->once())
            ->method('find')
            ->with($taskId)
            ->willReturn($task)
        ;

        $this->schedulingService->expects($this->once())
            ->method('handleUrgentTaskInsertion')
            ->with($task, $urgencyLevel)
            ->willReturn($expectedResult)
        ;

        $this->logger->expects($this->once())
            ->method('info')
            ->with('紧急任务处理完成', self::isArray())
        ;

        $result = $this->taskManager->handleUrgentTask($taskId, $urgencyLevel);

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * 测试批量任务重新分配
     */
    public function testBatchReassignTasks(): void
    {
        $affectedTaskIds = [1, 2, 3];
        $reason = 'worker_unavailable';

        $expectedResult = [
            'successful_reassignments' => 3,
            'failed_reassignments' => [],
            'new_assignments' => [
                ['task_id' => 1, 'worker_id' => 102],
                ['task_id' => 2, 'worker_id' => 103],
            ],
            'estimated_delay' => 300,
        ];

        $this->schedulingService->expects($this->once())
            ->method('batchReassignTasks')
            ->with($affectedTaskIds, $reason)
            ->willReturn($expectedResult)
        ;

        $this->logger->expects($this->once())
            ->method('info')
            ->with('批量任务重分配完成', self::isArray())
        ;

        $result = $this->taskManager->batchReassignTasks($affectedTaskIds, $reason);

        $this->assertEquals($expectedResult, $result);
    }
}
