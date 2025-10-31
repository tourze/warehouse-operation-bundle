<?php

namespace Tourze\WarehouseOperationBundle\Tests\Event;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\WarehouseOperationBundle\Entity\InboundTask;
use Tourze\WarehouseOperationBundle\Enum\TaskStatus;
use Tourze\WarehouseOperationBundle\Event\TaskCreatedEvent;

/**
 * TaskCreatedEvent 单元测试
 *
 * @internal
 */
#[CoversClass(TaskCreatedEvent::class)]
class TaskCreatedEventTest extends TestCase
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
        $creationData = [
            'reason' => 'stock_replenishment',
            'batch' => ['batch_id' => 'B001', 'items_count' => 50],
            'urgency_level' => 'high',
        ];
        $context = ['operator_id' => 101, 'session_id' => 'S123'];

        $event = new TaskCreatedEvent(
            $this->task,
            'system_admin',
            'api',
            $creationData,
            $context
        );

        $this->assertSame($this->task, $event->getTask());
        $this->assertSame('system_admin', $event->getCreatedBy());
        $this->assertSame('api', $event->getSource());
        $this->assertSame($creationData, $event->getCreationData());
        $this->assertSame($context, $event->getContext());
    }

    public function testConstructorWithDefaultParameters(): void
    {
        $event = new TaskCreatedEvent($this->task, 'user123');

        $this->assertSame($this->task, $event->getTask());
        $this->assertSame('user123', $event->getCreatedBy());
        $this->assertSame('system', $event->getSource());
        $this->assertSame([], $event->getCreationData());
        $this->assertSame([], $event->getContext());
    }

    public function testGetCreatedBy(): void
    {
        $event = new TaskCreatedEvent($this->task, 'admin_user');

        $this->assertSame('admin_user', $event->getCreatedBy());
    }

    public function testGetSource(): void
    {
        $event1 = new TaskCreatedEvent($this->task, 'user', 'manual');
        $this->assertSame('manual', $event1->getSource());

        $event2 = new TaskCreatedEvent($this->task, 'user');
        $this->assertSame('system', $event2->getSource());
    }

    public function testGetCreationData(): void
    {
        $creationData = [
            'imported_from' => 'legacy_system',
            'priority_reason' => 'urgent_customer_request',
            'estimated_duration' => 3600,
        ];

        $event = new TaskCreatedEvent($this->task, 'user', 'system', $creationData);

        $this->assertSame($creationData, $event->getCreationData());
    }

    public function testIsSystemCreated(): void
    {
        $systemEvent = new TaskCreatedEvent($this->task, 'user', 'system');
        $this->assertTrue($systemEvent->isSystemCreated());

        $manualEvent = new TaskCreatedEvent($this->task, 'user', 'manual');
        $this->assertFalse($manualEvent->isSystemCreated());
    }

    public function testIsManualCreated(): void
    {
        $manualEvent = new TaskCreatedEvent($this->task, 'user', 'manual');
        $this->assertTrue($manualEvent->isManualCreated());

        $systemEvent = new TaskCreatedEvent($this->task, 'user', 'system');
        $this->assertFalse($systemEvent->isManualCreated());
    }

    public function testIsApiCreated(): void
    {
        $apiEvent = new TaskCreatedEvent($this->task, 'user', 'api');
        $this->assertTrue($apiEvent->isApiCreated());

        $systemEvent = new TaskCreatedEvent($this->task, 'user', 'system');
        $this->assertFalse($systemEvent->isApiCreated());
    }

    public function testGetCreationReason(): void
    {
        $event1 = new TaskCreatedEvent(
            $this->task,
            'user',
            'system',
            ['reason' => 'inventory_adjustment']
        );
        $this->assertSame('inventory_adjustment', $event1->getCreationReason());

        $event2 = new TaskCreatedEvent($this->task, 'user');
        $this->assertNull($event2->getCreationReason());
    }

    public function testGetBatchInfo(): void
    {
        $batchInfo = [
            'batch_id' => 'BATCH-2025-001',
            'total_tasks' => 25,
            'priority_group' => 'high',
        ];

        $event1 = new TaskCreatedEvent(
            $this->task,
            'user',
            'system',
            ['batch' => $batchInfo]
        );
        $this->assertSame($batchInfo, $event1->getBatchInfo());

        $event2 = new TaskCreatedEvent($this->task, 'user');
        $this->assertNull($event2->getBatchInfo());
    }

    public function testComplexCreationScenario(): void
    {
        $creationData = [
            'reason' => 'emergency_stock_replenishment',
            'batch' => [
                'batch_id' => 'EMERGENCY-2025-0904',
                'items_count' => 150,
                'priority_group' => 'critical',
                'estimated_total_duration' => 18000,
            ],
            'urgency_level' => 'critical',
            'imported_from' => 'erp_system',
            'customer_request_id' => 'CR-789456',
        ];

        $context = [
            'operator_id' => 501,
            'supervisor_id' => 601,
            'session_id' => 'SESSION-789',
            'warehouse_zone' => 'A-ZONE',
            'timestamp' => '2025-09-04T22:40:00Z',
        ];

        $event = new TaskCreatedEvent(
            $this->task,
            'emergency_system',
            'api',
            $creationData,
            $context
        );

        $this->assertSame('emergency_system', $event->getCreatedBy());
        $this->assertSame('api', $event->getSource());
        $this->assertTrue($event->isApiCreated());
        $this->assertFalse($event->isSystemCreated());
        $this->assertFalse($event->isManualCreated());

        $this->assertSame('emergency_stock_replenishment', $event->getCreationReason());

        $batchInfo = $event->getBatchInfo();
        $this->assertNotNull($batchInfo);
        $this->assertSame('EMERGENCY-2025-0904', $batchInfo['batch_id']);
        $this->assertSame(150, $batchInfo['items_count']);
        $this->assertSame('critical', $batchInfo['priority_group']);

        $this->assertSame(501, $event->getContext()['operator_id']);
        $this->assertSame('CR-789456', $event->getCreationData()['customer_request_id']);
    }
}
