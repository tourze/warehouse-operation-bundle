<?php

namespace Tourze\WarehouseOperationBundle\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\BundleDependency\BundleDependencyInterface;
use Tourze\PHPUnitSymfonyKernelTest\AbstractBundleTestCase;
use Tourze\StockManageBundle\StockManageBundle;
use Tourze\WarehouseOperationBundle\WarehouseOperationBundle;

/**
 * @internal
 */
#[CoversClass(WarehouseOperationBundle::class)]
#[RunTestsInSeparateProcesses]
class WarehouseOperationBundleTest extends AbstractBundleTestCase
{
    protected function onSetUp(): void
    {
        // No additional setup needed for static tests
    }

    public function testImplementsBundleDependencyInterface(): void
    {
        $reflectionClass = new \ReflectionClass(WarehouseOperationBundle::class);
        $this->assertTrue($reflectionClass->implementsInterface(BundleDependencyInterface::class));
    }

    public function testDeclaresStockManageBundleDependency(): void
    {
        $dependencies = WarehouseOperationBundle::getBundleDependencies();

        $this->assertArrayHasKey(StockManageBundle::class, $dependencies);
        $this->assertEquals(['all' => true], $dependencies[StockManageBundle::class]);
    }
}
