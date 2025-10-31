<?php

namespace Tourze\WarehouseOperationBundle\Tests\Service\Extension;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\WarehouseOperationBundle\Service\Extension\QualityRuleInterface;

/**
 * @internal
 */
#[CoversClass(QualityRuleInterface::class)]
class QualityRuleInterfaceTest extends TestCase
{
    public function testInterfaceExists(): void
    {
        $this->assertTrue(interface_exists(QualityRuleInterface::class));
    }

    public function testHasGetNameMethod(): void
    {
        $reflection = new \ReflectionClass(QualityRuleInterface::class);
        $this->assertTrue($reflection->hasMethod('getName'));

        $method = $reflection->getMethod('getName');
        $this->assertEquals('getName', $method->getName());
        $this->assertTrue($method->isPublic());
        $this->assertCount(0, $method->getParameters());
    }

    public function testHasCheckMethod(): void
    {
        $reflection = new \ReflectionClass(QualityRuleInterface::class);
        $this->assertTrue($reflection->hasMethod('check'));

        $method = $reflection->getMethod('check');
        $this->assertEquals('check', $method->getName());
        $this->assertTrue($method->isPublic());
        $this->assertCount(3, $method->getParameters());
    }

    public function testHasSupportsMethod(): void
    {
        $reflection = new \ReflectionClass(QualityRuleInterface::class);
        $this->assertTrue($reflection->hasMethod('supports'));

        $method = $reflection->getMethod('supports');
        $this->assertEquals('supports', $method->getName());
        $this->assertTrue($method->isPublic());
        $this->assertCount(1, $method->getParameters());
    }

    public function testHasGetPriorityMethod(): void
    {
        $reflection = new \ReflectionClass(QualityRuleInterface::class);
        $this->assertTrue($reflection->hasMethod('getPriority'));

        $method = $reflection->getMethod('getPriority');
        $this->assertEquals('getPriority', $method->getName());
        $this->assertTrue($method->isPublic());
        $this->assertCount(0, $method->getParameters());
    }
}
