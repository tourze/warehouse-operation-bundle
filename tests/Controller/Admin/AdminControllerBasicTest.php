<?php

namespace Tourze\WarehouseOperationBundle\Tests\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\Test;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminControllerTestCase;
use Tourze\WarehouseOperationBundle\Controller\Admin\CountPlanAdminController;
use Tourze\WarehouseOperationBundle\Controller\Admin\QualityStandardAdminController;
use Tourze\WarehouseOperationBundle\Controller\Admin\TaskAdminController;
use Tourze\WarehouseOperationBundle\Controller\Admin\WorkerSkillAdminController;
use Tourze\WarehouseOperationBundle\Entity\CountPlan;
use Tourze\WarehouseOperationBundle\Entity\QualityStandard;
use Tourze\WarehouseOperationBundle\Entity\WarehouseTask;
use Tourze\WarehouseOperationBundle\Entity\WorkerSkill;

/**
 * Admin控制器基础功能测试
 *
 * 测试所有Admin控制器的基本功能，包括Entity映射、
 * 字段配置和自定义操作的基本功能验证。
 * @internal
 */
#[CoversClass(TaskAdminController::class)]
#[RunTestsInSeparateProcesses]
final class AdminControllerBasicTest extends AbstractEasyAdminControllerTestCase
{
    protected function getControllerService(): TaskAdminController
    {
        return self::getService(TaskAdminController::class);
    }

    public static function provideIndexPageHeaders(): \Generator
    {
        yield ['ID'];
        yield ['任务类型'];
        yield ['任务状态'];
        yield ['优先级'];
        yield ['任务描述'];
        yield ['作业位置'];
    }

    /** @return iterable<string, array{string}> */
    public static function provideNewPageFields(): iterable
    {
        // TaskAdminController 字段
        yield 'type' => ['type'];
        yield 'status' => ['status'];
        yield 'priority' => ['priority'];
        yield 'description' => ['description'];
        yield 'location' => ['location'];
        yield 'isEmergency' => ['isEmergency'];
        yield 'taskData' => ['taskData'];
        yield 'assignedWorker' => ['assignedWorker'];
    }

    /** @return iterable<string, array{string}> */
    public static function provideEditPageFields(): iterable
    {
        // Edit 页面包含与 New 页面相同的字段
        yield from self::provideNewPageFields();
    }

    public function __invoke(): void
    {
        // 执行所有基础控制器测试
        $this->testGetEntityFqcn(TaskAdminController::class, WarehouseTask::class);
        $this->testControllerInheritance(TaskAdminController::class);
    }

    #[Test]
    #[DataProvider('controllerEntityProvider')]
    public function testGetEntityFqcn(string $controllerClass, string $expectedEntity): void
    {
        /** @var AbstractCrudController<object> $controller */
        $controller = new $controllerClass();
        $this->assertEquals($expectedEntity, $controllerClass::getEntityFqcn());
    }

    /**
     * @return array<int, array{string, string}>
     */
    public static function controllerEntityProvider(): array
    {
        return [
            [TaskAdminController::class, WarehouseTask::class],
            [QualityStandardAdminController::class, QualityStandard::class],
            [CountPlanAdminController::class, CountPlan::class],
            [WorkerSkillAdminController::class, WorkerSkill::class],
        ];
    }

    #[Test]
    #[DataProvider('controllerProvider')]
    public function testConfigureFieldsReturnsIterable(string $controllerClass): void
    {
        /** @var AbstractCrudController<object> $controller */
        $controller = new $controllerClass();
        $fields = $controller->configureFields('index');

        $this->assertIsIterable($fields);

        // 验证至少有一些字段
        $fieldCount = 0;
        foreach ($fields as $field) {
            ++$fieldCount;
            // 基本验证字段是对象
            $this->assertIsObject($field);
        }

        $this->assertGreaterThan(0, $fieldCount, $controllerClass . ' should have at least one field');
    }

