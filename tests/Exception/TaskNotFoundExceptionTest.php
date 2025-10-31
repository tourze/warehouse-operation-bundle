<?php

namespace Tourze\WarehouseOperationBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;
use Tourze\WarehouseOperationBundle\Exception\TaskNotFoundException;
use Tourze\WarehouseOperationBundle\Exception\WarehouseOperationException;

/**
 * @internal
 */
#[CoversClass(TaskNotFoundException::class)]
class TaskNotFoundExceptionTest extends AbstractExceptionTestCase
{
    public function testInheritance(): void
    {
        $exception = new TaskNotFoundException('Task not found');

        $this->assertInstanceOf(WarehouseOperationException::class, $exception);
        $this->assertInstanceOf(\Exception::class, $exception);
    }

    public function testMessage(): void
    {
        $exception = new TaskNotFoundException('Task 123 not found');

        $this->assertEquals('Task 123 not found', $exception->getMessage());
    }

    public function testWithTaskId(): void
    {
        $exception = TaskNotFoundException::forTaskId(123);

        $this->assertEquals('Task with ID 123 not found', $exception->getMessage());
        $this->assertEquals(['task_id' => 123], $exception->getContext());
    }
}
