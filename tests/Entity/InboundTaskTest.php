<?php

namespace Tourze\WarehouseOperationBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;
use Tourze\WarehouseOperationBundle\Entity\InboundTask;
use Tourze\WarehouseOperationBundle\Entity\WarehouseTask;
use Tourze\WarehouseOperationBundle\Enum\TaskStatus;
use Tourze\WarehouseOperationBundle\Enum\TaskType;

/**
 * @internal
 */
#[CoversClass(InboundTask::class)]
final class InboundTaskTest extends AbstractEntityTestCase
{
    protected function createEntity(): object
    {
        return new InboundTask();
    }

    /**
     * @return iterable<string, array{string, \DateTimeImmutable}>
     */
    public static function propertiesProvider(): iterable
    {
        return [
            'createTime' => ['createTime', new \DateTimeImmutable()],
            'updateTime' => ['updateTime', new \DateTimeImmutable()],
        ];
    }

    private InboundTask $task;

    protected function setUp(): void
    {
        parent::setUp();
        $this->task = new InboundTask();
    }

    public function testGetIdInitiallyNull(): void
    {
        $this->assertNull($this->task->getId());
    }

    public function testTypeGetterAndSetter(): void
    {
        $this->task->setType(TaskType::INBOUND);
        $this->assertSame(TaskType::INBOUND, $this->task->getType());

        $this->task->setType(TaskType::COUNT);
        $this->assertSame(TaskType::COUNT, $this->task->getType());
    }

    public function testStatusDefaultsToPending(): void
    {
        $this->assertSame(TaskStatus::PENDING, $this->task->getStatus());
    }

    public function testStatusGetterAndSetter(): void
    {
        $this->task->setStatus(TaskStatus::ASSIGNED);
        $this->assertSame(TaskStatus::ASSIGNED, $this->task->getStatus());

        $this->task->setStatus(TaskStatus::IN_PROGRESS);
        $this->assertSame(TaskStatus::IN_PROGRESS, $this->task->getStatus());

        $this->task->setStatus(TaskStatus::COMPLETED);
        $this->assertSame(TaskStatus::COMPLETED, $this->task->getStatus());
    }

    public function testPriorityDefaultsToOne(): void
    {
        $this->assertSame(1, $this->task->getPriority());
    }

    public function testPriorityGetterAndSetter(): void
    {
        $this->task->setPriority(3);
        $this->assertSame(3, $this->task->getPriority());

        $this->task->setPriority(8);
        $this->assertSame(8, $this->task->getPriority());
    }

    public function testDataDefaultsToEmptyArray(): void
    {
        $this->assertEquals([], $this->task->getData());
    }

    public function testDataGetterAndSetter(): void
    {
        $data = [
            'purchase_order_id' => 'PO001',
            'supplier_id' => 'SUP001',
            'expected_date' => '2024-01-15',
            'items' => [
                ['sku' => 'PROD001', 'qty' => 50],
                ['sku' => 'PROD002', 'qty' => 30],
            ],
        ];
        $this->task->setData($data);
        $this->assertEquals($data, $this->task->getData());

        // 测试更新数据
        $updatedData = array_merge($data, ['received_date' => '2024-01-16']);
        $this->task->setData($updatedData);
        $this->assertEquals($updatedData, $this->task->getData());
    }

    public function testAssignedWorkerInitiallyNull(): void
    {
        $this->assertNull($this->task->getAssignedWorker());
    }

    public function testAssignedWorkerGetterAndSetter(): void
    {
        $this->task->setAssignedWorker(456);
        $this->assertSame(456, $this->task->getAssignedWorker());

        $this->task->setAssignedWorker(null);
        $this->assertNull($this->task->getAssignedWorker());
    }

    public function testAssignedAtInitiallyNull(): void
    {
        $this->assertNull($this->task->getAssignedAt());
    }

    public function testAssignedAtGetterAndSetter(): void
    {
        $assignedAt = new \DateTimeImmutable('2024-01-15 09:00:00');
        $this->task->setAssignedAt($assignedAt);
        $this->assertSame($assignedAt, $this->task->getAssignedAt());

        $this->task->setAssignedAt(null);
        $this->assertNull($this->task->getAssignedAt());
    }

