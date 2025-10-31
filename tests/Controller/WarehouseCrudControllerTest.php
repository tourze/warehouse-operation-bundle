<?php

declare(strict_types=1);

namespace Tourze\WarehouseOperationBundle\Tests\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\Test;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminControllerTestCase;
use Tourze\WarehouseOperationBundle\Controller\WarehouseCrudController;
use Tourze\WarehouseOperationBundle\Entity\Warehouse;

/**
 * 仓库控制器测试
 *
 * 测试 WarehouseCrudController 的基本功能，确保控制器正确配置
 * 并能够正常工作。
 * @internal
 */
#[CoversClass(WarehouseCrudController::class)]
#[RunTestsInSeparateProcesses]
final class WarehouseCrudControllerTest extends AbstractEasyAdminControllerTestCase
{
    /**
     * @return WarehouseCrudController<Warehouse>
     */
    protected function getControllerService(): WarehouseCrudController
    {
        return self::getService(WarehouseCrudController::class);
    }

    /**
     * 测试控制器配置方法返回正确的实体类名
     */
    #[Test]
    public function testControllerReturnsCorrectEntityFqcn(): void
    {
        $this->assertSame(Warehouse::class, WarehouseCrudController::getEntityFqcn());
    }

    /**
     * 测试控制器的CRUD配置
     */
    #[Test]
    public function testControllerCrudConfiguration(): void
    {
        $controller = $this->getControllerService();

        // 验证基本配置方法可以调用
        $crud = $controller->configureCrud(Crud::new());
        $this->assertInstanceOf(Crud::class, $crud);

        $fields = $controller->configureFields('index');
        $this->assertNotNull($fields);

        $filters = $controller->configureFilters(Filters::new());
        $this->assertInstanceOf(Filters::class, $filters);
    }

    /**
     * 测试控制器配置方法存在且可调用
     */
    #[Test]
    public function testControllerConfigurationMethodsExist(): void
    {
        $controller = $this->getControllerService();

        // 直接调用方法来验证它们存在且可用
        $crud = $controller->configureCrud(Crud::new());
        $fields = $controller->configureFields('index');
        $filters = $controller->configureFilters(Filters::new());

        $this->assertInstanceOf(Crud::class, $crud);
        $this->assertNotNull($fields);
        $this->assertInstanceOf(Filters::class, $filters);
    }

    /**
     * 测试验证规则 - 仓库必填字段
     */
    #[Test]
    public function testValidationRules(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);

        // 创建测试实体来验证验证规则
        $warehouse = new Warehouse();
        $validator = self::getService('Symfony\Component\Validator\Validator\ValidatorInterface');
        $violations = $validator->validate($warehouse);

        // 验证必填字段有验证错误
        $this->assertGreaterThan(0, count($violations), 'Warehouse should have validation errors');

        // 检查是否有code字段的验证错误
        $hasCodeError = false;
        foreach ($violations as $violation) {
            if ('code' === $violation->getPropertyPath()) {
                $hasCodeError = true;
                $this->assertNotEmpty($violation->getMessage(), 'Code field should have validation error message');
                break;
            }
        }
        $this->assertTrue($hasCodeError, 'Should have code field validation error');
    }

    /** @return \Generator<string, array{string}> */
    public static function provideIndexPageHeaders(): \Generator
    {
        yield 'ID' => ['ID'];
        yield '代号' => ['代号'];
        yield '名称' => ['名称'];
        yield '联系人' => ['联系人'];
        yield '联系电话' => ['联系电话'];
        yield '创建时间' => ['创建时间'];
        yield '更新时间' => ['更新时间'];
    }

    /** @return \Generator<string, array{string}> */
    public static function provideNewPageFields(): \Generator
    {
        yield 'code' => ['code'];
        yield 'name' => ['name'];
        yield 'contactName' => ['contactName'];
        yield 'contactTel' => ['contactTel'];
    }

    /** @return iterable<string, array{string}> */
    public static function provideEditPageFields(): iterable
    {
        return self::provideNewPageFields();
    }
}
