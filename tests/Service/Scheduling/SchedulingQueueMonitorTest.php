<?php

declare(strict_types=1);

namespace Tourze\WarehouseOperationBundle\Tests\Service\Scheduling;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\WarehouseOperationBundle\Enum\TaskStatus;
use Tourze\WarehouseOperationBundle\Repository\WarehouseTaskRepository;
use Tourze\WarehouseOperationBundle\Service\Scheduling\SchedulingQueueMonitor;

/**
 * SchedulingQueueMonitor 单元测试
 *
 * 测试调度队列监控服务的功能，包括队列状态监控、作业员利用率计算、瓶颈分析等核心逻辑。
 * @internal
 */
#[CoversClass(SchedulingQueueMonitor::class)]
class SchedulingQueueMonitorTest extends TestCase
{
    private SchedulingQueueMonitor $service;

    private WarehouseTaskRepository $taskRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->taskRepository = $this->createMock(WarehouseTaskRepository::class);
        $this->service = new SchedulingQueueMonitor($this->taskRepository);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Scheduling\SchedulingQueueMonitor::getQueueStatus
     */
    public function testGetQueueStatusWithEmptyQueue(): void
    {
        $this->taskRepository
            ->method('getTaskStatistics')
            ->willReturn([])
        ;

        $result = $this->service->getQueueStatus();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('pending_count', $result);
        $this->assertArrayHasKey('active_count', $result);
        $this->assertArrayHasKey('worker_utilization', $result);
        $this->assertArrayHasKey('average_wait_time', $result);
        $this->assertArrayHasKey('bottlenecks', $result);
        $this->assertArrayHasKey('queue_health', $result);
        $this->assertArrayHasKey('timestamp', $result);

        $this->assertEquals(0, $result['pending_count']);
        $this->assertEquals(0, $result['active_count']);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Scheduling\SchedulingQueueMonitor::getQueueStatus
     */
    public function testGetQueueStatusWithPendingTasks(): void
    {
        $this->taskRepository
            ->method('getTaskStatistics')
            ->willReturn([
                TaskStatus::PENDING->value => 25,
                TaskStatus::ASSIGNED->value => 10,
                TaskStatus::IN_PROGRESS->value => 8,
            ])
        ;

        $result = $this->service->getQueueStatus();

        $this->assertEquals(25, $result['pending_count']);
        $this->assertEquals(18, $result['active_count']); // 10 + 8
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Scheduling\SchedulingQueueMonitor::getQueueStatus
     */
    public function testGetQueueStatusWorkerUtilizationStructure(): void
    {
        $this->taskRepository
            ->method('getTaskStatistics')
            ->willReturn([])
        ;

        $result = $this->service->getQueueStatus();

        self::assertIsArray($result);
        $this->assertArrayHasKey('worker_utilization', $result);
        self::assertIsArray($result['worker_utilization']);
        /** @var array<string, mixed> $workerUtilization */
        $workerUtilization = $result['worker_utilization'];

        $this->assertIsArray($workerUtilization);
        $this->assertArrayHasKey('total_workers', $workerUtilization);
        $this->assertArrayHasKey('active_workers', $workerUtilization);
        $this->assertArrayHasKey('utilization_rate', $workerUtilization);
        $this->assertArrayHasKey('average_workload', $workerUtilization);

        $this->assertIsInt($workerUtilization['total_workers']);
        $this->assertIsInt($workerUtilization['active_workers']);
        $this->assertIsFloat($workerUtilization['utilization_rate']);
        $this->assertIsFloat($workerUtilization['average_workload']);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Scheduling\SchedulingQueueMonitor::getQueueStatus
     */
    public function testGetQueueStatusAverageWaitTimeStructure(): void
    {
        $this->taskRepository
            ->method('getTaskStatistics')
            ->willReturn([])
        ;

        $result = $this->service->getQueueStatus();

        self::assertIsArray($result);
        $this->assertArrayHasKey('average_wait_time', $result);
        self::assertIsArray($result['average_wait_time']);
        /** @var array<string, mixed> $waitTime */
        $waitTime = $result['average_wait_time'];

        $this->assertIsArray($waitTime);
        $this->assertArrayHasKey('average_minutes', $waitTime);
        $this->assertArrayHasKey('median_minutes', $waitTime);
        $this->assertArrayHasKey('max_minutes', $waitTime);

        $this->assertIsInt($waitTime['average_minutes']);
        $this->assertIsInt($waitTime['median_minutes']);
        $this->assertIsInt($waitTime['max_minutes']);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Scheduling\SchedulingQueueMonitor::getQueueStatus
     */
    public function testGetQueueStatusBottlenecksStructure(): void
    {
        $this->taskRepository
            ->method('getTaskStatistics')
            ->willReturn([])
        ;

        $result = $this->service->getQueueStatus();

        self::assertIsArray($result);
        $this->assertArrayHasKey('bottlenecks', $result);
        self::assertIsArray($result['bottlenecks']);
        /** @var array<array<string, mixed>> $bottlenecks */
        $bottlenecks = $result['bottlenecks'];

        $this->assertIsArray($bottlenecks);

        if (count($bottlenecks) > 0) {
            self::assertIsArray($bottlenecks[0]);
            /** @var array<string, mixed> $bottleneck */
            $bottleneck = $bottlenecks[0];
            $this->assertArrayHasKey('type', $bottleneck);
            $this->assertArrayHasKey('description', $bottleneck);
            $this->assertArrayHasKey('impact', $bottleneck);
        }
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Scheduling\SchedulingQueueMonitor::getQueueStatus
     */
    public function testGetQueueStatusHealthyQueue(): void
    {
        $this->taskRepository
            ->method('getTaskStatistics')
            ->willReturn([
                TaskStatus::PENDING->value => 10,
                TaskStatus::ASSIGNED->value => 5,
                TaskStatus::IN_PROGRESS->value => 3,
            ])
        ;

        $result = $this->service->getQueueStatus();

        $this->assertEquals('healthy', $result['queue_health']);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Scheduling\SchedulingQueueMonitor::getQueueStatus
     */
    public function testGetQueueStatusWarningQueue(): void
    {
        $this->taskRepository
            ->method('getTaskStatistics')
            ->willReturn([
                TaskStatus::PENDING->value => 25,
                TaskStatus::ASSIGNED->value => 10,
                TaskStatus::IN_PROGRESS->value => 5,
            ])
        ;

        $result = $this->service->getQueueStatus();

        $this->assertEquals('warning', $result['queue_health']);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Scheduling\SchedulingQueueMonitor::getQueueStatus
     */
    public function testGetQueueStatusCriticalQueue(): void
    {
        $this->taskRepository
            ->method('getTaskStatistics')
            ->willReturn([
                TaskStatus::PENDING->value => 55,
                TaskStatus::ASSIGNED->value => 15,
                TaskStatus::IN_PROGRESS->value => 10,
            ])
        ;

        $result = $this->service->getQueueStatus();

        $this->assertEquals('critical', $result['queue_health']);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Scheduling\SchedulingQueueMonitor::getQueueStatus
     */
    public function testGetQueueStatusTimestamp(): void
    {
        $this->taskRepository
            ->method('getTaskStatistics')
            ->willReturn([])
        ;

        $result = $this->service->getQueueStatus();

        $this->assertArrayHasKey('timestamp', $result);
        $this->assertInstanceOf(\DateTimeImmutable::class, $result['timestamp']);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Scheduling\SchedulingQueueMonitor::getQueueStatus
     */
    public function testGetQueueStatusBottleneckTypes(): void
    {
        $this->taskRepository
            ->method('getTaskStatistics')
            ->willReturn([])
        ;

        $result = $this->service->getQueueStatus();

        self::assertIsArray($result);
        self::assertIsArray($result['bottlenecks']);
        /** @var array<array<string, mixed>> $bottlenecks */
        $bottlenecks = $result['bottlenecks'];
        $validTypes = ['skill_shortage', 'zone_congestion', 'equipment_shortage', 'high_workload'];

        self::assertIsIterable($bottlenecks);
        foreach ($bottlenecks as $bottleneck) {
            self::assertIsArray($bottleneck);
            $this->assertContains($bottleneck['type'], $validTypes);
        }
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Scheduling\SchedulingQueueMonitor::getQueueStatus
     */
    public function testGetQueueStatusBottleneckImpactLevels(): void
    {
        $this->taskRepository
            ->method('getTaskStatistics')
            ->willReturn([])
        ;

        $result = $this->service->getQueueStatus();

        self::assertIsArray($result);
        self::assertIsArray($result['bottlenecks']);
        /** @var array<array<string, mixed>> $bottlenecks */
        $bottlenecks = $result['bottlenecks'];
        $validImpacts = ['high', 'medium', 'low'];

        self::assertIsIterable($bottlenecks);
        foreach ($bottlenecks as $bottleneck) {
            self::assertIsArray($bottleneck);
            $this->assertContains($bottleneck['impact'], $validImpacts);
        }
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Scheduling\SchedulingQueueMonitor::getQueueStatus
     */
    public function testGetQueueStatusWithOnlyPendingTasks(): void
    {
        $this->taskRepository
            ->method('getTaskStatistics')
            ->willReturn([
                TaskStatus::PENDING->value => 30,
            ])
        ;

        $result = $this->service->getQueueStatus();

        $this->assertEquals(30, $result['pending_count']);
        $this->assertEquals(0, $result['active_count']);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Scheduling\SchedulingQueueMonitor::getQueueStatus
     */
    public function testGetQueueStatusWithOnlyActiveTasks(): void
    {
        $this->taskRepository
            ->method('getTaskStatistics')
            ->willReturn([
                TaskStatus::ASSIGNED->value => 12,
                TaskStatus::IN_PROGRESS->value => 8,
            ])
        ;

        $result = $this->service->getQueueStatus();

        $this->assertEquals(0, $result['pending_count']);
        $this->assertEquals(20, $result['active_count']);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Scheduling\SchedulingQueueMonitor::getQueueStatus
     */
    public function testGetQueueStatusWorkerUtilizationValues(): void
    {
        $this->taskRepository
            ->method('getTaskStatistics')
            ->willReturn([])
        ;

        $result = $this->service->getQueueStatus();

        self::assertIsArray($result);
        self::assertIsArray($result['worker_utilization']);
        /** @var array<string, mixed> $workerUtilization */
        $workerUtilization = $result['worker_utilization'];

        // 验证利用率在0-1之间
        $this->assertGreaterThanOrEqual(0, $workerUtilization['utilization_rate']);
        $this->assertLessThanOrEqual(1, $workerUtilization['utilization_rate']);

        // 验证活跃作业员不超过总作业员
        $this->assertLessThanOrEqual(
            $workerUtilization['total_workers'],
            $workerUtilization['active_workers']
        );

        // 验证平均工作量为正数
        $this->assertGreaterThanOrEqual(0, $workerUtilization['average_workload']);
    }

    public function testServiceConstructorAndBasicFunctionality(): void
    {
        // 验证服务可以正确实例化
        $this->assertInstanceOf(SchedulingQueueMonitor::class, $this->service);

        $this->taskRepository
            ->method('getTaskStatistics')
            ->willReturn([])
        ;

        // 验证基本功能工作正常
        $result = $this->service->getQueueStatus();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('pending_count', $result);
        $this->assertArrayHasKey('active_count', $result);
        $this->assertArrayHasKey('worker_utilization', $result);
        $this->assertArrayHasKey('average_wait_time', $result);
        $this->assertArrayHasKey('bottlenecks', $result);
        $this->assertArrayHasKey('queue_health', $result);
        $this->assertArrayHasKey('timestamp', $result);
    }
}
