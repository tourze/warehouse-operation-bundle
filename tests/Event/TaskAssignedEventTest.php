<?php

namespace Tourze\WarehouseOperationBundle\Tests\Event;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\WarehouseOperationBundle\Entity\InboundTask;
use Tourze\WarehouseOperationBundle\Enum\TaskStatus;
use Tourze\WarehouseOperationBundle\Event\TaskAssignedEvent;

/**
 * TaskAssignedEvent 单元测试
 *
 * @internal
 */
#[CoversClass(TaskAssignedEvent::class)]
class TaskAssignedEventTest extends TestCase
{
    private InboundTask $task;

    protected function setUp(): void
    {
        parent::setUp();
        $this->task = new InboundTask();
        $this->task->setStatus(TaskStatus::PENDING);
        $this->task->setPriority(50);
    }

    public function testConstructor(): void
    {
        $assignmentData = [
            'reason' => 'skill_match',
            'skill_match_score' => 0.85,
            'estimated_completion' => new \DateTimeImmutable('2025-09-04 23:00:00'),
        ];
        $context = ['supervisor_id' => 201, 'zone' => 'A'];

        $event = new TaskAssignedEvent(
            $this->task,
            123,
            'supervisor_admin',
            'auto',
            $assignmentData,
            $context
        );

        $this->assertSame($this->task, $event->getTask());
        $this->assertSame(123, $event->getAssignedWorkerId());
        $this->assertSame('supervisor_admin', $event->getAssignedBy());
        $this->assertSame('auto', $event->getAssignmentMethod());
        $this->assertSame($assignmentData, $event->getAssignmentData());
        $this->assertSame($context, $event->getContext());
    }

    public function testConstructorWithDefaultParameters(): void
    {
        $event = new TaskAssignedEvent($this->task, 456, 'system');

        $this->assertSame($this->task, $event->getTask());
        $this->assertSame(456, $event->getAssignedWorkerId());
        $this->assertSame('system', $event->getAssignedBy());
        $this->assertSame('manual', $event->getAssignmentMethod());
        $this->assertSame([], $event->getAssignmentData());
        $this->assertSame([], $event->getContext());
    }

    public function testGetAssignedWorkerId(): void
    {
        $event = new TaskAssignedEvent($this->task, 789, 'supervisor');

        $this->assertSame(789, $event->getAssignedWorkerId());
    }

    public function testGetAssignedBy(): void
    {
        $event = new TaskAssignedEvent($this->task, 123, 'admin_user');

        $this->assertSame('admin_user', $event->getAssignedBy());
    }

    public function testGetAssignmentMethod(): void
    {
        $event1 = new TaskAssignedEvent($this->task, 123, 'system', 'auto');
        $this->assertSame('auto', $event1->getAssignmentMethod());

        $event2 = new TaskAssignedEvent($this->task, 123, 'system');
        $this->assertSame('manual', $event2->getAssignmentMethod());
    }

    public function testGetAssignmentData(): void
    {
        $assignmentData = [
            'priority_adjustment' => ['from' => 50, 'to' => 80],
            'workload_analysis' => ['current_tasks' => 3, 'capacity' => 5],
        ];

        $event = new TaskAssignedEvent($this->task, 123, 'system', 'manual', $assignmentData);

        $this->assertSame($assignmentData, $event->getAssignmentData());
    }

    public function testIsAutoAssignment(): void
    {
        $autoEvent = new TaskAssignedEvent($this->task, 123, 'system', 'auto');
        $this->assertTrue($autoEvent->isAutoAssignment());

        $manualEvent = new TaskAssignedEvent($this->task, 123, 'system', 'manual');
        $this->assertFalse($manualEvent->isAutoAssignment());
    }

    public function testIsManualAssignment(): void
    {
        $manualEvent = new TaskAssignedEvent($this->task, 123, 'system', 'manual');
        $this->assertTrue($manualEvent->isManualAssignment());

        $autoEvent = new TaskAssignedEvent($this->task, 123, 'system', 'auto');
        $this->assertFalse($autoEvent->isManualAssignment());
    }

    public function testIsSkillBasedAssignment(): void
    {
        $skillEvent = new TaskAssignedEvent($this->task, 123, 'system', 'skill_match');
        $this->assertTrue($skillEvent->isSkillBasedAssignment());

        $manualEvent = new TaskAssignedEvent($this->task, 123, 'system', 'manual');
        $this->assertFalse($manualEvent->isSkillBasedAssignment());
    }

