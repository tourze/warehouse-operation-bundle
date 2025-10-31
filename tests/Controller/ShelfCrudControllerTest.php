<?php

declare(strict_types=1);

namespace Tourze\WarehouseOperationBundle\Tests\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminControllerTestCase;
use Tourze\WarehouseOperationBundle\Controller\ShelfCrudController;
use Tourze\WarehouseOperationBundle\Entity\Shelf;

/**
 * 货架控制器测试
 *
 * 测试 ShelfCrudController 的基本功能，确保控制器正确配置
 * 并能够正常工作。
 * @internal
 */
#[CoversClass(ShelfCrudController::class)]
#[RunTestsInSeparateProcesses]
final class ShelfCrudControllerTest extends AbstractEasyAdminControllerTestCase
{
    /**
     * @return ShelfCrudController<Shelf>
     */
    protected function getControllerService(): ShelfCrudController
    {
        return self::getService(ShelfCrudController::class);
    }

    /**
     * 测试控制器配置方法返回正确的实体类名
     */
    #[Test]
    public function testControllerReturnsCorrectEntityFqcn(): void
    {
        $this->assertSame(Shelf::class, ShelfCrudController::getEntityFqcn());
    }

    /**
     * 测试未登录用户访问应抛出AccessDeniedException
     */
    #[Test]
    public function testUnauthenticatedUserShouldThrowAccessDeniedException(): void
    {
        $client = self::createClientWithDatabase();

        $this->expectException(AccessDeniedException::class);
        $url = $this->generateAdminUrl('index');
        $client->request('GET', $url);
    }

    /**
     * 测试管理员用户可以成功访问货架列表
     */
    #[Test]
    public function testAdminUserCanAccessShelfIndex(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);

        $url = $this->generateAdminUrl('index');
        $crawler = $client->request('GET', $url);

        $this->assertTrue($client->getResponse()->isSuccessful(), 'Admin should be able to access shelf index');
        $content = $crawler->text();
        $this->assertStringContainsString('货架', $content, 'Page should contain shelf text');
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
     * 测试字段配置包含预期字段
     */
    #[Test]
    public function testControllerConfiguresFields(): void
    {
        $controller = $this->getControllerService();
        $fields = iterator_to_array($controller->configureFields('index'));

        $this->assertNotEmpty($fields, 'Controller should configure fields');
        $this->assertGreaterThan(3, count($fields), 'Controller should configure multiple fields');

        // 验证基本字段存在
        $fieldNames = array_map(
            /**
             * @param FieldInterface|string $field
             */
            static function ($field): string {
                if ($field instanceof FieldInterface) {
                    $dto = $field->getAsDto();

                    return $dto->getProperty();
                }

                self::fail('无法从字段对象中解析属性名');
            },
            $fields
        );

        $this->assertContainsEquals('id', $fieldNames, 'Should have id field');
        $this->assertContainsEquals('zone', $fieldNames, 'Should have zone field');
        $this->assertContainsEquals('title', $fieldNames, 'Should have title field');
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
     * 测试验证规则 - 货架必填字段
     */
    #[Test]
    public function testValidationRules(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);

        // 创建测试实体来验证验证规则
        $shelf = new Shelf();
        $validator = self::getService('Symfony\Component\Validator\Validator\ValidatorInterface');
        $violations = $validator->validate($shelf);

        // 验证必填字段有验证错误
        $this->assertGreaterThan(0, count($violations), 'Shelf should have validation errors');

        // 检查是否有title字段的验证错误
        $hasTitleError = false;
        foreach ($violations as $violation) {
            if ('title' === $violation->getPropertyPath()) {
                $hasTitleError = true;
                $this->assertNotEmpty($violation->getMessage(), 'Title field should have validation error message');
                break;
            }
        }
        $this->assertTrue($hasTitleError, 'Should have title field validation error');
    }

    /**
     * 测试创建货架页面
     */
    #[Test]
    public function testCreateShelfPage(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);

        $url = $this->generateAdminUrl('new');
        $crawler = $client->request('GET', $url);

        $this->assertTrue($client->getResponse()->isSuccessful(), 'Should be able to access create page');
        $content = $crawler->text();
        $this->assertStringContainsString('新建货架', $content, 'Create page should have correct title');
    }

    /** @return \Generator<string, array{string}> */
    public static function provideIndexPageHeaders(): \Generator
    {
        yield 'ID' => ['ID'];
        yield '库区' => ['库区'];
        yield '货架名称' => ['货架名称'];
        yield '创建时间' => ['创建时间'];
        yield '更新时间' => ['更新时间'];
    }

    /** @return \Generator<string, array{string}> */
    public static function provideNewPageFields(): \Generator
    {
        yield 'zone' => ['zone'];
        yield 'title' => ['title'];
    }

    /** @return iterable<string, array{string}> */
    public static function provideEditPageFields(): iterable
    {
        return self::provideNewPageFields();
    }
}
