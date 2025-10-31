<?php

namespace Tourze\WarehouseOperationBundle\Tests\Event;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\WarehouseOperationBundle\Entity\InboundTask;
use Tourze\WarehouseOperationBundle\Event\AbstractTaskEvent;
use Tourze\WarehouseOperationBundle\Event\TaskCompletedEvent;

/**
 * @internal
 */
#[CoversClass(TaskCompletedEvent::class)]
class TaskCompletedEventTest extends TestCase
{
    public function testConstructorAndGetters(): void
    {
        $task = new InboundTask();
        $workerId = 123;
        $completedAt = new \DateTimeImmutable();
        $completionResult = ['status' => 'success', 'items_processed' => 50];
        $performanceMetrics = ['duration' => 3600, 'accuracy' => 0.95];
        $context = ['completed_by' => 'worker_001', 'duration' => 3600, 'result' => 'success'];

        $event = new TaskCompletedEvent($task, $workerId, $completedAt, $completionResult, $performanceMetrics, $context);

        $this->assertSame($task, $event->getTask());
        $this->assertSame($context, $event->getContext());
        $this->assertSame($workerId, $event->getCompletedByWorkerId());
        $this->assertSame($completedAt, $event->getCompletedAt());
        $this->assertSame($completionResult, $event->getCompletionResult());
        $this->assertSame($performanceMetrics, $event->getPerformanceMetrics());
        $this->assertInstanceOf(AbstractTaskEvent::class, $event);
    }

    public function testConstructorWithDefaultContext(): void
    {
        $task = new InboundTask();
        $workerId = 456;
        $completedAt = new \DateTimeImmutable();

        $event = new TaskCompletedEvent($task, $workerId, $completedAt);

        $this->assertSame($task, $event->getTask());
        $this->assertSame([], $event->getContext());
        $this->assertSame($workerId, $event->getCompletedByWorkerId());
        $this->assertSame($completedAt, $event->getCompletedAt());
    }

    public function testConstructorWithEmptyContext(): void
    {
        $task = new InboundTask();
        $workerId = 789;
        $completedAt = new \DateTimeImmutable();
        $context = [];

        $event = new TaskCompletedEvent($task, $workerId, $completedAt, [], [], $context);

        $this->assertSame($task, $event->getTask());
        $this->assertSame($context, $event->getContext());
        $this->assertSame($workerId, $event->getCompletedByWorkerId());
        $this->assertSame($completedAt, $event->getCompletedAt());
    }

    public function testTaskObjectReference(): void
    {
        $task = new InboundTask();
        $task->setNotes('Initial notes');
        $workerId = 111;
        $completedAt = new \DateTimeImmutable();

        $event = new TaskCompletedEvent($task, $workerId, $completedAt);
        $retrievedTask = $event->getTask();

        // Should be the same object reference
        $this->assertSame($task, $retrievedTask);
        $this->assertEquals('Initial notes', $retrievedTask->getNotes());

        // Modify the task and verify the event reflects the change
        $task->setNotes('Updated notes');
        $this->assertEquals('Updated notes', $event->getTask()->getNotes());
    }

    public function testContextImmutability(): void
    {
        $task = new InboundTask();
        $workerId = 222;
        $completedAt = new \DateTimeImmutable();
        $context = ['completion_time' => '2023-12-01 10:00:00', 'quality_check' => true];

        $event = new TaskCompletedEvent($task, $workerId, $completedAt, [], [], $context);
        $retrievedContext = $event->getContext();

        // Context should be equal initially
        $this->assertEquals($context, $retrievedContext);

        // Modifying retrieved context shouldn't affect event's internal context
        $retrievedContext['new_field'] = 'new_value';
        $this->assertArrayNotHasKey('new_field', $event->getContext());
        $this->assertEquals($context, $event->getContext());
    }

    public function testEventInheritance(): void
    {
        $task = new InboundTask();
        $workerId = 333;
        $completedAt = new \DateTimeImmutable();
        $event = new TaskCompletedEvent($task, $workerId, $completedAt);

        $this->assertInstanceOf(AbstractTaskEvent::class, $event);
    }

    public function testWithComplexTaskData(): void
    {
        $task = new InboundTask();
        $task->setData(['items' => 50, 'location' => 'warehouse_a']);
        $task->setPriority(5);
        $workerId = 444;
        $completedAt = new \DateTimeImmutable();

        $context = [
            'actual_items' => 48,
            'discrepancy' => 2,
            'completion_notes' => 'Found minor discrepancy in count',
        ];

        $event = new TaskCompletedEvent($task, $workerId, $completedAt, [], [], $context);

        $this->assertSame($task, $event->getTask());
        $this->assertEquals(50, $event->getTask()->getData()['items']);
        $this->assertEquals(5, $event->getTask()->getPriority());
        $this->assertEquals($context, $event->getContext());
        $this->assertEquals(48, $event->getContext()['actual_items']);
    }
}
