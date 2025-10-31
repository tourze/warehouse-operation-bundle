<?php

namespace Tourze\WarehouseOperationBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;
use Tourze\WarehouseOperationBundle\Entity\CountTask;
use Tourze\WarehouseOperationBundle\Entity\WarehouseTask;
use Tourze\WarehouseOperationBundle\Enum\TaskStatus;
use Tourze\WarehouseOperationBundle\Enum\TaskType;

/**
 * @internal
 */
#[CoversClass(CountTask::class)]
final class CountTaskTest extends AbstractEntityTestCase
{
    protected function createEntity(): object
    {
        return new CountTask();
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

    private CountTask $task;

    protected function setUp(): void
    {
        parent::setUp();
        $this->task = new CountTask();
    }

    public function testGetIdInitiallyNull(): void
    {
        $this->assertNull($this->task->getId());
    }

    public function testTypeGetterAndSetter(): void
    {
        $this->task->setType(TaskType::COUNT);
        $this->assertSame(TaskType::COUNT, $this->task->getType());

        $this->task->setType(TaskType::INBOUND);
        $this->assertSame(TaskType::INBOUND, $this->task->getType());
    }

    public function testStatusDefaultsToPending(): void
    {
        $this->assertSame(TaskStatus::PENDING, $this->task->getStatus());
    }

    public function testStatusGetterAndSetter(): void
    {
        $this->task->setStatus(TaskStatus::ASSIGNED);
        $this->assertSame(TaskStatus::ASSIGNED, $this->task->getStatus());

        $this->task->setStatus(TaskStatus::COMPLETED);
        $this->assertSame(TaskStatus::COMPLETED, $this->task->getStatus());
    }

    public function testPriorityDefaultsToOne(): void
    {
        $this->assertSame(1, $this->task->getPriority());
    }

    public function testPriorityGetterAndSetter(): void
    {
        $this->task->setPriority(5);
        $this->assertSame(5, $this->task->getPriority());

        $this->task->setPriority(10);
        $this->assertSame(10, $this->task->getPriority());
    }

    public function testDataDefaultsToEmptyArray(): void
    {
        $this->assertEquals([], $this->task->getData());
    }

    public function testDataGetterAndSetter(): void
    {
        $data = [
            'location_id' => 'LOC001',
            'expected_qty' => 100,
            'actual_qty' => null,
            'discrepancy' => 0,
        ];
        $this->task->setData($data);
        $this->assertEquals($data, $this->task->getData());

        // 测试空数组
        $this->task->setData([]);
        $this->assertEquals([], $this->task->getData());
    }

    public function testAssignedWorkerInitiallyNull(): void
    {
        $this->assertNull($this->task->getAssignedWorker());
    }

    public function testAssignedWorkerGetterAndSetter(): void
    {
        $this->task->setAssignedWorker(123);
        $this->assertSame(123, $this->task->getAssignedWorker());

        $this->task->setAssignedWorker(null);
        $this->assertNull($this->task->getAssignedWorker());
    }

    public function testAssignedAtInitiallyNull(): void
    {
        $this->assertNull($this->task->getAssignedAt());
    }

    public function testAssignedAtGetterAndSetter(): void
    {
        $assignedAt = new \DateTimeImmutable();
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
        $startedAt = new \DateTimeImmutable();
        $this->task->setStartedAt($startedAt);
        $this->assertSame($startedAt, $this->task->getStartedAt());
    }

    public function testCompletedAtInitiallyNull(): void
    {
        $this->assertNull($this->task->getCompletedAt());
    }

    public function testCompletedAtGetterAndSetter(): void
    {
        $completedAt = new \DateTimeImmutable();
        $this->task->setCompletedAt($completedAt);
        $this->assertSame($completedAt, $this->task->getCompletedAt());
    }

    public function testNotesInitiallyNull(): void
    {
        $this->assertNull($this->task->getNotes());
    }

    public function testNotesGetterAndSetter(): void
    {
        $notes = '盘点发现差异，需要进一步核查';
        $this->task->setNotes($notes);
        $this->assertSame($notes, $this->task->getNotes());

        $this->task->setNotes(null);
        $this->assertNull($this->task->getNotes());
    }

    public function testToStringMethod(): void
    {
        // 使用setId方法设置ID
        $this->task->setId(123);

        $this->task->setType(TaskType::COUNT);

        $this->assertSame('Task #123 (count)', $this->task->__toString());
    }

    public function testTimestampableAwareTraitMethods(): void
    {
        $now = new \DateTimeImmutable();

        $this->task->setCreateTime($now);
        $this->assertSame($now, $this->task->getCreateTime());

        $this->task->setUpdateTime($now);
        $this->assertSame($now, $this->task->getUpdateTime());
    }

    public function testBlameableAwareTraitMethods(): void
    {
        $userId = 'admin';

        $this->task->setCreatedBy($userId);
        $this->assertSame($userId, $this->task->getCreatedBy());

        $this->task->setUpdatedBy($userId);
        $this->assertSame($userId, $this->task->getUpdatedBy());
    }

    public function testCanInstantiateCountTask(): void
    {
        $countTask = new CountTask();
        $this->assertInstanceOf(CountTask::class, $countTask);
    }

    public function testSetterMethods(): void
    {
        $this->task->setType(TaskType::COUNT);
        $this->task->setStatus(TaskStatus::ASSIGNED);
        $this->task->setPriority(5);
        $this->task->setData(['test' => 'data']);
        $this->task->setAssignedWorker(123);
        $this->task->setNotes('Test notes');

        $this->assertSame(TaskType::COUNT, $this->task->getType());
        $this->assertSame(TaskStatus::ASSIGNED, $this->task->getStatus());
        $this->assertSame(5, $this->task->getPriority());
        $this->assertEquals(['test' => 'data'], $this->task->getData());
        $this->assertSame(123, $this->task->getAssignedWorker());
        $this->assertSame('Test notes', $this->task->getNotes());
    }
}
