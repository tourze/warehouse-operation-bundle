<?php

namespace Tourze\WarehouseOperationBundle\Tests\Entity;

use Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;
use Tourze\WarehouseOperationBundle\Entity\WarehouseTask;
use Tourze\WarehouseOperationBundle\Enum\TaskStatus;
use Tourze\WarehouseOperationBundle\Enum\TaskType;

/**
 * @internal
 */
#[CoversClass(WarehouseTask::class)]
class WarehouseTaskTest extends AbstractEntityTestCase
{
    protected function createEntity(): object
    {
        return $this->createMockTask();
    }

    public function testWarehouseTaskIsAbstractClass(): void
    {
        $reflection = new \ReflectionClass(WarehouseTask::class);
        $this->assertTrue($reflection->isAbstract(), 'WarehouseTask should be abstract');

        // 验证抽象类有预期的抽象特征但仍然可以被继承
        $this->assertTrue(false === $reflection->isInstantiable());

        // 验证可以通过具体子类实例化
        $mockTask = $this->createMockTask();
        $this->assertInstanceOf(WarehouseTask::class, $mockTask);
    }

    public function testGetIdInitiallyNull(): void
    {
        $task = $this->createMockTask();
        $this->assertNull($task->getId());
    }

    public function testTypeGetterAndSetter(): void
    {
        $task = $this->createMockTask();

        $task->setType(TaskType::INBOUND);
        $this->assertSame(TaskType::INBOUND, $task->getType());

        $task->setType(TaskType::OUTBOUND);
        $this->assertSame(TaskType::OUTBOUND, $task->getType());
    }

    public function testStatusDefaultsToPending(): void
    {
        $task = $this->createMockTask();
        $this->assertSame(TaskStatus::PENDING, $task->getStatus());
    }

    public function testStatusGetterAndSetter(): void
    {
        $task = $this->createMockTask();

        $task->setStatus(TaskStatus::ASSIGNED);
        $this->assertSame(TaskStatus::ASSIGNED, $task->getStatus());

        $task->setStatus(TaskStatus::COMPLETED);
        $this->assertSame(TaskStatus::COMPLETED, $task->getStatus());
    }

    public function testPriorityDefaultsToOne(): void
    {
        $task = $this->createMockTask();
        $this->assertSame(1, $task->getPriority());
    }

    public function testPriorityGetterAndSetter(): void
    {
        $task = $this->createMockTask();

        $task->setPriority(5);
        $this->assertSame(5, $task->getPriority());

        $task->setPriority(10);
        $this->assertSame(10, $task->getPriority());
    }

    public function testDataGetterAndSetter(): void
    {
        $task = $this->createMockTask();

        $data = ['sku' => 'PROD001', 'qty' => 100];
        $task->setData($data);
        $this->assertSame($data, $task->getData());

        // 测试空数组默认值
        $task->setData([]);
        $this->assertSame([], $task->getData());
    }

    public function testAssignedWorkerGetterAndSetter(): void
    {
        $task = $this->createMockTask();

        $this->assertNull($task->getAssignedWorker());

        $task->setAssignedWorker(123);
        $this->assertSame(123, $task->getAssignedWorker());

        $task->setAssignedWorker(null);
        $this->assertNull($task->getAssignedWorker());
    }

    public function testAssignedAtGetterAndSetter(): void
    {
        $task = $this->createMockTask();

        $this->assertNull($task->getAssignedAt());

        $assignedAt = new \DateTimeImmutable();
        $task->setAssignedAt($assignedAt);
        $this->assertSame($assignedAt, $task->getAssignedAt());

        $task->setAssignedAt(null);
        $this->assertNull($task->getAssignedAt());
    }

    public function testStartedAtGetterAndSetter(): void
    {
        $task = $this->createMockTask();

        $this->assertNull($task->getStartedAt());

        $startedAt = new \DateTimeImmutable();
        $task->setStartedAt($startedAt);
        $this->assertSame($startedAt, $task->getStartedAt());
    }

    public function testCompletedAtGetterAndSetter(): void
    {
        $task = $this->createMockTask();

        $this->assertNull($task->getCompletedAt());

        $completedAt = new \DateTimeImmutable();
        $task->setCompletedAt($completedAt);
        $this->assertSame($completedAt, $task->getCompletedAt());
    }

    public function testNotesGetterAndSetter(): void
    {
        $task = $this->createMockTask();

        $this->assertNull($task->getNotes());

        $notes = 'Test notes for the task';
        $task->setNotes($notes);
        $this->assertSame($notes, $task->getNotes());

        $task->setNotes(null);
        $this->assertNull($task->getNotes());
    }

    public function testToStringMethod(): void
    {
        $task = $this->createMockTask();

        // 使用setId方法设置ID
        $task->setId(123);

        $task->setType(TaskType::INBOUND);

        $this->assertSame('Task #123 (inbound)', $task->__toString());
    }

    public function testTimestampableAwareTraitMethods(): void
    {
        $task = $this->createMockTask();

        // 测试 TimestampableAware trait 方法的实际功能
        $now = new \DateTimeImmutable();

        $task->setCreateTime($now);
        $this->assertSame($now, $task->getCreateTime());

        $task->setUpdateTime($now);
        $this->assertSame($now, $task->getUpdateTime());
    }

    public function testBlameableAwareTraitMethods(): void
    {
        $task = $this->createMockTask();

        // 测试 BlameableAware trait 方法的实际功能
        $userId = 'user123';

        $task->setCreatedBy($userId);
        $this->assertSame($userId, $task->getCreatedBy());

        $task->setUpdatedBy($userId);
        $this->assertSame($userId, $task->getUpdatedBy());
    }

    /**
     * 提供属性及其样本值的 Data Provider
     *
     * @return iterable<array{string, mixed}>
     */
    /**
     * @return \Generator<string, array{string, mixed}>
     */
    public static function propertiesProvider(): \Generator
    {
        yield 'type' => ['type', TaskType::INBOUND];
        yield 'status' => ['status', TaskStatus::ASSIGNED];
        yield 'priority' => ['priority', 5];
        yield 'data' => ['data', ['test' => 'value']];
        yield 'assignedWorker' => ['assignedWorker', 123];
        yield 'assignedAt' => ['assignedAt', new \DateTimeImmutable('2023-01-01 10:00:00')];
        yield 'startedAt' => ['startedAt', new \DateTimeImmutable('2023-01-01 10:30:00')];
        yield 'completedAt' => ['completedAt', new \DateTimeImmutable('2023-01-01 11:00:00')];
        yield 'notes' => ['notes', 'Test notes'];
        yield 'createTime' => ['createTime', new \DateTimeImmutable('2023-01-01 10:00:00')];
        yield 'updateTime' => ['updateTime', new \DateTimeImmutable('2023-01-01 10:30:00')];
        yield 'createdBy' => ['createdBy', 'user123'];
        yield 'updatedBy' => ['updatedBy', 'user456'];
    }

    private function createMockTask(): WarehouseTask
    {
        // 创建抽象类的匿名子类用于测试
        return new class () extends WarehouseTask {
            // 匿名类实现，用于测试抽象基类
        };
    }
}
