<?php

namespace Tourze\WarehouseOperationBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\WarehouseOperationBundle\Service\LocationManagerInterface;

/**
 * @internal
 */
#[CoversClass(LocationManagerInterface::class)]
class LocationManagerInterfaceTest extends TestCase
{
    public function testInterfaceExists(): void
    {
        $this->assertTrue(interface_exists(LocationManagerInterface::class));
    }

    public function testHasFindAvailableLocationsMethod(): void
    {
        $reflection = new \ReflectionClass(LocationManagerInterface::class);
        $this->assertTrue($reflection->hasMethod('findAvailableLocations'));

        $method = $reflection->getMethod('findAvailableLocations');
        $this->assertEquals('findAvailableLocations', $method->getName());
        $this->assertTrue($method->isPublic());
        $this->assertCount(2, $method->getParameters());
    }

    public function testHasOccupyLocationMethod(): void
    {
        $reflection = new \ReflectionClass(LocationManagerInterface::class);
        $this->assertTrue($reflection->hasMethod('occupyLocation'));

        $method = $reflection->getMethod('occupyLocation');
        $this->assertEquals('occupyLocation', $method->getName());
        $this->assertTrue($method->isPublic());
        $this->assertCount(3, $method->getParameters());
    }

    public function testHasReleaseLocationMethod(): void
    {
        $reflection = new \ReflectionClass(LocationManagerInterface::class);
        $this->assertTrue($reflection->hasMethod('releaseLocation'));

        $method = $reflection->getMethod('releaseLocation');
        $this->assertEquals('releaseLocation', $method->getName());
        $this->assertTrue($method->isPublic());
        $this->assertCount(3, $method->getParameters());
    }

    public function testHasGetLocationStatusMethod(): void
    {
        $reflection = new \ReflectionClass(LocationManagerInterface::class);
        $this->assertTrue($reflection->hasMethod('getLocationStatus'));

        $method = $reflection->getMethod('getLocationStatus');
        $this->assertEquals('getLocationStatus', $method->getName());
        $this->assertTrue($method->isPublic());
        $this->assertCount(1, $method->getParameters());
    }
}
