<?php

declare(strict_types=1);

namespace Tourze\WarehouseOperationBundle\Tests\Service\Scheduling;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Psr\Log\LoggerInterface;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\WarehouseOperationBundle\Entity\InboundTask;
use Tourze\WarehouseOperationBundle\Enum\TaskType;
use Tourze\WarehouseOperationBundle\Service\Scheduling\BatchTaskScheduler;
use Tourze\WarehouseOperationBundle\Service\Scheduling\WorkerAssignmentServiceInterface;

/**
 * BatchTaskScheduler 单元测试
 *
 * 测试批量任务调度服务的功能，包括批量分配、统计生成、优化建议等核心逻辑。
 * @internal
 */
#[CoversClass(BatchTaskScheduler::class)]
#[RunTestsInSeparateProcesses]
class BatchTaskSchedulerTest extends AbstractIntegrationTestCase
{
    private BatchTaskScheduler $service;

    private WorkerAssignmentServiceInterface $workerAssignmentService;

    private LoggerInterface $logger;

    protected function onSetUp(): void
    {
        $this->workerAssignmentService = new class () implements WorkerAssignmentServiceInterface {
            /** @var array<int, array<string, mixed>> */
            private array $predefinedResults = [];
            private int $callCount = 0;
            /** @var callable|null */
            private $callback = null;

            public function __construct()
            {
                // Skip parent constructor to avoid dependencies
            }

            /**
             * @param array<int, array<string, mixed>> $results
             */
            public function setPredefinedResults(array $results): void
            {
                $this->predefinedResults = $results;
                $this->callCount = 0;
                $this->callback = null;
            }

            /**
             * @param callable $callback
             */
            public function setCallback(callable $callback): void
            {
                $this->callback = $callback;
                $this->predefinedResults = [];
                $this->callCount = 0;
            }

            public function assignTaskToOptimalWorker(\Tourze\WarehouseOperationBundle\Entity\WarehouseTask $task, array $availableWorkers, array $constraints): ?array
            {
                if ($this->callback !== null) {
                    return call_user_func($this->callback, $task, $availableWorkers, $constraints);
                }
                if (isset($this->predefinedResults[$this->callCount])) {
                    return $this->predefinedResults[$this->callCount++];
                }
                return null;
            }

            public function assignWorkerBySkill(\Tourze\WarehouseOperationBundle\Entity\WarehouseTask $task, array $options = []): ?array
            {
                return null;
            }

            public function calculateTaskWorkerMatch(\Tourze\WarehouseOperationBundle\Entity\WarehouseTask $task, array $worker): float
            {
                return 0.5;
            }
        };

        $this->logger = $this->createMock(LoggerInterface::class);

        // @phpstan-ignore integrationTest.noDirectInstantiationOfCoveredClass
        $this->service = new BatchTaskScheduler(
            $this->workerAssignmentService,
            $this->logger
        );
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Scheduling\BatchTaskScheduler::scheduleTaskBatch
     */
    public function testScheduleTaskBatchWithEmptyTasks(): void
    {
        $pendingTasks = [];
        $constraints = [];

        $result = $this->service->scheduleTaskBatch($pendingTasks, $constraints);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('assignments', $result);
        $this->assertArrayHasKey('unassigned', $result);
        $this->assertArrayHasKey('statistics', $result);
        $this->assertArrayHasKey('recommendations', $result);

        $this->assertEmpty($result['assignments']);
        $this->assertEmpty($result['unassigned']);

        $this->assertArrayHasKey('statistics', $result);
        $statistics = $result['statistics'];
        self::assertIsArray($statistics);
        /** @var array<string, mixed> $statistics */
        $this->assertEquals(0, $statistics['total_tasks']);
        $this->assertEquals(0, $statistics['assigned_count']);
        $this->assertEquals(0, $statistics['unassigned_count']);
        $this->assertEquals(0.0, $statistics['assignment_rate']);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Scheduling\BatchTaskScheduler::scheduleTaskBatch
     */
    public function testScheduleTaskBatchWithSuccessfulAssignments(): void
    {
        $task1 = new InboundTask();
        $task1->setType(TaskType::INBOUND);
        $task1->setPriority(80);

        $task2 = new InboundTask();
        $task2->setType(TaskType::OUTBOUND);
        $task2->setPriority(70);

        $pendingTasks = [$task1, $task2];
        $constraints = ['max_tasks_per_worker' => 10];

        /** @phpstan-ignore method.notFound */
        $this->workerAssignmentService->setPredefinedResults([
            [
                'worker_id' => 1,
                'worker_name' => 'Worker 1',
                'match_score' => 0.85,
            ],
            [
                'worker_id' => 2,
                'worker_name' => 'Worker 2',
                'match_score' => 0.78,
            ]
        ]);

        $result = $this->service->scheduleTaskBatch($pendingTasks, $constraints);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('assignments', $result);
        $assignments = $result['assignments'];
        self::assertIsIterable($assignments);
        $this->assertCount(2, $assignments);
        $this->assertEmpty($result['unassigned']);

        $this->assertArrayHasKey('statistics', $result);
        $statistics = $result['statistics'];
        self::assertIsArray($statistics);
        /** @var array<string, mixed> $statistics */
        $this->assertEquals(2, $statistics['total_tasks']);
        $this->assertEquals(2, $statistics['assigned_count']);
        $this->assertEquals(0, $statistics['unassigned_count']);
        $this->assertEquals(1.0, $statistics['assignment_rate']);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Scheduling\BatchTaskScheduler::scheduleTaskBatch
     */
    public function testScheduleTaskBatchWithPartialAssignments(): void
    {
        $task1 = new InboundTask();
        $task1->setType(TaskType::INBOUND);
        $task1->setPriority(90);

        $task2 = new InboundTask();
        $task2->setType(TaskType::QUALITY);
        $task2->setPriority(60);

        $task3 = new InboundTask();
        $task3->setType(TaskType::OUTBOUND);
        $task3->setPriority(50);

        $pendingTasks = [$task1, $task2, $task3];
        $constraints = [];

        /** @phpstan-ignore method.notFound */
        $this->workerAssignmentService->setPredefinedResults([
            [
                'worker_id' => 1,
                'worker_name' => 'Worker 1',
                'match_score' => 0.9,
            ],
            [],
            [
                'worker_id' => 2,
                'worker_name' => 'Worker 2',
                'match_score' => 0.75,
            ]
        ]);

        $result = $this->service->scheduleTaskBatch($pendingTasks, $constraints);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('assignments', $result);
        $assignments = $result['assignments'];
        self::assertIsIterable($assignments);
        $this->assertCount(2, $assignments);
        $this->assertArrayHasKey('unassigned', $result);
        $unassigned = $result['unassigned'];
        self::assertIsIterable($unassigned);
        $this->assertCount(1, $unassigned);

        $this->assertArrayHasKey('statistics', $result);
        $statistics = $result['statistics'];
        self::assertIsArray($statistics);
        /** @var array<string, mixed> $statistics */
        $this->assertEquals(3, $statistics['total_tasks']);
        $this->assertEquals(2, $statistics['assigned_count']);
        $this->assertEquals(1, $statistics['unassigned_count']);
        $this->assertEquals(0.667, $statistics['assignment_rate']);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Scheduling\BatchTaskScheduler::scheduleTaskBatch
     */
    public function testScheduleTaskBatchSortsTasksByPriority(): void
    {
        $lowPriorityTask = new InboundTask();
        $lowPriorityTask->setType(TaskType::INBOUND);
        $lowPriorityTask->setPriority(30);

        $highPriorityTask = new InboundTask();
        $highPriorityTask->setType(TaskType::QUALITY);
        $highPriorityTask->setPriority(95);

        $mediumPriorityTask = new InboundTask();
        $mediumPriorityTask->setType(TaskType::OUTBOUND);
        $mediumPriorityTask->setPriority(60);

        $pendingTasks = [$lowPriorityTask, $highPriorityTask, $mediumPriorityTask];

        $assignmentCallOrder = [];

        /** @phpstan-ignore method.notFound */
        $this->workerAssignmentService->setCallback(function ($task) use (&$assignmentCallOrder) {
            $assignmentCallOrder[] = $task->getPriority();

            return [
                'worker_id' => 1,
                'worker_name' => 'Worker 1',
                'match_score' => 0.8,
            ];
        });

        $result = $this->service->scheduleTaskBatch($pendingTasks, []);

        // 验证调用顺序是按优先级排序的（降序）
        $this->assertEquals([95, 60, 30], $assignmentCallOrder);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Scheduling\BatchTaskScheduler::scheduleTaskBatch
     */
    public function testScheduleTaskBatchGeneratesStatistics(): void
    {
        $task = new InboundTask();
        $task->setType(TaskType::INBOUND);
        $task->setPriority(70);

        $pendingTasks = [$task];

        /** @phpstan-ignore method.notFound */
        $this->workerAssignmentService->setPredefinedResults([
            [
                'worker_id' => 1,
                'worker_name' => 'Worker 1',
                'match_score' => 0.85,
            ]
        ]);

        $result = $this->service->scheduleTaskBatch($pendingTasks, []);

        $this->assertArrayHasKey('statistics', $result);
        $statistics = $result['statistics'];
        self::assertIsArray($statistics);
        /** @var array<string, mixed> $statistics */

        $this->assertArrayHasKey('total_tasks', $statistics);
        $this->assertArrayHasKey('assigned_count', $statistics);
        $this->assertArrayHasKey('unassigned_count', $statistics);
        $this->assertArrayHasKey('assignment_rate', $statistics);
        $this->assertArrayHasKey('processing_time_ms', $statistics);
        $this->assertArrayHasKey('average_match_score', $statistics);
        $this->assertArrayHasKey('worker_utilization', $statistics);

        $this->assertIsFloat($statistics['processing_time_ms']);
        $this->assertGreaterThanOrEqual(0, $statistics['processing_time_ms']);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Scheduling\BatchTaskScheduler::scheduleTaskBatch
     */
    public function testScheduleTaskBatchCalculatesAverageMatchScore(): void
    {
        $task1 = new InboundTask();
        $task1->setType(TaskType::INBOUND);
        $task1->setPriority(80);

        $task2 = new InboundTask();
        $task2->setType(TaskType::QUALITY);
        $task2->setPriority(70);

        $pendingTasks = [$task1, $task2];

        /** @phpstan-ignore method.notFound */
        $this->workerAssignmentService->setPredefinedResults([
            [
                'worker_id' => 1,
                'worker_name' => 'Worker 1',
                'match_score' => 0.8,
            ],
            [
                'worker_id' => 2,
                'worker_name' => 'Worker 2',
                'match_score' => 0.6,
            ]
        ]);

        $result = $this->service->scheduleTaskBatch($pendingTasks, []);

        $this->assertArrayHasKey('statistics', $result);
        $statistics = $result['statistics'];
        self::assertIsArray($statistics);
        /** @var array<string, mixed> $statistics */
        // 平均分数应该是 (0.8 + 0.6) / 2 = 0.7
        $this->assertEquals(0.7, $statistics['average_match_score']);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Scheduling\BatchTaskScheduler::scheduleTaskBatch
     */
    public function testScheduleTaskBatchGeneratesRecommendationsForUnassignedTasks(): void
    {
        $task1 = new InboundTask();
        $task1->setType(TaskType::INBOUND);
        $task1->setPriority(80);

        $task2 = new InboundTask();
        $task2->setType(TaskType::QUALITY);
        $task2->setPriority(70);

        $pendingTasks = [$task1, $task2];

        /** @phpstan-ignore method.notFound */
        $this->workerAssignmentService->setPredefinedResults([
            [],
            []
        ]);

        $result = $this->service->scheduleTaskBatch($pendingTasks, []);

        $this->assertArrayHasKey('recommendations', $result);
        $recommendations = $result['recommendations'];
        $this->assertIsArray($recommendations);
        $this->assertNotEmpty($recommendations);

        self::assertIsArray($recommendations);
        /** @var array<int, array<string, mixed>> $recommendations */
        $this->assertArrayHasKey(0, $recommendations);
        $recommendation = $recommendations[0];
        self::assertIsArray($recommendation);
        /** @var array<string, mixed> $recommendation */
        $this->assertArrayHasKey('type', $recommendation);
        $this->assertArrayHasKey('description', $recommendation);
        $this->assertArrayHasKey('priority', $recommendation);
        $this->assertEquals('increase_workers', $recommendation['type']);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Scheduling\BatchTaskScheduler::scheduleTaskBatch
     */
    public function testScheduleTaskBatchGeneratesHighUnassignedRateRecommendation(): void
    {
        // 创建10个任务，其中8个无法分配（超过30%）
        $pendingTasks = [];
        for ($i = 0; $i < 10; ++$i) {
            $task = new InboundTask();
            $task->setType(TaskType::INBOUND);
            $task->setPriority(70);
            $pendingTasks[] = $task;
        }

        /** @phpstan-ignore method.notFound */
        $this->workerAssignmentService->setCallback(function () {
            static $count = 0;
            ++$count;

            return $count <= 2 ? ['worker_id' => 1, 'worker_name' => 'Worker 1', 'match_score' => 0.8] : [];
        });

        $result = $this->service->scheduleTaskBatch($pendingTasks, []);

        $this->assertArrayHasKey('recommendations', $result);
        $recommendations = $result['recommendations'];
        self::assertIsArray($recommendations);
        /** @var array<string, mixed> $recommendations */

        // 应该有至少2个建议（增加作业员 + 调整优先级）
        $this->assertGreaterThanOrEqual(2, count($recommendations));

        $types = array_column($recommendations, 'type');
        $this->assertContains('increase_workers', $types);
        $this->assertContains('adjust_priorities', $types);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Scheduling\BatchTaskScheduler::scheduleTaskBatch
     */
    public function testScheduleTaskBatchLogsStartAndCompletion(): void
    {
        $task = new InboundTask();
        $task->setType(TaskType::INBOUND);
        $task->setPriority(70);

        $pendingTasks = [$task];

        $this->logger
            ->expects($this->exactly(2))
            ->method('info')
            ->willReturnCallback(function ($message, $context) {
                static $callCount = 0;
                ++$callCount;

                if (1 === $callCount) {
                    $this->assertEquals('开始批量任务调度', $message);
                    $this->assertArrayHasKey('task_count', $context);
                } else {
                    $this->assertEquals('批量任务调度完成', $message);
                }
            })
        ;

        /** @phpstan-ignore method.notFound */
        $this->workerAssignmentService->setPredefinedResults([
            [
                'worker_id' => 1,
                'worker_name' => 'Worker 1',
                'match_score' => 0.8,
            ]
        ]);

        $this->service->scheduleTaskBatch($pendingTasks, []);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Scheduling\BatchTaskScheduler::scheduleTaskBatch
     */
    public function testScheduleTaskBatchHandlesNonTaskObjects(): void
    {
        // 测试混合数组（包含非任务对象）
        $task = new InboundTask();
        $task->setType(TaskType::INBOUND);
        $task->setPriority(70);

        $pendingTasks = [
            $task,
            'invalid_task', // 非任务对象
            123, // 非对象
        ];

        /** @phpstan-ignore method.notFound */
        $this->workerAssignmentService->setPredefinedResults([
            [
                'worker_id' => 1,
                'worker_name' => 'Worker 1',
                'match_score' => 0.8,
            ]
        ]);

        $result = $this->service->scheduleTaskBatch($pendingTasks, []);

        $this->assertArrayHasKey('assignments', $result);
        $assignments = $result['assignments'];
        self::assertIsIterable($assignments);
        $this->assertCount(1, $assignments);
        $this->assertArrayHasKey('unassigned', $result);
        $unassigned = $result['unassigned'];
        self::assertIsIterable($unassigned);
        $this->assertCount(2, $unassigned);
    }

    public function testServiceConstructorAndBasicFunctionality(): void
    {
        // 验证服务可以正确实例化
        $this->assertInstanceOf(BatchTaskScheduler::class, $this->service);

        // 验证基本功能工作正常
        $result = $this->service->scheduleTaskBatch([], []);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('assignments', $result);
        $this->assertArrayHasKey('unassigned', $result);
        $this->assertArrayHasKey('statistics', $result);
        $this->assertArrayHasKey('recommendations', $result);
    }
}
