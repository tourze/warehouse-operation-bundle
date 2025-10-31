<?php

namespace Tourze\WarehouseOperationBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;
use Tourze\WarehouseOperationBundle\Entity\TransferTask;
use Tourze\WarehouseOperationBundle\Entity\WarehouseTask;
use Tourze\WarehouseOperationBundle\Enum\TaskStatus;
use Tourze\WarehouseOperationBundle\Enum\TaskType;

/**
 * @internal
 */
#[CoversClass(TransferTask::class)]
final class TransferTaskTest extends AbstractEntityTestCase
{
    protected function createEntity(): object
    {
        return new TransferTask();
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

    private TransferTask $task;

    protected function setUp(): void
    {
        parent::setUp();
        $this->task = new TransferTask();
    }

    public function testGetIdInitiallyNull(): void
    {
        $this->assertNull($this->task->getId());
    }

    public function testTypeGetterAndSetter(): void
    {
        $this->task->setType(TaskType::TRANSFER);
        $this->assertSame(TaskType::TRANSFER, $this->task->getType());

        $this->task->setType(TaskType::OUTBOUND);
        $this->assertSame(TaskType::OUTBOUND, $this->task->getType());
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

        $this->task->setStatus(TaskStatus::PAUSED);
        $this->assertSame(TaskStatus::PAUSED, $this->task->getStatus());

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
        $this->task->setPriority(6);
        $this->assertSame(6, $this->task->getPriority());

        $this->task->setPriority(3);
        $this->assertSame(3, $this->task->getPriority());
    }

    public function testDataDefaultsToEmptyArray(): void
    {
        $this->assertEquals([], $this->task->getData());
    }

    public function testDataGetterAndSetter(): void
    {
        $data = [
            'transfer_order_id' => 'TO001',
            'from_location' => [
                'warehouse_id' => 'WH001',
                'zone_id' => 'ZONE_A',
                'shelf_id' => 'SHELF_001',
                'location_id' => 'LOC_A01',
            ],
            'to_location' => [
                'warehouse_id' => 'WH002',
                'zone_id' => 'ZONE_B',
                'shelf_id' => 'SHELF_002',
                'location_id' => 'LOC_B01',
            ],
            'items' => [
                [
                    'sku' => 'PROD006',
                    'qty' => 20,
                    'transferred_qty' => 0,
                    'reason' => 'stock_rebalancing',
                ],
            ],
            'transfer_type' => 'internal_transfer',
        ];
        $this->task->setData($data);
        $this->assertEquals($data, $this->task->getData());

        // 测试更新转移数量
        $data['items'][0]['transferred_qty'] = 20;
        $this->task->setData($data);
        $this->assertEquals($data, $this->task->getData());
    }

    public function testAssignedWorkerInitiallyNull(): void
    {
        $this->assertNull($this->task->getAssignedWorker());
    }

    public function testAssignedWorkerGetterAndSetter(): void
    {
        $this->task->setAssignedWorker(202);
        $this->assertSame(202, $this->task->getAssignedWorker());

        $this->task->setAssignedWorker(null);
        $this->assertNull($this->task->getAssignedWorker());
    }

    public function testAssignedAtInitiallyNull(): void
    {
        $this->assertNull($this->task->getAssignedAt());
    }

    public function testAssignedAtGetterAndSetter(): void
    {
        $assignedAt = new \DateTimeImmutable('2024-01-22 09:00:00');
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
        $startedAt = new \DateTimeImmutable('2024-01-22 10:15:00');
        $this->task->setStartedAt($startedAt);
        $this->assertSame($startedAt, $this->task->getStartedAt());
    }

    public function testCompletedAtInitiallyNull(): void
    {
        $this->assertNull($this->task->getCompletedAt());
    }

    public function testCompletedAtGetterAndSetter(): void
    {
        $completedAt = new \DateTimeImmutable('2024-01-22 14:30:00');
        $this->task->setCompletedAt($completedAt);
        $this->assertSame($completedAt, $this->task->getCompletedAt());
    }

    public function testNotesInitiallyNull(): void
    {
        $this->assertNull($this->task->getNotes());
    }

    public function testNotesGetterAndSetter(): void
    {
        $notes = '库存转移完成，目标库位已确认收货';
        $this->task->setNotes($notes);
        $this->assertSame($notes, $this->task->getNotes());

        $this->task->setNotes(null);
        $this->assertNull($this->task->getNotes());
    }

    public function testToStringMethod(): void
    {
        // 使用setId方法设置ID
        $this->task->setId(202);

        $this->task->setType(TaskType::TRANSFER);

        $this->assertSame('Task #202 (transfer)', $this->task->__toString());
    }

    public function testTimestampableAwareTraitMethods(): void
    {
        $createTime = new \DateTimeImmutable('2024-01-22 08:30:00');
        $updateTime = new \DateTimeImmutable('2024-01-22 15:00:00');

        $this->task->setCreateTime($createTime);
        $this->assertSame($createTime, $this->task->getCreateTime());

        $this->task->setUpdateTime($updateTime);
        $this->assertSame($updateTime, $this->task->getUpdateTime());
    }

    public function testBlameableAwareTraitMethods(): void
    {
        $createdBy = 'inventory_manager';
        $updatedBy = 'transfer_worker';

        $this->task->setCreatedBy($createdBy);
        $this->assertSame($createdBy, $this->task->getCreatedBy());

        $this->task->setUpdatedBy($updatedBy);
        $this->assertSame($updatedBy, $this->task->getUpdatedBy());
    }

    public function testCanInstantiateTransferTask(): void
    {
        $transferTask = new TransferTask();
        $this->assertInstanceOf(TransferTask::class, $transferTask);
    }

    public function testSetterMethods(): void
    {
        $this->task->setType(TaskType::TRANSFER);
        $this->task->setStatus(TaskStatus::IN_PROGRESS);
        $this->task->setPriority(6);
        $this->task->setData(['transfer_order' => 'TO001']);
        $this->task->setAssignedWorker(202);
        $this->task->setNotes('转移任务进行中');

        $this->assertSame(TaskType::TRANSFER, $this->task->getType());
        $this->assertSame(TaskStatus::IN_PROGRESS, $this->task->getStatus());
        $this->assertSame(6, $this->task->getPriority());
        $this->assertSame(['transfer_order' => 'TO001'], $this->task->getData());
        $this->assertSame(202, $this->task->getAssignedWorker());
        $this->assertSame('转移任务进行中', $this->task->getNotes());
    }

    public function testTransferWorkflowStates(): void
    {
        // 测试转移任务状态流转
        $this->assertSame(TaskStatus::PENDING, $this->task->getStatus());

        $this->task->setStatus(TaskStatus::ASSIGNED);
        $this->assertSame(TaskStatus::ASSIGNED, $this->task->getStatus());

        $this->task->setStatus(TaskStatus::IN_PROGRESS);
        $this->assertSame(TaskStatus::IN_PROGRESS, $this->task->getStatus());

        // 转移任务可能需要暂停
        $this->task->setStatus(TaskStatus::PAUSED);
        $this->assertSame(TaskStatus::PAUSED, $this->task->getStatus());

        // 恢复并完成
        $this->task->setStatus(TaskStatus::IN_PROGRESS);
        $this->task->setStatus(TaskStatus::COMPLETED);
        $this->assertSame(TaskStatus::COMPLETED, $this->task->getStatus());
    }

    public function testTransferTaskCancellation(): void
    {
        // 测试转移任务取消场景
        $this->task->setStatus(TaskStatus::ASSIGNED);
        $this->task->setStatus(TaskStatus::CANCELLED);

        $this->assertSame(TaskStatus::CANCELLED, $this->task->getStatus());
    }

    public function testCrossWarehouseTransferScenario(): void
    {
        // 测试跨仓库转移场景的数据结构
        $crossWarehouseData = [
            'transfer_type' => 'cross_warehouse',
            'from_location' => [
                'warehouse_id' => 'WH001',
                'location_id' => 'LOC_A01',
            ],
            'to_location' => [
                'warehouse_id' => 'WH003',
                'location_id' => 'LOC_C01',
            ],
            'transport_required' => true,
            'estimated_transport_time' => '2 hours',
        ];

        $this->task->setData($crossWarehouseData);
        $this->assertSame($crossWarehouseData, $this->task->getData());
        $this->assertSame('cross_warehouse', $this->task->getData()['transfer_type']);
        $this->assertTrue($this->task->getData()['transport_required']);
    }
}