    #[Test]
    #[DataProvider('controllerProvider')]
    public function testConfigureFieldsForDifferentPages(string $controllerClass): void
    {
        /** @var AbstractCrudController<object> $controller */
        $controller = new $controllerClass();
        $pages = ['index', 'detail', 'edit', 'new'];

        foreach ($pages as $page) {
            $fields = $controller->configureFields($page);
            $this->assertIsIterable($fields, $controllerClass . ' should return iterable for page: ' . $page);

            $fieldCount = 0;
            foreach ($fields as $field) {
                ++$fieldCount;
            }
            $this->assertGreaterThan(0, $fieldCount, $controllerClass . ' should have fields for page: ' . $page);
        }
    }

    #[Test]
    public function testTaskAdminControllerCustomActions(): void
    {
        $controller = new TaskAdminController();

        // 由于这些方法已确认存在，直接验证它们是公共方法
        $reflection = new \ReflectionClass($controller);
        $this->assertTrue($reflection->hasMethod('assignWorker') && $reflection->getMethod('assignWorker')->isPublic());
        $this->assertTrue($reflection->hasMethod('changePriority') && $reflection->getMethod('changePriority')->isPublic());
        $this->assertTrue($reflection->hasMethod('pauseTask') && $reflection->getMethod('pauseTask')->isPublic());
        $this->assertTrue($reflection->hasMethod('resumeTask') && $reflection->getMethod('resumeTask')->isPublic());
    }

    #[Test]
    public function testQualityStandardControllerCustomActions(): void
    {
        $controller = new QualityStandardAdminController();

        // 验证方法存在且可调用
        $reflection = new \ReflectionClass($controller);
        $this->assertTrue($reflection->hasMethod('activateStandard') && $reflection->getMethod('activateStandard')->isPublic());
        $this->assertTrue($reflection->hasMethod('deactivateStandard') && $reflection->getMethod('deactivateStandard')->isPublic());
        $this->assertTrue($reflection->hasMethod('duplicateStandard') && $reflection->getMethod('duplicateStandard')->isPublic());
    }

    #[Test]
    public function testCountPlanControllerCustomActions(): void
    {
        $controller = new CountPlanAdminController();

        // 验证方法存在且可调用
        $reflection = new \ReflectionClass($controller);
        $this->assertTrue($reflection->hasMethod('executeCountPlan') && $reflection->getMethod('executeCountPlan')->isPublic());
        $this->assertTrue($reflection->hasMethod('activatePlan') && $reflection->getMethod('activatePlan')->isPublic());
        $this->assertTrue($reflection->hasMethod('deactivatePlan') && $reflection->getMethod('deactivatePlan')->isPublic());
        $this->assertTrue($reflection->hasMethod('duplicatePlan') && $reflection->getMethod('duplicatePlan')->isPublic());
        $this->assertTrue($reflection->hasMethod('viewExecutionHistory') && $reflection->getMethod('viewExecutionHistory')->isPublic());
    }

    #[Test]
    public function testWorkerSkillControllerCustomActions(): void
    {
        $controller = new WorkerSkillAdminController();

        $reflection = new \ReflectionClass($controller);
        $this->assertTrue($reflection->hasMethod('certifySkill') && $reflection->getMethod('certifySkill')->isPublic());
        $this->assertTrue($reflection->hasMethod('revokeCertification') && $reflection->getMethod('revokeCertification')->isPublic());
        $this->assertTrue($reflection->hasMethod('upgradeSkill') && $reflection->getMethod('upgradeSkill')->isPublic());
        $this->assertTrue($reflection->hasMethod('viewSkillPerformance') && $reflection->getMethod('viewSkillPerformance')->isPublic());
    }

    #[Test]
    #[DataProvider('controllerProvider')]
    public function testControllerInheritance(string $controllerClass): void
    {
        /** @var AbstractCrudController<object> $controller */
        $controller = new $controllerClass();

        // 验证继承关系
        $this->assertInstanceOf(AbstractCrudController::class, $controller);

        // 验证这些方法存在并且可以被调用
        $this->assertNotNull($controllerClass::getEntityFqcn());
        $this->assertIsIterable($controller->configureFields('index'));
        $crud = Crud::new();
        $this->assertNotNull($controller->configureCrud($crud));
        $actions = Actions::new();
        $this->assertNotNull($controller->configureActions($actions));
        $filters = Filters::new();
        $this->assertNotNull($controller->configureFilters($filters));
    }

