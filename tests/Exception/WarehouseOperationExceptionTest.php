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
class WarehouseOperationExceptionTest extends AbstractExceptionTestCase
{
    public function testIsException(): void
    {
        $exception = new TaskNotFoundException('Test message');

        $this->assertInstanceOf(\Exception::class, $exception);
        $this->assertInstanceOf(WarehouseOperationException::class, $exception);
        $this->assertEquals('Test message', $exception->getMessage());
    }

    public function testWithContext(): void
    {
        $context = ['task_id' => 123, 'user' => 'test'];
        $exception = new TaskNotFoundException('Test message', 0, null, $context);

        $this->assertEquals($context, $exception->getContext());
    }

    public function testWithoutContext(): void
    {
        $exception = new TaskNotFoundException('Test message');

        $this->assertEquals([], $exception->getContext());
    }
}
