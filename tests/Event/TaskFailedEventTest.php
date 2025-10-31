<?php

namespace Tourze\WarehouseOperationBundle\Tests\Event;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\WarehouseOperationBundle\Entity\InboundTask;
use Tourze\WarehouseOperationBundle\Event\AbstractTaskEvent;
use Tourze\WarehouseOperationBundle\Event\TaskFailedEvent;

/**
 * @internal
 */
#[CoversClass(TaskFailedEvent::class)]
class TaskFailedEventTest extends TestCase
{
    public function testConstructorAndGetters(): void
    {
        $task = new InboundTask();
        $failureReason = 'Task exceeded maximum execution time';
        $failedAt = new \DateTimeImmutable();
        $failureDetails = ['error_code' => 'TIMEOUT', 'retry_count' => 2];
        $impactAnalysis = ['affected_orders' => 3, 'delay_minutes' => 30];
        $context = [
            'error_code' => 'TIMEOUT',
            'error_message' => 'Task exceeded maximum execution time',
            'failed_by' => 'worker_003',
            'retry_count' => 2,
        ];

        $event = new TaskFailedEvent($task, $failureReason, $failedAt, $failureDetails, $impactAnalysis, $context);

        $this->assertSame($task, $event->getTask());
        $this->assertSame($context, $event->getContext());
        $this->assertSame($failureReason, $event->getFailureReason());
        $this->assertSame($failedAt, $event->getFailedAt());
        $this->assertSame($failureDetails, $event->getFailureDetails());
        $this->assertSame($impactAnalysis, $event->getImpactAnalysis());
        $this->assertInstanceOf(AbstractTaskEvent::class, $event);
    }

    public function testConstructorWithDefaultContext(): void
    {
        $task = new InboundTask();
        $failureReason = 'Default failure';
        $failedAt = new \DateTimeImmutable();

        $event = new TaskFailedEvent($task, $failureReason, $failedAt);

        $this->assertSame($task, $event->getTask());
        $this->assertSame([], $event->getContext());
        $this->assertSame($failureReason, $event->getFailureReason());
        $this->assertSame($failedAt, $event->getFailedAt());
    }

    public function testConstructorWithEmptyContext(): void
    {
        $task = new InboundTask();
        $failureReason = 'Empty context failure';
        $failedAt = new \DateTimeImmutable();
        $context = [];

        $event = new TaskFailedEvent($task, $failureReason, $failedAt, [], [], $context);

        $this->assertSame($task, $event->getTask());
        $this->assertSame($context, $event->getContext());
        $this->assertSame($failureReason, $event->getFailureReason());
        $this->assertSame($failedAt, $event->getFailedAt());
    }

    public function testTaskObjectReference(): void
    {
        $task = new InboundTask();
        $task->setNotes('Task with potential issues');
        $originalNotes = $task->getNotes();
        $failureReason = 'Task reference test failure';
        $failedAt = new \DateTimeImmutable();

        $event = new TaskFailedEvent($task, $failureReason, $failedAt);
        $retrievedTask = $event->getTask();

        // Should be the same object reference
        $this->assertSame($task, $retrievedTask);
        $this->assertEquals($originalNotes, $retrievedTask->getNotes());

        // Modifying the task should be reflected in the event
        $task->setNotes('Task failed with errors');
        $this->assertEquals('Task failed with errors', $event->getTask()->getNotes());
        $this->assertNotEquals($originalNotes, $event->getTask()->getNotes());
    }

    public function testContextDataIntegrity(): void
    {
        $task = new InboundTask();
        $context = [
            'exception' => 'RuntimeException: Connection refused',
            'stack_trace' => 'at line 42 in TaskProcessor.php',
            'system_status' => 'degraded',
            'impact_level' => 'high',
        ];

        $event = new TaskFailedEvent($task, 'Context test failure', new \DateTimeImmutable(), [], [], $context);
        $retrievedContext = $event->getContext();

        // Context should be preserved exactly
        $this->assertEquals($context, $retrievedContext);
        $this->assertEquals('RuntimeException: Connection refused', $retrievedContext['exception']);
        $this->assertEquals('high', $retrievedContext['impact_level']);

        // Modifying retrieved context shouldn't affect the event's internal context
        $retrievedContext['resolved'] = true;
        $this->assertArrayNotHasKey('resolved', $event->getContext());
        $this->assertEquals($context, $event->getContext());
    }

    public function testEventInheritance(): void
    {
        $task = new InboundTask();
        $event = new TaskFailedEvent($task, 'Inheritance test failure', new \DateTimeImmutable());

        $this->assertInstanceOf(AbstractTaskEvent::class, $event);
        $this->assertInstanceOf(TaskFailedEvent::class, $event);
    }

    public function testFailureWithTaskCompleteData(): void
    {
        $task = new InboundTask();
        $task->setPriority(8);
        $task->setData([
            'expected_items' => 200,
            'processed_items' => 150,
            'location' => 'dock_b',
        ]);
        $task->setNotes('High priority inbound task');

        $context = [
            'failure_reason' => 'equipment_malfunction',
            'equipment_id' => 'scanner_007',
            'remaining_items' => 50,
            'supervisor_notified' => true,
            'estimated_delay' => '2 hours',
        ];

        $event = new TaskFailedEvent($task, 'Complete data test failure', new \DateTimeImmutable(), [], [], $context);

        // Verify task data integrity
        $this->assertSame($task, $event->getTask());
        $this->assertEquals(8, $event->getTask()->getPriority());
        $this->assertEquals(200, $event->getTask()->getData()['expected_items']);
        $this->assertEquals('dock_b', $event->getTask()->getData()['location']);

        // Verify failure context
        $this->assertEquals($context, $event->getContext());
        $this->assertEquals('equipment_malfunction', $event->getContext()['failure_reason']);
        $this->assertEquals(50, $event->getContext()['remaining_items']);
        $this->assertTrue($event->getContext()['supervisor_notified']);
    }