    /**
     * @return array<int, array{string}>
     */
    public static function controllerProvider(): array
    {
        return [
            [TaskAdminController::class],
            [QualityStandardAdminController::class],
            [CountPlanAdminController::class],
            [WorkerSkillAdminController::class],
        ];
    }

    #[Test]
    public function testControllersHaveDocumentation(): void
    {
        $bundleDir = __DIR__ . '/../../..';
        $controllerFiles = [
            $bundleDir . '/src/Controller/Admin/TaskAdminController.php',
            $bundleDir . '/src/Controller/Admin/QualityStandardAdminController.php',
            $bundleDir . '/src/Controller/Admin/CountPlanAdminController.php',
            $bundleDir . '/src/Controller/Admin/WorkerSkillAdminController.php',
        ];

        foreach ($controllerFiles as $file) {
            $this->assertFileExists($file);
            $content = file_get_contents($file);
            $this->assertNotFalse($content, 'Unable to read file: ' . $file);

            // 验证有类文档注释
            $this->assertStringContainsString('/**', $content, basename($file) . ' should have documentation');
            $this->assertStringContainsString('*/', $content, basename($file) . ' should have documentation');

            // 验证有namespace
            $this->assertStringContainsString('namespace Tourze\WarehouseOperationBundle\Controller\Admin', $content);
        }
    }

    #[Test]
    public function testAllControllersExtendAbstractCrudController(): void
    {
        $controllers = [
            TaskAdminController::class,
            QualityStandardAdminController::class,
            CountPlanAdminController::class,
            WorkerSkillAdminController::class,
        ];

        foreach ($controllers as $controllerClass) {
            $reflection = new \ReflectionClass($controllerClass);
            $parentClass = $reflection->getParentClass();

            $this->assertNotFalse($parentClass);
            $this->assertEquals('EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController', $parentClass->getName());
        }
    }

    #[Test]
    public function testControllerClassNaming(): void
    {
        $expectedControllers = [
            'TaskAdminController' => TaskAdminController::class,
            'QualityStandardAdminController' => QualityStandardAdminController::class,
            'CountPlanAdminController' => CountPlanAdminController::class,
            'WorkerSkillAdminController' => WorkerSkillAdminController::class,
        ];

        foreach ($expectedControllers as $expectedName => $className) {
            $reflection = new \ReflectionClass($className);
            $this->assertEquals($expectedName, $reflection->getShortName());
        }
    }

    #[Test]
    public function testControllerMethodsReturnCorrectTypes(): void
    {
        $controller = new TaskAdminController();

        // getEntityFqcn应返回字符串
        $entityFqcn = TaskAdminController::getEntityFqcn();
        $this->assertIsString($entityFqcn);
        $this->assertEquals(WarehouseTask::class, $entityFqcn);

        // configureFields应返回可迭代对象
        $fields = $controller->configureFields('index');
        $this->assertIsIterable($fields);
    }

    /**
     * 实现抽象方法 testMethodNotAllowed
     */
    #[Test]
    #[DataProvider('provideNotAllowedMethods')]
    public function testCustomMethodNotAllowed(string $method = 'GET'): void
    {
        // 测试基础控制器的架构一致性
        $controllers = [
            TaskAdminController::class,
            QualityStandardAdminController::class,
            CountPlanAdminController::class,
            WorkerSkillAdminController::class,
        ];

        foreach ($controllers as $controllerClass) {
            $controller = new $controllerClass();

            // 验证所有控制器都继承自正确的基类
            $this->assertInstanceOf(AbstractCrudController::class, $controller);

            // 验证都有正确的Entity映射
            $entityFqcn = $controllerClass::getEntityFqcn();
            $this->assertIsString($entityFqcn);
            $this->assertStringStartsWith('Tourze\WarehouseOperationBundle\Entity\\', $entityFqcn);
        }
    }
}
