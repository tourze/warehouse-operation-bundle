<?php

namespace Tourze\WarehouseOperationBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\WarehouseOperationBundle\Service\InboundProcessInterface;

/**
 * 测试入库流程接口的完整性和功能
 * @internal
 */
#[CoversClass(InboundProcessInterface::class)]
#[RunTestsInSeparateProcesses]
class InboundProcessInterfaceTest extends AbstractIntegrationTestCase
{
    protected function onSetUp(): void
    {
        // 无需额外的设置
    }

    /**
     * 测试入库流程接口方法的完整性
     */
    public function testInboundProcessInterfaceMethodsExistence(): void
    {
        $reflection = new \ReflectionClass(InboundProcessInterface::class);

        $this->assertTrue($reflection->isInterface(), 'InboundProcessInterface 应该是一个接口');

        // 验证入库流程的关键方法存在
        $expectedMethods = [
            'startInbound' => 2,
            'executeReceiving' => 2,
            'executeQualityCheck' => 2,
            'executePutaway' => 2,
        ];

        foreach ($expectedMethods as $methodName => $paramCount) {
            $this->assertTrue(
                $reflection->hasMethod($methodName),
                "入库流程接口应该有 {$methodName} 方法"
            );

            $method = $reflection->getMethod($methodName);
            $this->assertTrue($method->isPublic(), "{$methodName} 方法应该是公共的");
            $this->assertTrue($method->isAbstract(), "{$methodName} 方法应该是抽象的");
            $this->assertCount($paramCount, $method->getParameters(), "{$methodName} 方法应该有 {$paramCount} 个参数");
        }
    }

    /**
     * 测试入库流程接口的业务语义完整性
     */
    public function testInboundProcessInterfaceBusinessSemantics(): void
    {
        $reflection = new \ReflectionClass(InboundProcessInterface::class);

        // 验证接口命名空间正确
        $this->assertEquals('Tourze\WarehouseOperationBundle\Service\InboundProcessInterface', $reflection->getName());

        // 验证入库流程的完整性：应包含收货->质检->上架的完整流程
        $methods = $reflection->getMethods();
        $methodNames = array_map(fn ($method) => $method->getName(), $methods);

        $this->assertContainsEquals('executeReceiving', $methodNames, '入库流程应包含收货环节');
        $this->assertContainsEquals('executeQualityCheck', $methodNames, '入库流程应包含质检环节');
        $this->assertContainsEquals('executePutaway', $methodNames, '入库流程应包含上架环节');
        $this->assertContainsEquals('startInbound', $methodNames, '入库流程应包含启动环节');

        // 验证方法数量合理（避免接口过于复杂）
        $this->assertGreaterThan(3, count($methods), '入库流程接口应包含基本的流程方法');
        $this->assertLessThan(10, count($methods), '入库流程接口不应过于复杂');
    }
}