    public function testNestedContextData(): void
    {
        $task = new InboundTask();
        $context = [
            'error' => [
                'type' => 'ValidationError',
                'message' => 'Invalid barcode format',
                'details' => [
                    'expected_format' => 'EAN-13',
                    'received_format' => 'invalid',
                    'barcode_value' => '12345',
                ],
            ],
            'recovery' => [
                'attempted' => true,
                'success' => false,
                'next_action' => 'manual_intervention',
            ],
        ];

        $event = new TaskFailedEvent($task, 'Complex structure test failure', new \DateTimeImmutable(), [], [], $context);

        $eventContext = $event->getContext();
        $this->assertEquals($context, $eventContext);
        $this->assertIsArray($eventContext);
        $this->assertArrayHasKey('error', $eventContext);
        $this->assertIsArray($eventContext['error']);
        $this->assertArrayHasKey('type', $eventContext['error']);
        $this->assertEquals('ValidationError', $eventContext['error']['type']);
        $this->assertArrayHasKey('details', $eventContext['error']);
        $this->assertIsArray($eventContext['error']['details']);
        $this->assertArrayHasKey('expected_format', $eventContext['error']['details']);
        $this->assertEquals('EAN-13', $eventContext['error']['details']['expected_format']);
        $this->assertArrayHasKey('recovery', $eventContext);
        $this->assertIsArray($eventContext['recovery']);
        $this->assertArrayHasKey('success', $eventContext['recovery']);
        $this->assertFalse($eventContext['recovery']['success']);
    }

    public function testMultipleFailureEvents(): void
    {
        $task = new InboundTask();

        $context1 = ['attempt' => 1, 'error' => 'network_timeout'];
        $context2 = ['attempt' => 2, 'error' => 'database_connection_failed'];

        $event1 = new TaskFailedEvent($task, 'Multiple events test failure 1', new \DateTimeImmutable(), [], [], $context1);
        $event2 = new TaskFailedEvent($task, 'Multiple events test failure 2', new \DateTimeImmutable(), [], [], $context2);

        // Both events should reference the same task
        $this->assertSame($task, $event1->getTask());
        $this->assertSame($task, $event2->getTask());

        // But have different failure contexts
        $this->assertNotEquals($event1->getContext(), $event2->getContext());
        $this->assertEquals(1, $event1->getContext()['attempt']);
        $this->assertEquals(2, $event2->getContext()['attempt']);
        $this->assertEquals('network_timeout', $event1->getContext()['error']);
        $this->assertEquals('database_connection_failed', $event2->getContext()['error']);
    }

    public function testCanRetry(): void
    {
        $task = new InboundTask();

        // Test can retry - normal failure with low retry count
        $failureDetails = ['retry_count' => 1, 'max_retries' => 3, 'severity' => 'medium'];
        $event = new TaskFailedEvent($task, 'Retryable failure', new \DateTimeImmutable(), $failureDetails);

        $this->assertTrue($event->canRetry());

        // Test cannot retry - critical failure
        $failureDetails = ['retry_count' => 1, 'max_retries' => 3, 'severity' => 'critical'];
        $event = new TaskFailedEvent($task, 'Critical failure', new \DateTimeImmutable(), $failureDetails);

        $this->assertFalse($event->canRetry());

        // Test cannot retry - max retries exceeded
        $failureDetails = ['retry_count' => 3, 'max_retries' => 3, 'severity' => 'medium'];
        $event = new TaskFailedEvent($task, 'Max retries reached', new \DateTimeImmutable(), $failureDetails);

        $this->assertFalse($event->canRetry());
    }

    public function testRequiresManagerApproval(): void
    {
        $task = new InboundTask();

        // Test requires approval - critical failure
        $failureDetails = ['retry_count' => 1, 'severity' => 'critical'];
        $event = new TaskFailedEvent($task, 'Critical failure', new \DateTimeImmutable(), $failureDetails);

        $this->assertTrue($event->requiresManagerApproval());

        // Test requires approval - high retry count
        $failureDetails = ['retry_count' => 2, 'severity' => 'medium'];
        $event = new TaskFailedEvent($task, 'High retry count', new \DateTimeImmutable(), $failureDetails);

        $this->assertTrue($event->requiresManagerApproval());

        // Test requires approval - affected tasks
        $impactAnalysis = ['affected_tasks' => [1, 2, 3]];
        $event = new TaskFailedEvent($task, 'Affected tasks', new \DateTimeImmutable(), [], $impactAnalysis);

        $this->assertTrue($event->requiresManagerApproval());

        // Test does not require approval - normal failure
        $failureDetails = ['retry_count' => 1, 'severity' => 'medium'];
        $event = new TaskFailedEvent($task, 'Normal failure', new \DateTimeImmutable(), $failureDetails);

        $this->assertFalse($event->requiresManagerApproval());
    }
}
