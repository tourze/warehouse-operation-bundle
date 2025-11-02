<?php

declare(strict_types=1);

namespace Tourze\WarehouseOperationBundle\Tests\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminControllerTestCase;
use Tourze\WarehouseOperationBundle\Controller\Admin\CountPlanAdminController;

/**
 * 盘点计划管理控制器测试
 *
 * 测试 CountPlanAdminController 的基本功能，确保控制器正确配置
 * 并能够正常工作。
 * @internal
 */
#[CoversClass(CountPlanAdminController::class)]
#[RunTestsInSeparateProcesses]
final class CountPlanAdminControllerTest extends AbstractEasyAdminControllerTestCase
{
    /**
     * @return CountPlanAdminController
     */
    protected function getControllerService(): CountPlanAdminController
    {
        return self::getService(CountPlanAdminController::class);
    }

    public static function provideIndexPageHeaders(): \Generator
    {
        // 按照控制器 configureFields('index') 的字段顺序提供表头标签
        yield 'ID' => ['ID'];
        yield '计划名称' => ['计划名称'];
        yield '盘点类型' => ['盘点类型'];
        yield '优先级' => ['优先级'];
        yield '启用状态' => ['启用状态'];
        yield '开始日期' => ['开始日期'];
    }

    public function testControllerExists(): void
    {
        $client = self::createAuthenticatedClient();

        // 测试控制器类可实例化
        $controller = new CountPlanAdminController();
        $this->assertInstanceOf(CountPlanAdminController::class, $controller);
    }

    public function testGetEntityFqcn(): void
    {
        $controller = new CountPlanAdminController();
        $entityFqcn = $controller::getEntityFqcn();

        $this->assertIsString($entityFqcn);
        $this->assertEquals('Tourze\WarehouseOperationBundle\Entity\CountPlan', $entityFqcn);
    }

    public function testConfigureFields(): void
    {
        $controller = new CountPlanAdminController();
        $fields = $controller->configureFields('index');

        $this->assertIsIterable($fields);

        // 验证至少有一些字段
        $fieldCount = 0;
        foreach ($fields as $field) {
            ++$fieldCount;
            $this->assertIsObject($field);
        }

        $this->assertGreaterThan(0, $fieldCount, '盘点计划控制器应该有字段配置');
    }

    public function testCustomActionMethods(): void
    {
        $controller = new CountPlanAdminController();

        // 测试自定义操作方法的可调用性和反射信息
        $reflection = new \ReflectionClass($controller);

        $methods = ['executeCountPlan', 'activatePlan', 'deactivatePlan', 'duplicatePlan', 'viewExecutionHistory'];
        foreach ($methods as $methodName) {
            $this->assertTrue($reflection->hasMethod($methodName), "Method {$methodName} should exist");
            $method = $reflection->getMethod($methodName);
            $this->assertTrue($method->isPublic(), "Method {$methodName} should be public");
        }
    }

    #[DataProvider('provideNotAllowedMethods')]
    public function testCustomMethodNotAllowed(string $method): void
    {
        // 对于INVALID方法，我们期望异常
        if ('INVALID' === $method) {
            $this->expectException(NotFoundHttpException::class);
        }

        // 测试访问非法HTTP方法的处理
        $client = self::createAuthenticatedClient();

        $client->request($method, '/admin/count-plan/1');

        if ('INVALID' !== $method) {
            $response = $client->getResponse();
            // EasyAdmin 控制器应该返回正确的状态码或重定向
            $this->assertContainsEquals($response->getStatusCode(), [405, 302, 404]);
        }
    }

    /**
     * 测试过滤器配置功能
     */
    public function testConfigureFilters(): void
    {
        $controller = new CountPlanAdminController();

        // 创建真实的Filters对象进行测试
        $filters = Filters::new();
        $configuredFilters = $controller->configureFilters($filters);

        // 验证返回的是同一个Filters对象（符合EasyAdmin设计）
        $this->assertSame($filters, $configuredFilters);
        $this->assertInstanceOf(Filters::class, $configuredFilters);

        // 验证控制器有必要的过滤器方法（通过反射）
        $reflection = new \ReflectionClass($controller);
        $this->assertTrue($reflection->hasMethod('configureFilters'));

        // 验证过滤器配置不为空
        $this->assertNotNull($configuredFilters);
    }

    /** @return \Generator<string, array{string}> */
    public static function provideNewPageFields(): \Generator
    {
        yield 'name' => ['name'];
        yield 'countType' => ['countType'];
        yield 'description' => ['description'];
        yield 'scope' => ['scope'];
        yield 'schedule' => ['schedule'];
        yield 'priority' => ['priority'];
        yield 'isActive' => ['isActive'];
        yield 'autoExecute' => ['autoExecute'];
        yield 'nextExecutionTime' => ['nextExecutionTime'];
        yield 'estimatedDurationHours' => ['estimatedDurationHours'];
    }

    /** @return iterable<string, array{string}> */
    public static function provideEditPageFields(): iterable
    {
        return self::provideNewPageFields();
    }
}
