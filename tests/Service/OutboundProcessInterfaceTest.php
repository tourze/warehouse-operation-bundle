<?php

namespace Tourze\WarehouseOperationBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\WarehouseOperationBundle\Service\OutboundProcessInterface;

/**
 * @internal
 */
#[CoversClass(OutboundProcessInterface::class)]
class OutboundProcessInterfaceTest extends TestCase
{
    public function testInterfaceExists(): void
    {
        $this->assertTrue(interface_exists(OutboundProcessInterface::class));
    }

    public function testHasStartOutboundMethod(): void
    {
        $reflection = new \ReflectionClass(OutboundProcessInterface::class);
        $this->assertTrue($reflection->hasMethod('startOutbound'));

        $method = $reflection->getMethod('startOutbound');
        $this->assertEquals('startOutbound', $method->getName());
        $this->assertTrue($method->isPublic());
        $this->assertCount(2, $method->getParameters());
    }

    public function testHasExecutePickingMethod(): void
    {
        $reflection = new \ReflectionClass(OutboundProcessInterface::class);
        $this->assertTrue($reflection->hasMethod('executePicking'));

        $method = $reflection->getMethod('executePicking');
        $this->assertEquals('executePicking', $method->getName());
        $this->assertTrue($method->isPublic());
        $this->assertCount(1, $method->getParameters());
    }

    public function testHasExecutePackingMethod(): void
    {
        $reflection = new \ReflectionClass(OutboundProcessInterface::class);
        $this->assertTrue($reflection->hasMethod('executePacking'));

        $method = $reflection->getMethod('executePacking');
        $this->assertEquals('executePacking', $method->getName());
        $this->assertTrue($method->isPublic());
        $this->assertCount(2, $method->getParameters());
    }

    public function testHasExecuteShippingMethod(): void
    {
        $reflection = new \ReflectionClass(OutboundProcessInterface::class);
        $this->assertTrue($reflection->hasMethod('executeShipping'));

        $method = $reflection->getMethod('executeShipping');
        $this->assertEquals('executeShipping', $method->getName());
        $this->assertTrue($method->isPublic());
        $this->assertCount(2, $method->getParameters());
    }
}
