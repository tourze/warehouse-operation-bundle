<?php

namespace Tourze\WarehouseOperationBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;
use Tourze\WarehouseOperationBundle\Exception\QualityCheckFailedException;
use Tourze\WarehouseOperationBundle\Exception\WarehouseOperationException;

/**
 * @internal
 */
#[CoversClass(QualityCheckFailedException::class)]
class QualityCheckFailedExceptionTest extends AbstractExceptionTestCase
{
    public function testInheritance(): void
    {
        $exception = new QualityCheckFailedException('Quality check error');

        $this->assertInstanceOf(WarehouseOperationException::class, $exception);
    }

    public function testForItem(): void
    {
        $failureReasons = ['Weight mismatch', 'Damaged packaging'];
        $exception = QualityCheckFailedException::forItem('PROD002', $failureReasons);

        $this->assertEquals(
            "Quality check failed for item 'PROD002': Weight mismatch, Damaged packaging",
            $exception->getMessage()
        );

        $expectedContext = [
            'item_code' => 'PROD002',
            'failure_reasons' => $failureReasons,
        ];

        $this->assertEquals($expectedContext, $exception->getContext());
    }
}
