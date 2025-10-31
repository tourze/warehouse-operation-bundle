<?php

namespace Tourze\WarehouseOperationBundle\Tests\Event;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\WarehouseOperationBundle\Entity\InboundTask;
use Tourze\WarehouseOperationBundle\Event\AbstractTaskEvent;
use Tourze\WarehouseOperationBundle\Event\TaskStartedEvent;

/**
 * @internal
 */
#[CoversClass(TaskStartedEvent::class)]
class TaskStartedEventTest extends TestCase
{
    public function testConstructorAndGetters(): void
    {
        $task = new InboundTask();
        $workerId = 555;
        $startedAt = new \DateTimeImmutable();
        $initialState = ['pre_conditions' => 'met', 'equipment_ready' => true];
        $environmentData = ['temperature' => 22.5, 'humidity' => 45];
        $context = [
            'started_by' => 'worker_005',
            'start_location' => 'dock_a',
            'estimated_duration' => 1800,
            'tools_assigned' => ['scanner_001', 'forklift_003'],
        ];

        $event = new TaskStartedEvent($task, $workerId, $startedAt, $initialState, $environmentData, $context);

        $this->assertSame($task, $event->getTask());
        $this->assertEquals($context, $event->getContext());
        $this->assertSame($workerId, $event->getStartedByWorkerId());
        $this->assertSame($startedAt, $event->getStartedAt());
        $this->assertSame($initialState, $event->getInitialState());
        $this->assertSame($environmentData, $event->getEnvironmentData());
        $this->assertInstanceOf(AbstractTaskEvent::class, $event);
    }

    public function testConstructorWithDefaultContext(): void
    {
        $task = new InboundTask();
        $workerId = 666;
        $startedAt = new \DateTimeImmutable();

        $event = new TaskStartedEvent($task, $workerId, $startedAt);

        $this->assertSame($task, $event->getTask());
        $this->assertSame($workerId, $event->getStartedByWorkerId());
        $this->assertSame($startedAt, $event->getStartedAt());
        $this->assertEquals([], $event->getContext());
    }

    public function testConstructorWithEmptyContext(): void
    {
        $task = new InboundTask();
        $workerId = 777;
        $startedAt = new \DateTimeImmutable();
        $context = [];

        $event = new TaskStartedEvent($task, $workerId, $startedAt, [], [], $context);

        $this->assertSame($task, $event->getTask());
        $this->assertEquals($context, $event->getContext());
        $this->assertSame($workerId, $event->getStartedByWorkerId());
        $this->assertSame($startedAt, $event->getStartedAt());
    }

    public function testTaskObjectReference(): void
    {
        $task = new InboundTask();
        $task->setPriority(7);
        $originalPriority = $task->getPriority();
        $workerId = 888;
        $startedAt = new \DateTimeImmutable();

        $event = new TaskStartedEvent($task, $workerId, $startedAt);
        $retrievedTask = $event->getTask();

        // Should be the same object reference
        $this->assertSame($task, $retrievedTask);
        $this->assertSame($originalPriority, $retrievedTask->getPriority());

        // Changes to task should be reflected in event
        $task->setPriority(9);
        $this->assertSame(9, $event->getTask()->getPriority());
        $this->assertNotEquals($originalPriority, $event->getTask()->getPriority());
    }

    public function testContextIsolation(): void
    {
        $task = new InboundTask();
        $context = [
            'start_time' => '2023-12-01 14:30:00',
            'shift' => 'afternoon',
            'weather_conditions' => 'clear',
        ];

        $event = new TaskStartedEvent($task, 123, new \DateTimeImmutable(), [], [], $context);
        $retrievedContext = $event->getContext();

        // Context should match initially
        $this->assertEquals($context, $retrievedContext);
        $this->assertSame('afternoon', $retrievedContext['shift']);

        // Modifying retrieved context shouldn't affect event's internal context
        $retrievedContext['shift'] = 'evening';
        $retrievedContext['additional_info'] = 'modified';

        // Event's context should remain unchanged
        $this->assertEquals($context, $event->getContext());
        $this->assertSame('afternoon', $event->getContext()['shift']);
        $this->assertArrayNotHasKey('additional_info', $event->getContext());
    }

    public function testEventInheritance(): void
    {
        $task = new InboundTask();
        $event = new TaskStartedEvent($task, 123, new \DateTimeImmutable());

        $this->assertInstanceOf(AbstractTaskEvent::class, $event);
        $this->assertInstanceOf(TaskStartedEvent::class, $event);
    }

    public function testStartEventWithCompleteTaskData(): void
    {
        $task = new InboundTask();
        $task->setPriority(4);
        $task->setData([
            'supplier' => 'supplier_xyz',
            'expected_items' => 75,
            'delivery_truck' => 'TRK-456',
            'bay_assignment' => 'bay_3',
        ]);
        $task->setNotes('Urgent delivery from supplier_xyz');

        $context = [
            'worker_experience' => 'senior',
            'start_checklist_completed' => true,
            'safety_briefing' => true,
            'equipment_status' => 'operational',
            'concurrent_tasks' => ['task_123', 'task_124'],
        ];

        $event = new TaskStartedEvent($task, 123, new \DateTimeImmutable(), [], [], $context);

        // Verify task properties
        $this->assertSame($task, $event->getTask());
        $this->assertSame(4, $event->getTask()->getPriority());
        $this->assertSame('supplier_xyz', $event->getTask()->getData()['supplier']);
        $this->assertSame(75, $event->getTask()->getData()['expected_items']);
        $this->assertSame('Urgent delivery from supplier_xyz', $event->getTask()->getNotes());

        // Verify start context
        $eventContext = $event->getContext();
        $this->assertEquals($context, $eventContext);
        $this->assertIsArray($eventContext);
        $this->assertArrayHasKey('worker_experience', $eventContext);
        $this->assertSame('senior', $eventContext['worker_experience']);
        $this->assertArrayHasKey('start_checklist_completed', $eventContext);
        $this->assertTrue($eventContext['start_checklist_completed']);
        $this->assertArrayHasKey('safety_briefing', $eventContext);
        $this->assertTrue($eventContext['safety_briefing']);
        $this->assertArrayHasKey('concurrent_tasks', $eventContext);
        $this->assertIsArray($eventContext['concurrent_tasks']);
        $this->assertCount(2, $eventContext['concurrent_tasks']);
    }