    public function testStartedAtInitiallyNull(): void
    {
        $this->assertNull($this->task->getStartedAt());
    }

    public function testStartedAtGetterAndSetter(): void
    {
        $startedAt = new \DateTimeImmutable('2024-01-15 10:30:00');
        $this->task->setStartedAt($startedAt);
        $this->assertSame($startedAt, $this->task->getStartedAt());
    }

    public function testCompletedAtInitiallyNull(): void
    {
        $this->assertNull($this->task->getCompletedAt());
    }

    public function testCompletedAtGetterAndSetter(): void
    {
        $completedAt = new \DateTimeImmutable('2024-01-15 15:45:00');
        $this->task->setCompletedAt($completedAt);
        $this->assertSame($completedAt, $this->task->getCompletedAt());
    }

    public function testNotesInitiallyNull(): void
    {
        $this->assertNull($this->task->getNotes());
    }

    public function testNotesGetterAndSetter(): void
    {
        $notes = '货物按时到达，质量良好，全部上架完成';
        $this->task->setNotes($notes);
        $this->assertSame($notes, $this->task->getNotes());

        $this->task->setNotes(null);
        $this->assertNull($this->task->getNotes());
    }

    public function testToStringMethod(): void
    {
        // 使用setId方法设置ID
        $this->task->setId(456);

        $this->task->setType(TaskType::INBOUND);

        $this->assertSame('Task #456 (inbound)', $this->task->__toString());
    }

    public function testTimestampableAwareTraitMethods(): void
    {
        $createTime = new \DateTimeImmutable('2024-01-15 08:00:00');
        $updateTime = new \DateTimeImmutable('2024-01-15 16:00:00');

        $this->task->setCreateTime($createTime);
        $this->assertSame($createTime, $this->task->getCreateTime());

        $this->task->setUpdateTime($updateTime);
        $this->assertSame($updateTime, $this->task->getUpdateTime());
    }

    public function testBlameableAwareTraitMethods(): void
    {
        $createdBy = 'warehouse_manager';
        $updatedBy = 'warehouse_worker';

        $this->task->setCreatedBy($createdBy);
        $this->assertSame($createdBy, $this->task->getCreatedBy());

        $this->task->setUpdatedBy($updatedBy);
        $this->assertSame($updatedBy, $this->task->getUpdatedBy());
    }

    public function testCanInstantiateInboundTask(): void
    {
        $inboundTask = new InboundTask();
        $this->assertInstanceOf(InboundTask::class, $inboundTask);
    }

    public function testSetterMethods(): void
    {
        $this->task->setType(TaskType::INBOUND);
        $this->task->setStatus(TaskStatus::IN_PROGRESS);
        $this->task->setPriority(3);
        $this->task->setData(['purchase_order' => 'PO001']);
        $this->task->setAssignedWorker(456);
        $this->task->setNotes('入库任务进行中');

        $this->assertSame(TaskType::INBOUND, $this->task->getType());
        $this->assertSame(TaskStatus::IN_PROGRESS, $this->task->getStatus());
        $this->assertSame(3, $this->task->getPriority());
        $this->assertEquals(['purchase_order' => 'PO001'], $this->task->getData());
        $this->assertSame(456, $this->task->getAssignedWorker());
        $this->assertSame('入库任务进行中', $this->task->getNotes());
    }

    public function testTaskWorkflowStates(): void
    {
        // 测试任务状态流转
        $this->assertSame(TaskStatus::PENDING, $this->task->getStatus());

        $this->task->setStatus(TaskStatus::ASSIGNED);
        $this->assertSame(TaskStatus::ASSIGNED, $this->task->getStatus());

        $this->task->setStatus(TaskStatus::IN_PROGRESS);
        $this->assertSame(TaskStatus::IN_PROGRESS, $this->task->getStatus());

        $this->task->setStatus(TaskStatus::COMPLETED);
        $this->assertSame(TaskStatus::COMPLETED, $this->task->getStatus());
    }
}
