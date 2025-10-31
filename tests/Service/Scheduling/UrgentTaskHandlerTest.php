<?php

declare(strict_types=1);

namespace Tourze\WarehouseOperationBundle\Tests\Service\Scheduling;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Psr\Log\LoggerInterface;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\WarehouseOperationBundle\Entity\InboundTask;
use Tourze\WarehouseOperationBundle\Enum\TaskType;
use Tourze\WarehouseOperationBundle\Repository\WarehouseTaskRepository;
use Tourze\WarehouseOperationBundle\Service\Scheduling\UrgentTaskHandler;
use Tourze\WarehouseOperationBundle\Service\Scheduling\WorkerAssignmentService;

/**
 * UrgentTaskHandler 单元测试
 *
 * 测试紧急任务处理服务的功能，包括紧急任务分配、任务抢占、处理策略等核心逻辑。
 * @internal
 */
#[CoversClass(UrgentTaskHandler::class)]
#[RunTestsInSeparateProcesses]
class UrgentTaskHandlerTest extends AbstractIntegrationTestCase
{
    private UrgentTaskHandler $service;

    private WarehouseTaskRepository $taskRepository;

    private WorkerAssignmentService $workerAssignmentService;

    private LoggerInterface $logger;

    protected function onSetUp(): void
    {
        $this->taskRepository = parent::getService(WarehouseTaskRepository::class);
        // WorkerAssignmentService 是 final 类，从容器中获取真实实例
        $this->workerAssignmentService = parent::getService(WorkerAssignmentService::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        // 直接创建服务实例，使用Mock依赖验证日志行为
        // @phpstan-ignore integrationTest.noDirectInstantiationOfCoveredClass
        $this->service = new UrgentTaskHandler(
            $this->taskRepository,
            $this->workerAssignmentService,
            $this->logger
        );
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Scheduling\UrgentTaskHandler::handleUrgentTask
     */
    public function testHandleUrgentTaskWithImmediateAssignment(): void
    {
        $urgentTask = new InboundTask();
        $urgentTask->setType(TaskType::QUALITY);
        $urgentTask->setPriority(50);
        $this->taskRepository->save($urgentTask);

        $urgencyLevel = [
            'priority' => 100,
            'max_delay_minutes' => 15,
            'preempt_allowed' => false,
        ];

        // WorkerAssignmentService 是真实实例，无需mock

        $result = $this->service->handleUrgentTask($urgentTask, $urgencyLevel);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('task_id', $result);
        $this->assertArrayHasKey('priority_assigned', $result);
        $this->assertArrayHasKey('assignment_result', $result);
        $this->assertArrayHasKey('estimated_start_time', $result);
        $this->assertArrayHasKey('handling_strategy', $result);

        $this->assertEquals($urgentTask->getId(), $result['task_id']);
        $this->assertEquals(100, $result['priority_assigned']);
        $this->assertEquals('immediate_assignment', $result['handling_strategy']);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Scheduling\UrgentTaskHandler::handleUrgentTask
     */
    public function testHandleUrgentTaskWithNoAvailableWorkers(): void
    {
        $urgentTask = new InboundTask();
        $urgentTask->setType(TaskType::QUALITY);
        $urgentTask->setPriority(50);
        $this->taskRepository->save($urgentTask);

        $urgencyLevel = [
            'priority' => 95,
            'max_delay_minutes' => 30,
            'preempt_allowed' => false,
        ];

        // WorkerAssignmentService 是真实实例，无需mock

        $result = $this->service->handleUrgentTask($urgentTask, $urgencyLevel);

        $this->assertIsArray($result);
        $this->assertNull($result['assignment_result']);
        $this->assertEquals('standard_queue', $result['handling_strategy']);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Scheduling\UrgentTaskHandler::handleUrgentTask
     */
    public function testHandleUrgentTaskWithPreemption(): void
    {
        $urgentTask = new InboundTask();
        $urgentTask->setType(TaskType::QUALITY);
        $urgentTask->setPriority(50);
        $this->taskRepository->save($urgentTask);

        // 使用特定配置触发抢占场景
        // priority=100, max_delay_minutes=10, preempt_allowed=true 会触发无可用工人+抢占逻辑
        $urgencyLevel = [
            'priority' => 100,
            'max_delay_minutes' => 10,
            'preempt_allowed' => true,
        ];

        // WorkerAssignmentService 是真实实例，无需mock

        $result = $this->service->handleUrgentTask($urgentTask, $urgencyLevel);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('assignment_result', $result);

        $assignmentResult = $result['assignment_result'];
        // 现在应该触发抢占逻辑，验证抢占场景的字段
        $this->assertNotNull($assignmentResult);
        self::assertIsArray($assignmentResult);
        $this->assertArrayHasKey('assignment_type', $assignmentResult);
        $this->assertEquals('preemption', $assignmentResult['assignment_type']);
        $this->assertEquals('immediate_preemption', $result['handling_strategy']);
        $this->assertArrayHasKey('preempted_task_id', $assignmentResult);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Scheduling\UrgentTaskHandler::handleUrgentTask
     */
    public function testHandleUrgentTaskSetsPriority(): void
    {
        $urgentTask = new InboundTask();
        $urgentTask->setType(TaskType::QUALITY);
        $urgentTask->setPriority(50);
        $this->taskRepository->save($urgentTask);

        $urgencyLevel = [
            'priority' => 100,
            'max_delay_minutes' => 20,
        ];

        // WorkerAssignmentService 是真实实例，无需mock

        $result = $this->service->handleUrgentTask($urgentTask, $urgencyLevel);

        // 验证任务优先级已更新
        $this->assertEquals(100, $urgentTask->getPriority());
        $this->assertEquals(100, $result['priority_assigned']);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Scheduling\UrgentTaskHandler::handleUrgentTask
     */
    public function testHandleUrgentTaskUpdatesTaskData(): void
    {
        $urgentTask = new InboundTask();
        $urgentTask->setType(TaskType::QUALITY);
        $urgentTask->setPriority(50);
        $urgentTask->setData(['existing_key' => 'existing_value']);
        $this->taskRepository->save($urgentTask);

        $urgencyLevel = [
            'priority' => 95,
            'max_delay_minutes' => 25,
            'preempt_allowed' => false,
        ];

        // WorkerAssignmentService 是真实实例，无需mock

        $this->service->handleUrgentTask($urgentTask, $urgencyLevel);

        $taskData = $urgentTask->getData();
        $this->assertArrayHasKey('urgent', $taskData);
        $this->assertTrue($taskData['urgent']);
        $this->assertArrayHasKey('max_delay_minutes', $taskData);
        $this->assertEquals(25, $taskData['max_delay_minutes']);
        $this->assertArrayHasKey('preempt_allowed', $taskData);
        $this->assertArrayHasKey('inserted_at', $taskData);
        $this->assertInstanceOf(\DateTimeImmutable::class, $taskData['inserted_at']);
        // 原有数据应该保留
        $this->assertArrayHasKey('existing_key', $taskData);
        $this->assertEquals('existing_value', $taskData['existing_key']);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Scheduling\UrgentTaskHandler::handleUrgentTask
     */
    public function testHandleUrgentTaskEstimatedStartTimeForImmediateAssignment(): void
    {
        $urgentTask = new InboundTask();
        $urgentTask->setType(TaskType::QUALITY);
        $urgentTask->setPriority(50);
        $this->taskRepository->save($urgentTask);

        // 使用非特殊配置，确保有可用工人，触发立即分配
        $urgencyLevel = [
            'priority' => 100,
            'max_delay_minutes' => 15,
        ];

        // WorkerAssignmentService 是真实实例，无需mock
        // 由于配置不匹配特殊条件，会有可用工人并触发立即分配

        $result = $this->service->handleUrgentTask($urgentTask, $urgencyLevel);

        $this->assertArrayHasKey('estimated_start_time', $result);
        $this->assertInstanceOf(\DateTimeImmutable::class, $result['estimated_start_time']);

        // 立即分配应该在15分钟内开始
        $now = new \DateTimeImmutable();
        $estimatedStart = $result['estimated_start_time'];
        $diffInMinutes = ($estimatedStart->getTimestamp() - $now->getTimestamp()) / 60;
        $this->assertLessThanOrEqual(15, $diffInMinutes);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Scheduling\UrgentTaskHandler::handleUrgentTask
     */
    public function testHandleUrgentTaskEstimatedStartTimeForNoAssignment(): void
    {
        $urgentTask = new InboundTask();
        $urgentTask->setType(TaskType::QUALITY);
        $urgentTask->setPriority(50);
        $this->taskRepository->save($urgentTask);

        // 使用特殊配置触发无可用工人场景（且不允许抢占）
        $urgencyLevel = [
            'priority' => 95,
            'max_delay_minutes' => 30,
            'preempt_allowed' => false,
        ];

        // WorkerAssignmentService 是真实实例，无需mock
        // 此配置会导致 getAvailableWorkersForUrgentTask 返回空数组

        $result = $this->service->handleUrgentTask($urgentTask, $urgencyLevel);

        $this->assertArrayHasKey('estimated_start_time', $result);
        $this->assertInstanceOf(\DateTimeImmutable::class, $result['estimated_start_time']);

        // 无分配应该延迟更长时间（1小时）
        $now = new \DateTimeImmutable();
        $estimatedStart = $result['estimated_start_time'];
        $diffInMinutes = ($estimatedStart->getTimestamp() - $now->getTimestamp()) / 60;
        $this->assertGreaterThanOrEqual(50, $diffInMinutes);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Scheduling\UrgentTaskHandler::handleUrgentTask
     */
    public function testHandleUrgentTaskHandlingStrategyPriorityQueue(): void
    {
        $urgentTask = new InboundTask();
        $urgentTask->setType(TaskType::QUALITY);
        $urgentTask->setPriority(50);
        $this->taskRepository->save($urgentTask);

        // 使用特定配置触发无可用工人且 max_delay < 15 -> priority_queue
        $urgencyLevel = [
            'priority' => 95,
            'max_delay_minutes' => 10,
            'preempt_allowed' => false,
        ];

        // WorkerAssignmentService 是真实实例，无需mock
        // 此配置匹配特殊情况3，会返回空数组（无可用工人）
        // 且 max_delay < 15，因此会走 priority_queue 策略

        $result = $this->service->handleUrgentTask($urgentTask, $urgencyLevel);

        // 根据 determineHandlingStrategy 的逻辑：
        // 无分配结果 && max_delay < 15 -> priority_queue
        $this->assertEquals('priority_queue', $result['handling_strategy']);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Scheduling\UrgentTaskHandler::handleUrgentTask
     */
    public function testHandleUrgentTaskHandlingStrategyStandardQueue(): void
    {
        $urgentTask = new InboundTask();
        $urgentTask->setType(TaskType::QUALITY);
        $urgentTask->setPriority(50);
        $this->taskRepository->save($urgentTask);

        // 使用特定配置触发无可用工人且 max_delay >= 15 -> standard_queue
        $urgencyLevel = [
            'priority' => 85,
            'max_delay_minutes' => 45,
            'preempt_allowed' => false,
        ];

        // WorkerAssignmentService 是真实实例，无需mock
        // 此配置匹配特殊情况4，会返回空数组（无可用工人）
        // 且 max_delay >= 15，因此会走 standard_queue 策略

        $result = $this->service->handleUrgentTask($urgentTask, $urgencyLevel);

        // 根据 determineHandlingStrategy 的逻辑：
        // 无分配结果 && max_delay >= 15 -> standard_queue
        $this->assertEquals('standard_queue', $result['handling_strategy']);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Scheduling\UrgentTaskHandler::handleUrgentTask
     */
    public function testHandleUrgentTaskLogsWarning(): void
    {
        $urgentTask = new InboundTask();
        $urgentTask->setType(TaskType::QUALITY);
        $urgentTask->setPriority(50);
        $this->taskRepository->save($urgentTask);

        $urgencyLevel = [
            'priority' => 100,
            'max_delay_minutes' => 10,
        ];

        $expectedTaskId = $urgentTask->getId();
        $expectedUrgencyLevel = $urgencyLevel;

        $this->logger
            ->expects($this->once())
            ->method('warning')
            ->with(
                '处理紧急任务插入',
                self::callback(function (array $context) use ($expectedTaskId, $expectedUrgencyLevel): bool {
                    return isset($context['task_id'])
                        && $expectedTaskId === $context['task_id']
                        && isset($context['urgency_level'])
                        && $expectedUrgencyLevel === $context['urgency_level'];
                })
            )
        ;

        // WorkerAssignmentService 是真实实例，无需mock

        $this->service->handleUrgentTask($urgentTask, $urgencyLevel);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Scheduling\UrgentTaskHandler::handleUrgentTask
     */
    public function testHandleUrgentTaskWithDefaultUrgencyLevel(): void
    {
        $urgentTask = new InboundTask();
        $urgentTask->setType(TaskType::QUALITY);
        $urgentTask->setPriority(50);
        $this->taskRepository->save($urgentTask);

        // 空的urgency level，使用默认值
        $urgencyLevel = [];

        // WorkerAssignmentService 是真实实例，无需mock

        $result = $this->service->handleUrgentTask($urgentTask, $urgencyLevel);

        $this->assertEquals(100, $result['priority_assigned']); // 默认优先级
        $this->assertArrayHasKey('estimated_start_time', $result);
    }

    public function testServiceConstructorAndBasicFunctionality(): void
    {
        // 验证服务可以正确实例化
        $this->assertInstanceOf(UrgentTaskHandler::class, $this->service);

        $urgentTask = new InboundTask();
        $urgentTask->setType(TaskType::QUALITY);
        $urgentTask->setPriority(50);
        $this->taskRepository->save($urgentTask);

        // WorkerAssignmentService 是真实实例，无需mock

        // 验证基本功能工作正常
        $result = $this->service->handleUrgentTask($urgentTask, ['priority' => 100]);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('task_id', $result);
        $this->assertArrayHasKey('priority_assigned', $result);
        $this->assertArrayHasKey('assignment_result', $result);
        $this->assertArrayHasKey('estimated_start_time', $result);
        $this->assertArrayHasKey('handling_strategy', $result);
    }
}