    public function testComplexContextStructure(): void
    {
        $task = new InboundTask();
        $context = [
            'worker' => [
                'id' => 'worker_005',
                'name' => 'John Doe',
                'certification' => ['forklift', 'hazmat'],
                'shift_start' => '08:00',
            ],
            'environment' => [
                'temperature' => 22,
                'humidity' => 65,
                'lighting' => 'optimal',
            ],
            'resources' => [
                'equipment' => ['scanner', 'cart', 'safety_vest'],
                'consumables' => ['labels', 'tape'],
                'availability' => 'full',
            ],
        ];

        $event = new TaskStartedEvent($task, 123, new \DateTimeImmutable(), [], [], $context);

        $eventContext = $event->getContext();
        $this->assertEquals($context, $eventContext);
        $this->assertIsArray($eventContext);

        // Verify worker information
        $this->assertArrayHasKey('worker', $eventContext);
        $this->assertIsArray($eventContext['worker']);
        $this->assertArrayHasKey('id', $eventContext['worker']);
        $this->assertSame('worker_005', $eventContext['worker']['id']);
        $this->assertArrayHasKey('certification', $eventContext['worker']);
        $this->assertIsArray($eventContext['worker']['certification']);
        $this->assertContainsEquals('forklift', $eventContext['worker']['certification']);

        // Verify environment information
        $this->assertArrayHasKey('environment', $eventContext);
        $this->assertIsArray($eventContext['environment']);
        $this->assertArrayHasKey('temperature', $eventContext['environment']);
        $this->assertSame(22, $eventContext['environment']['temperature']);

        // Verify resources information
        $this->assertArrayHasKey('resources', $eventContext);
        $this->assertIsArray($eventContext['resources']);
        $this->assertArrayHasKey('equipment', $eventContext['resources']);
        $this->assertIsArray($eventContext['resources']['equipment']);
        $this->assertContainsEquals('scanner', $eventContext['resources']['equipment']);
    }

    public function testMultipleStartEvents(): void
    {
        $task = new InboundTask();

        $context1 = ['attempt' => 1, 'worker' => 'worker_001'];
        $context2 = ['attempt' => 2, 'worker' => 'worker_002'];

        $event1 = new TaskStartedEvent($task, 123, new \DateTimeImmutable(), [], [], $context1);
        $event2 = new TaskStartedEvent($task, 456, new \DateTimeImmutable(), [], [], $context2);

        // Both events should reference the same task
        $this->assertSame($task, $event1->getTask());
        $this->assertSame($task, $event2->getTask());
        $this->assertSame($event1->getTask(), $event2->getTask());

        // But have different start contexts
        $this->assertNotEquals($event1->getContext(), $event2->getContext());
        $this->assertSame(1, $event1->getContext()['attempt']);
        $this->assertSame(2, $event2->getContext()['attempt']);
        $this->assertSame('worker_001', $event1->getContext()['worker']);
        $this->assertSame('worker_002', $event2->getContext()['worker']);
    }

    public function testEventWithMinimalData(): void
    {
        $task = new InboundTask();
        $context = ['started' => true];

        $event = new TaskStartedEvent($task, 123, new \DateTimeImmutable(), [], [], $context);

        $this->assertSame($task, $event->getTask());
        $this->assertEquals(['started' => true], $event->getContext());
        $this->assertTrue($event->getContext()['started']);
    }

    public function testTaskStateConsistency(): void
    {
        $task = new InboundTask();
        $task->setData(['initial' => 'value']);

        $event = new TaskStartedEvent($task, 123, new \DateTimeImmutable(), [], [], ['event_data' => 'test']);

        // Modifying task data after event creation
        $task->setData(['modified' => 'value']);

        // Event should reflect the current state of the task object
        $this->assertSame(['modified' => 'value'], $event->getTask()->getData());
        $this->assertArrayNotHasKey('initial', $event->getTask()->getData());

        // But context should remain unchanged
        $this->assertEquals(['event_data' => 'test'], $event->getContext());
    }

    public function testPassedPreConditions(): void
    {
        $task = new InboundTask();
        $workerId = 123;
        $startedAt = new \DateTimeImmutable();

        // Test with passed pre-conditions
        $initialStatePass = ['precondition_check' => ['passed' => true]];
        $eventPass = new TaskStartedEvent($task, $workerId, $startedAt, $initialStatePass);
        $this->assertTrue($eventPass->passedPreConditions());

        // Test with failed pre-conditions
        $initialStateFail = ['precondition_check' => ['passed' => false]];
        $eventFail = new TaskStartedEvent($task, $workerId, $startedAt, $initialStateFail);
        $this->assertFalse($eventFail->passedPreConditions());

        // Test with no pre-conditions
        $initialStateEmpty = [];
        $eventEmpty = new TaskStartedEvent($task, $workerId, $startedAt, $initialStateEmpty);
        $this->assertFalse($eventEmpty->passedPreConditions());
    }
}
