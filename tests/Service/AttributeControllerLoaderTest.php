<?php

declare(strict_types=1);

namespace Tourze\WarehouseOperationBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\WarehouseOperationBundle\Controller\Admin\CountTaskCrudController;
use Tourze\WarehouseOperationBundle\Controller\Admin\InboundTaskCrudController;
use Tourze\WarehouseOperationBundle\Controller\Admin\LocationCrudController;
use Tourze\WarehouseOperationBundle\Controller\Admin\OutboundTaskCrudController;
use Tourze\WarehouseOperationBundle\Controller\Admin\QualityStandardCrudController;
use Tourze\WarehouseOperationBundle\Controller\Admin\QualityTaskCrudController;
use Tourze\WarehouseOperationBundle\Controller\Admin\ShelfCrudController;
use Tourze\WarehouseOperationBundle\Controller\Admin\TaskRuleCrudController;
use Tourze\WarehouseOperationBundle\Controller\Admin\TransferTaskCrudController;
use Tourze\WarehouseOperationBundle\Controller\Admin\WarehouseCrudController;
use Tourze\WarehouseOperationBundle\Controller\Admin\WorkerSkillCrudController;
use Tourze\WarehouseOperationBundle\Controller\Admin\ZoneCrudController;
use Tourze\WarehouseOperationBundle\Service\AttributeControllerLoader;

/**
 * @internal
 */
#[CoversClass(AttributeControllerLoader::class)]
#[RunTestsInSeparateProcesses]
final class AttributeControllerLoaderTest extends AbstractIntegrationTestCase
{
    private AttributeControllerLoader $loader;

    protected function onSetUp(): void
    {
        $this->loader = self::getService(AttributeControllerLoader::class);
    }

    public function testGetControllers(): void
    {
        $controllers = $this->loader->getControllers();

        self::assertIsArray($controllers);
        self::assertNotEmpty($controllers);

        // 验证包含所有预期的控制器类
        $expectedControllers = [
            WarehouseCrudController::class,
            ZoneCrudController::class,
            ShelfCrudController::class,
            LocationCrudController::class,
            InboundTaskCrudController::class,
            OutboundTaskCrudController::class,
            TransferTaskCrudController::class,
            QualityStandardCrudController::class,
            QualityTaskCrudController::class,
            TaskRuleCrudController::class,
            CountTaskCrudController::class,
            WorkerSkillCrudController::class,
        ];

        self::assertCount(12, $controllers);

        foreach ($expectedControllers as $expectedController) {
            self::assertContainsEquals(
                $expectedController,
                $controllers,
                "Expected controller {$expectedController} not found in returned controllers"
            );
        }
    }

    public function testGetControllersReturnsConsistentResults(): void
    {
        $controllers1 = $this->loader->getControllers();
        $controllers2 = $this->loader->getControllers();

        self::assertSame($controllers1, $controllers2);
    }

    public function testGetControllersReturnsValidClassNames(): void
    {
        $controllers = $this->loader->getControllers();

        foreach ($controllers as $controller) {
            self::assertIsString($controller);
            self::assertTrue(
                class_exists($controller),
                "Controller class {$controller} does not exist"
            );
        }
    }

    public function testAutoloadHandlesRouteCollection(): void
    {
        // autoload方法应该能够处理RouteCollection而不抛出异常
        // 此测试的目的是验证方法可以被调用而不抛出异常
        $this->expectNotToPerformAssertions();
        $this->loader->autoload();
    }

    public function testSupportsMethodExists(): void
    {
        // 验证 supports 方法存在
        $reflection = new \ReflectionClass($this->loader);
        self::assertTrue($reflection->hasMethod('supports'), 'AttributeControllerLoader应该有supports方法');
    }

    public function testLoaderIsImmutable(): void
    {
        $reflection = new \ReflectionClass($this->loader);

        // 验证类不包含可修改的公共属性
        $publicProperties = $reflection->getProperties(\ReflectionProperty::IS_PUBLIC);
        self::assertEmpty($publicProperties, 'AttributeControllerLoader不应有公共属性，确保不可变性');

        // 验证类本身没有定义setter方法（排除从父类继承的方法）
        $methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);
        $ownMethods = array_filter($methods, function (\ReflectionMethod $method) use ($reflection): bool {
            return $method->getDeclaringClass()->getName() === $reflection->getName();
        });

        foreach ($ownMethods as $method) {
            $methodName = $method->getName();
            self::assertStringStartsNotWith('set', $methodName, "不应有setter方法: {$methodName}");
        }
    }

    public function testLoaderIsAutoconfigured(): void
    {
        $reflection = new \ReflectionClass($this->loader);
        $attributes = $reflection->getAttributes();

        $hasAutoconfigure = false;
        foreach ($attributes as $attribute) {
            if ('Symfony\Component\DependencyInjection\Attribute\Autoconfigure' === $attribute->getName()) {
                $hasAutoconfigure = true;
                $arguments = $attribute->getArguments();
                self::assertTrue($arguments['public'] ?? false);
                break;
            }
        }

        self::assertTrue($hasAutoconfigure, 'AttributeControllerLoader should have Autoconfigure attribute');
    }
}
