<?php

namespace Tourze\WarehouseOperationBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;
use Tourze\WarehouseOperationBundle\Exception\LocationNotAvailableException;
use Tourze\WarehouseOperationBundle\Exception\WarehouseOperationException;

/**
 * @internal
 */
#[CoversClass(LocationNotAvailableException::class)]
class LocationNotAvailableExceptionTest extends AbstractExceptionTestCase
{
    public function testInheritance(): void
    {
        $exception = new LocationNotAvailableException('Location error');

        $this->assertInstanceOf(WarehouseOperationException::class, $exception);
    }

    public function testForLocation(): void
    {
        $exception = LocationNotAvailableException::forLocation(456);

        $this->assertEquals('Location is not available', $exception->getMessage());
        $this->assertEquals(['location_id' => 456], $exception->getContext());
    }

    public function testForLocationWithCustomReason(): void
    {
        $exception = LocationNotAvailableException::forLocation(456, 'Location is under maintenance');

        $this->assertEquals('Location is under maintenance', $exception->getMessage());
        $this->assertEquals(['location_id' => 456], $exception->getContext());
    }
}
