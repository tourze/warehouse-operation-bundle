<?php

namespace Tourze\WarehouseOperationBundle\Tests\Service\Extension;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\WarehouseOperationBundle\Service\Extension\AllocationStrategyInterface;

/**
 * 测试分配策略接口的完整性和功能
 * @internal
 */
#[CoversClass(AllocationStrategyInterface::class)]
#[RunTestsInSeparateProcesses]
class AllocationStrategyInterfaceTest extends AbstractIntegrationTestCase
{
    protected function onSetUp(): void
    {
        // 无需额外的设置
    }

    /**
     * 测试分配策略接口方法的完整性
     */
    public function testAllocationStrategyInterfaceMethodsExistence(): void
    {
        $reflection = new \ReflectionClass(AllocationStrategyInterface::class);

        $this->assertTrue($reflection->isInterface(), 'AllocationStrategyInterface 应该是一个接口');

        // 验证分配策略的关键方法存在
        $expectedMethods = [
            'getName' => 0,
            'allocateLocation' => 3,
            'getPriority' => 0,
        ];

        foreach ($expectedMethods as $methodName => $paramCount) {
            $this->assertTrue(
                $reflection->hasMethod($methodName),
                "分配策略接口应该有 {$methodName} 方法"
            );

            $method = $reflection->getMethod($methodName);
            $this->assertTrue($method->isPublic(), "{$methodName} 方法应该是公共的");
            $this->assertTrue($method->isAbstract(), "{$methodName} 方法应该是抽象的");
            $this->assertCount($paramCount, $method->getParameters(), "{$methodName} 方法应该有 {$paramCount} 个参数");
        }
    }

    /**
     * 测试分配策略接口的业务语义完整性
     */
    public function testAllocationStrategyInterfaceBusinessSemantics(): void
    {
        $reflection = new \ReflectionClass(AllocationStrategyInterface::class);

        // 验证接口命名空间正确
        $this->assertEquals('Tourze\WarehouseOperationBundle\Service\Extension\AllocationStrategyInterface', $reflection->getName());

        // 验证分配策略的完整性：应包含名称、分配方法、优先级
        $methods = $reflection->getMethods();
        $methodNames = array_map(fn ($method) => $method->getName(), $methods);

        $this->assertContainsEquals('getName', $methodNames, '分配策略应包含获取名称的方法');
        $this->assertContainsEquals('allocateLocation', $methodNames, '分配策略应包含分配库位的方法');
        $this->assertContainsEquals('getPriority', $methodNames, '分配策略应包含获取优先级的方法');

        // 验证方法数量合理（避免接口过于复杂）
        $this->assertGreaterThan(2, count($methods), '分配策略接口应包含基本的策略方法');
        $this->assertLessThan(8, count($methods), '分配策略接口不应过于复杂');
    }
}
