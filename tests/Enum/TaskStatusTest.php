<?php

namespace Tourze\WarehouseOperationBundle\Tests\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;
use Tourze\WarehouseOperationBundle\Enum\TaskStatus;

/**
 * 测试任务状态枚举
 * @internal
 */
#[CoversClass(TaskStatus::class)]
class TaskStatusTest extends AbstractEnumTestCase
{
    public function testAllTaskStatusesExist(): void
    {
        $expectedStatuses = [
            TaskStatus::PENDING,
            TaskStatus::ASSIGNED,
            TaskStatus::IN_PROGRESS,
            TaskStatus::PAUSED,
            TaskStatus::COMPLETED,
            TaskStatus::CANCELLED,
            TaskStatus::FAILED,
        ];

        $this->assertCount(7, $expectedStatuses);

        foreach ($expectedStatuses as $status) {
            $this->assertInstanceOf(TaskStatus::class, $status);
        }
    }

    public function testTaskStatusValues(): void
    {
        $this->assertEquals('pending', TaskStatus::PENDING->value);
        $this->assertEquals('assigned', TaskStatus::ASSIGNED->value);
        $this->assertEquals('in_progress', TaskStatus::IN_PROGRESS->value);
        $this->assertEquals('paused', TaskStatus::PAUSED->value);
        $this->assertEquals('completed', TaskStatus::COMPLETED->value);
        $this->assertEquals('cancelled', TaskStatus::CANCELLED->value);
        $this->assertEquals('failed', TaskStatus::FAILED->value);
    }

    public function testCanAssign(): void
    {
        $this->assertTrue(TaskStatus::PENDING->canAssign());
        $this->assertFalse(TaskStatus::ASSIGNED->canAssign());
        $this->assertFalse(TaskStatus::IN_PROGRESS->canAssign());
        $this->assertFalse(TaskStatus::COMPLETED->canAssign());
    }

    public function testCanStart(): void
    {
        $this->assertFalse(TaskStatus::PENDING->canStart());
        $this->assertTrue(TaskStatus::ASSIGNED->canStart());
        $this->assertFalse(TaskStatus::IN_PROGRESS->canStart());
        $this->assertFalse(TaskStatus::COMPLETED->canStart());
    }

    public function testCanComplete(): void
    {
        $this->assertFalse(TaskStatus::PENDING->canComplete());
        $this->assertFalse(TaskStatus::ASSIGNED->canComplete());
        $this->assertTrue(TaskStatus::IN_PROGRESS->canComplete());
        $this->assertFalse(TaskStatus::COMPLETED->canComplete());
    }

    public function testCanCancel(): void
    {
        $this->assertTrue(TaskStatus::PENDING->canCancel());
        $this->assertTrue(TaskStatus::ASSIGNED->canCancel());
        $this->assertFalse(TaskStatus::IN_PROGRESS->canCancel());
        $this->assertTrue(TaskStatus::PAUSED->canCancel());
        $this->assertFalse(TaskStatus::COMPLETED->canCancel());
    }

    public function testGetLabel(): void
    {
        $this->assertEquals('待分配', TaskStatus::PENDING->getLabel());
        $this->assertEquals('已分配', TaskStatus::ASSIGNED->getLabel());
        $this->assertEquals('进行中', TaskStatus::IN_PROGRESS->getLabel());
        $this->assertEquals('暂停', TaskStatus::PAUSED->getLabel());
        $this->assertEquals('已完成', TaskStatus::COMPLETED->getLabel());
        $this->assertEquals('已取消', TaskStatus::CANCELLED->getLabel());
        $this->assertEquals('失败', TaskStatus::FAILED->getLabel());
    }

    public function testToArray(): void
    {
        // toArray() 是实例方法，测试单个枚举值的 toArray() 输出
        $result = TaskStatus::PENDING->toArray();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('value', $result);
        $this->assertArrayHasKey('label', $result);

        $this->assertEquals('pending', $result['value']);
        $this->assertEquals('待分配', $result['label']);
    }
}
