<?php

namespace Tourze\WarehouseOperationBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;
use Tourze\WarehouseOperationBundle\Entity\OutboundTask;
use Tourze\WarehouseOperationBundle\Entity\WarehouseTask;
use Tourze\WarehouseOperationBundle\Enum\TaskStatus;
use Tourze\WarehouseOperationBundle\Enum\TaskType;

/**
 * @internal
 */
#[CoversClass(OutboundTask::class)]
final class OutboundTaskTest extends AbstractEntityTestCase
{
    protected function createEntity(): object
    {
        return new OutboundTask();
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

    private OutboundTask $task;

    protected function setUp(): void
    {
        parent::setUp();
        $this->task = new OutboundTask();
    }

    public function testGetIdInitiallyNull(): void
    {
        $this->assertNull($this->task->getId());
    }

    public function testTypeGetterAndSetter(): void
    {
        $this->task->setType(TaskType::OUTBOUND);
        $this->assertSame(TaskType::OUTBOUND, $this->task->getType());

        $this->task->setType(TaskType::TRANSFER);
        $this->assertSame(TaskType::TRANSFER, $this->task->getType());
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

        $this->task->setStatus(TaskStatus::CANCELLED);
        $this->assertSame(TaskStatus::CANCELLED, $this->task->getStatus());
    }

    public function testPriorityDefaultsToOne(): void
    {
        $this->assertSame(1, $this->task->getPriority());
    }

    public function testPriorityGetterAndSetter(): void
    {
        $this->task->setPriority(7);
        $this->assertSame(7, $this->task->getPriority());

        $this->task->setPriority(2);
        $this->assertSame(2, $this->task->getPriority());
    }

    public function testDataDefaultsToEmptyArray(): void
    {
        $this->assertEquals([], $this->task->getData());
    }

    public function testDataGetterAndSetter(): void
    {
        $data = [
            'sales_order_id' => 'SO001',
            'customer_id' => 'CUST001',
            'shipping_date' => '2024-01-20',
            'items' => [
                ['sku' => 'PROD003', 'qty' => 25, 'picked_qty' => 0],
                ['sku' => 'PROD004', 'qty' => 15, 'picked_qty' => 0],
            ],
            'shipping_address' => [
                'street' => '123 Main St',
                'city' => 'Shanghai',
                'country' => 'CN',
            ],
        ];
        $this->task->setData($data);
        $this->assertEquals($data, $this->task->getData());

        // 测试更新拣货数量
        $data['items'][0]['picked_qty'] = 25;
        $data['items'][1]['picked_qty'] = 15;
        $this->task->setData($data);
        $this->assertEquals($data, $this->task->getData());
    }

    public function testAssignedWorkerInitiallyNull(): void
    {
        $this->assertNull($this->task->getAssignedWorker());
    }

    public function testAssignedWorkerGetterAndSetter(): void
    {
        $this->task->setAssignedWorker(789);
        $this->assertSame(789, $this->task->getAssignedWorker());

        $this->task->setAssignedWorker(null);
        $this->assertNull($this->task->getAssignedWorker());
    }

    public function testAssignedAtInitiallyNull(): void
    {
        $this->assertNull($this->task->getAssignedAt());
    }

    public function testAssignedAtGetterAndSetter(): void
    {
        $assignedAt = new \DateTimeImmutable('2024-01-20 08:30:00');
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
        $startedAt = new \DateTimeImmutable('2024-01-20 09:15:00');
        $this->task->setStartedAt($startedAt);
        $this->assertSame($startedAt, $this->task->getStartedAt());
    }

    public function testCompletedAtInitiallyNull(): void
    {
        $this->assertNull($this->task->getCompletedAt());
    }

    public function testCompletedAtGetterAndSetter(): void
    {
        $completedAt = new \DateTimeImmutable('2024-01-20 11:30:00');
        $this->task->setCompletedAt($completedAt);
        $this->assertSame($completedAt, $this->task->getCompletedAt());
    }

    public function testNotesInitiallyNull(): void
    {
        $this->assertNull($this->task->getNotes());
    }

    public function testNotesGetterAndSetter(): void
    {
        $notes = '订单拣货完成，已打包准备发货';
        $this->task->setNotes($notes);
        $this->assertSame($notes, $this->task->getNotes());

        $this->task->setNotes(null);
        $this->assertNull($this->task->getNotes());
    }

    public function testToStringMethod(): void
    {
        // 使用setId方法设置ID
        $this->task->setId(789);

        $this->task->setType(TaskType::OUTBOUND);

        $this->assertSame('Task #789 (outbound)', $this->task->__toString());
    }

    public function testTimestampableAwareTraitMethods(): void
    {
        $createTime = new \DateTimeImmutable('2024-01-20 07:00:00');
        $updateTime = new \DateTimeImmutable('2024-01-20 12:00:00');

        $this->task->setCreateTime($createTime);
        $this->assertSame($createTime, $this->task->getCreateTime());

        $this->task->setUpdateTime($updateTime);
        $this->assertSame($updateTime, $this->task->getUpdateTime());
    }

    public function testBlameableAwareTraitMethods(): void
    {
        $createdBy = 'sales_manager';
        $updatedBy = 'picker_001';

        $this->task->setCreatedBy($createdBy);
        $this->assertSame($createdBy, $this->task->getCreatedBy());

        $this->task->setUpdatedBy($updatedBy);
        $this->assertSame($updatedBy, $this->task->getUpdatedBy());
    }

    public function testCanInstantiateOutboundTask(): void
    {
        $outboundTask = new OutboundTask();
        $this->assertInstanceOf(OutboundTask::class, $outboundTask);
    }

    public function testSetterMethods(): void
    {
        $this->task->setType(TaskType::OUTBOUND);
        $this->task->setStatus(TaskStatus::IN_PROGRESS);
        $this->task->setPriority(7);
        $this->task->setData(['sales_order' => 'SO001']);
        $this->task->setAssignedWorker(789);
        $this->task->setNotes('出库任务拣货中');

        $this->assertSame(TaskType::OUTBOUND, $this->task->getType());
        $this->assertSame(TaskStatus::IN_PROGRESS, $this->task->getStatus());
        $this->assertSame(7, $this->task->getPriority());
        $this->assertEquals(['sales_order' => 'SO001'], $this->task->getData());
        $this->assertSame(789, $this->task->getAssignedWorker());
        $this->assertSame('出库任务拣货中', $this->task->getNotes());
    }

    public function testPickingWorkflowStates(): void
    {
        // 测试拣货任务状态流转
        $this->assertSame(TaskStatus::PENDING, $this->task->getStatus());

        $this->task->setStatus(TaskStatus::ASSIGNED);
        $this->assertSame(TaskStatus::ASSIGNED, $this->task->getStatus());

        $this->task->setStatus(TaskStatus::IN_PROGRESS);
        $this->assertSame(TaskStatus::IN_PROGRESS, $this->task->getStatus());

        $this->task->setStatus(TaskStatus::COMPLETED);
        $this->assertSame(TaskStatus::COMPLETED, $this->task->getStatus());
    }

    public function testTaskCancellation(): void
    {
        $this->task->setStatus(TaskStatus::ASSIGNED);
        $this->task->setStatus(TaskStatus::CANCELLED);

        $this->assertSame(TaskStatus::CANCELLED, $this->task->getStatus());
    }
}
