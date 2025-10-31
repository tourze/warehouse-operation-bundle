<?php

namespace Tourze\WarehouseOperationBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\WarehouseOperationBundle\Service\TaskManagerInterface;

/**
 * 测试任务管理器接口的完整性和功能
 * @internal
 */
#[CoversClass(TaskManagerInterface::class)]
#[RunTestsInSeparateProcesses]
class TaskManagerInterfaceTest extends AbstractIntegrationTestCase
{
    protected function onSetUp(): void
    {
        // Integration test setup - no special setup required for interface tests
    }

    /**
     * 测试接口方法的完整性和可调用性
     */
    public function testInterfaceMethodsExistence(): void
    {
        // 检查接口及其关键方法的存在性
        $reflection = new \ReflectionClass(TaskManagerInterface::class);

        $this->assertTrue($reflection->isInterface(), 'TaskManagerInterface 应该是一个接口');

        // 验证所有关键方法存在并具有正确的签名
        $expectedMethods = [
            'createTask' => 2,
            'assignTask' => 2,
            'completeTask' => 2,
            'pauseTask' => 2,
            'resumeTask' => 1,
            'cancelTask' => 2,
            'findTasksByStatus' => 2,
            'getTaskTrace' => 1,
        ];

        foreach ($expectedMethods as $methodName => $paramCount) {
            $this->assertTrue(
                $reflection->hasMethod($methodName),
                "接口应该有 {$methodName} 方法"
            );

            $method = $reflection->getMethod($methodName);
            $this->assertTrue($method->isPublic(), "{$methodName} 方法应该是公共的");
            $this->assertCount($paramCount, $method->getParameters(), "{$methodName} 方法应该有 {$paramCount} 个参数");
        }
    }

    /**
     * 测试接口的合约定义的完整性
     */
    public function testInterfaceContractDefinition(): void
    {
        $reflection = new \ReflectionClass(TaskManagerInterface::class);

        // 验证接口在正确的命名空间中
        $this->assertEquals('Tourze\WarehouseOperationBundle\Service\TaskManagerInterface', $reflection->getName());

        // 验证接口有适当的方法数量（避免接口过于庞大）
        $methods = $reflection->getMethods();
        $this->assertGreaterThan(5, count($methods), '接口应该至少有6个方法');
        $this->assertLessThan(15, count($methods), '接口不应该有太多方法，建议拆分');

        // 验证所有方法都是抽象的
        foreach ($methods as $method) {
            $this->assertTrue($method->isAbstract(), "接口中的 {$method->getName()} 方法应该是抽象的");
        }
    }
}
