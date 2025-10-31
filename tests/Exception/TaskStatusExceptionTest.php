<?php

namespace Tourze\WarehouseOperationBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;
use Tourze\WarehouseOperationBundle\Exception\TaskStatusException;
use Tourze\WarehouseOperationBundle\Exception\WarehouseOperationException;

/**
 * @internal
 */
#[CoversClass(TaskStatusException::class)]
class TaskStatusExceptionTest extends AbstractExceptionTestCase
{
    public function testInheritance(): void
    {
        $exception = new TaskStatusException('Status error');

        $this->assertInstanceOf(WarehouseOperationException::class, $exception);
    }

    public function testCannotPerformAction(): void
    {
        $exception = TaskStatusException::cannotPerformAction(123, 'completed', 'assign');

        $this->assertEquals(
            "Cannot perform action 'assign' on task 123 with status 'completed'",
            $exception->getMessage()
        );

        $expectedContext = [
            'task_id' => 123,
            'current_status' => 'completed',
            'action' => 'assign',
        ];

        $this->assertEquals($expectedContext, $exception->getContext());
    }
}
