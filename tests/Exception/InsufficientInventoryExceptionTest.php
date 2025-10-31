<?php

namespace Tourze\WarehouseOperationBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;
use Tourze\WarehouseOperationBundle\Exception\InsufficientInventoryException;
use Tourze\WarehouseOperationBundle\Exception\WarehouseOperationException;

/**
 * @internal
 */
#[CoversClass(InsufficientInventoryException::class)]
class InsufficientInventoryExceptionTest extends AbstractExceptionTestCase
{
    public function testInheritance(): void
    {
        $exception = new InsufficientInventoryException('Inventory error');

        $this->assertInstanceOf(WarehouseOperationException::class, $exception);
    }

    public function testForItem(): void
    {
        $exception = InsufficientInventoryException::forItem('PROD001', 100, 50);

        $this->assertEquals(
            "Insufficient inventory for item 'PROD001': requested 100, available 50",
            $exception->getMessage()
        );

        $expectedContext = [
            'item_code' => 'PROD001',
            'requested' => 100,
            'available' => 50,
        ];

        $this->assertEquals($expectedContext, $exception->getContext());
    }
}