    public function testGetAssignmentReason(): void
    {
        $event1 = new TaskAssignedEvent(
            $this->task,
            123,
            'system',
            'auto',
            ['reason' => 'workload_balancing']
        );
        $this->assertSame('workload_balancing', $event1->getAssignmentReason());

        $event2 = new TaskAssignedEvent($this->task, 123, 'system');
        $this->assertNull($event2->getAssignmentReason());
    }

    public function testGetSkillMatchScore(): void
    {
        $event1 = new TaskAssignedEvent(
            $this->task,
            123,
            'system',
            'skill_match',
            ['skill_match_score' => 0.92]
        );
        $this->assertSame(0.92, $event1->getSkillMatchScore());

        $event2 = new TaskAssignedEvent($this->task, 123, 'system');
        $this->assertNull($event2->getSkillMatchScore());
    }

    public function testGetEstimatedCompletionTime(): void
    {
        $completionTime = new \DateTimeImmutable('2025-09-04 23:30:00');

        $event1 = new TaskAssignedEvent(
            $this->task,
            123,
            'system',
            'auto',
            ['estimated_completion' => $completionTime]
        );
        $this->assertSame($completionTime, $event1->getEstimatedCompletionTime());

        $event2 = new TaskAssignedEvent(
            $this->task,
            123,
            'system',
            'auto',
            ['estimated_completion' => '2025-09-04T23:30:00+00:00']
        );
        $this->assertNull($event2->getEstimatedCompletionTime());

        $event3 = new TaskAssignedEvent($this->task, 123, 'system');
        $this->assertNull($event3->getEstimatedCompletionTime());
    }

    public function testGetPriorityAdjustment(): void
    {
        $priorityAdjustment = [
            'original_priority' => 50,
            'new_priority' => 80,
            'adjustment_reason' => 'urgent_customer_request',
        ];

        $event1 = new TaskAssignedEvent(
            $this->task,
            123,
            'supervisor',
            'manual',
            ['priority_adjustment' => $priorityAdjustment]
        );
        $this->assertSame($priorityAdjustment, $event1->getPriorityAdjustment());

        $event2 = new TaskAssignedEvent($this->task, 123, 'system');
        $this->assertNull($event2->getPriorityAdjustment());
    }

    public function testComplexAssignmentScenario(): void
    {
        $assignmentData = [
            'reason' => 'emergency_redistribution',
            'skill_match_score' => 0.95,
            'estimated_completion' => new \DateTimeImmutable('2025-09-04 23:45:00'),
            'priority_adjustment' => [
                'original_priority' => 50,
                'new_priority' => 90,
                'adjustment_reason' => 'critical_customer_issue',
            ],
            'workload_analysis' => [
                'current_tasks' => 2,
                'total_capacity' => 8,
                'efficiency_rating' => 0.88,
            ],
        ];

        $context = [
            'supervisor_id' => 301,
            'emergency_code' => 'EMG-001',
            'original_assignee' => 999,
            'reassignment_timestamp' => '2025-09-04T22:45:00Z',
        ];

        $event = new TaskAssignedEvent(
            $this->task,
            555,
            'emergency_supervisor',
            'skill_match',
            $assignmentData,
            $context
        );

        $this->assertSame(555, $event->getAssignedWorkerId());
        $this->assertSame('emergency_supervisor', $event->getAssignedBy());
        $this->assertSame('skill_match', $event->getAssignmentMethod());
        $this->assertTrue($event->isSkillBasedAssignment());
        $this->assertFalse($event->isAutoAssignment());
        $this->assertFalse($event->isManualAssignment());

        $this->assertSame('emergency_redistribution', $event->getAssignmentReason());
        $this->assertSame(0.95, $event->getSkillMatchScore());

        $priorityAdjustment = $event->getPriorityAdjustment();
        $this->assertNotNull($priorityAdjustment);
        $this->assertSame(90, $priorityAdjustment['new_priority']);
        $this->assertSame('critical_customer_issue', $priorityAdjustment['adjustment_reason']);

        $this->assertSame(301, $event->getContext()['supervisor_id']);
        $this->assertSame('EMG-001', $event->getContext()['emergency_code']);
    }
}
