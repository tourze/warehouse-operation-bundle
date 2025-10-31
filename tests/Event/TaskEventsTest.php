<?php

namespace Tourze\WarehouseOperationBundle\Tests\Event;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\WarehouseOperationBundle\Entity\InboundTask;
use Tourze\WarehouseOperationBundle\Event\AbstractTaskEvent;
use Tourze\WarehouseOperationBundle\Event\TaskAssignedEvent;
use Tourze\WarehouseOperationBundle\Event\TaskCompletedEvent;
use Tourze\WarehouseOperationBundle\Event\TaskCreatedEvent;
use Tourze\WarehouseOperationBundle\Event\TaskFailedEvent;
use Tourze\WarehouseOperationBundle\Event\TaskStartedEvent;

/**
 * @internal
 * 所有任务事件的集成测试
 */
#[CoversClass(AbstractTaskEvent::class)]
class TaskEventsTest extends TestCase
{
    public function testAllEventClassesAreProperlyDefined(): void
    {
        $eventClasses = [
            TaskCreatedEvent::class,
            TaskAssignedEvent::class,
            TaskStartedEvent::class,
            TaskCompletedEvent::class,
            TaskFailedEvent::class,
        ];

        foreach ($eventClasses as $eventClass) {
            $reflection = new \ReflectionClass($eventClass);
            $this->assertFalse($reflection->isAbstract(), "Event class {$eventClass} should not be abstract");
            $this->assertTrue($reflection->isInstantiable(), "Event class {$eventClass} should be instantiable");

            // 验证继承关系
            $this->assertTrue(
                $reflection->isSubclassOf('Tourze\WarehouseOperationBundle\Event\AbstractTaskEvent'),
                "Event class {$eventClass} should extend AbstractTaskEvent"
            );
        }
    }

    public function testAllEventsCanBeInstantiated(): void
    {
        $task = new InboundTask();
        $context = ['test' => 'data'];
        $workerId = 123;
        $completedAt = new \DateTimeImmutable();
        $assignedAt = new \DateTimeImmutable();
        $startedAt = new \DateTimeImmutable();
        $failureReason = 'Test failure reason';

        $events = [
            new TaskCreatedEvent($task, 'test_user', 'system', [], $context),
            new TaskAssignedEvent($task, $workerId, 'test_user', 'manual', [], $context),
            new TaskStartedEvent($task, $workerId, $startedAt, [], [], $context),
            new TaskCompletedEvent($task, $workerId, $completedAt, [], [], $context),
            new TaskFailedEvent($task, $failureReason, $startedAt, [], [], $context),
        ];

        foreach ($events as $event) {
            $this->assertSame($task, $event->getTask());
            $this->assertEquals($context, $event->getContext());
        }
    }
}
