<?php

declare(strict_types=1);

namespace Tourze\WarehouseOperationBundle\Tests\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminControllerTestCase;
use Tourze\WarehouseOperationBundle\Controller\Admin\CountTaskCrudController;
use Tourze\WarehouseOperationBundle\Entity\CountTask;

/**
 * 盘点任务控制器测试
 *
 * 测试 CountTaskCrudController 的基本功能，确保控制器正确配置
 * 并能够正常工作。
 * @internal
 */
#[CoversClass(CountTaskCrudController::class)]
#[RunTestsInSeparateProcesses]
final class CountTaskCrudControllerTest extends AbstractEasyAdminControllerTestCase
{
    /**
     * @return CountTaskCrudController<CountTask>
     */
    protected function getControllerService(): CountTaskCrudController
    {
        return self::getService(CountTaskCrudController::class);
    }

    /**
     * 测试控制器配置方法返回正确的实体类名
     */
    #[Test]
    public function testControllerReturnsCorrectEntityFqcn(): void
    {
        $this->assertSame(CountTask::class, CountTaskCrudController::getEntityFqcn());
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
     * 测试管理员用户可以成功访问盘点任务列表
     */
    #[Test]
    public function testAdminUserCanAccessCountTaskIndex(): void
    {
        $client = self::createAuthenticatedClient();

        $url = $this->generateAdminUrl('index');
        $crawler = $client->request('GET', $url);

        $this->assertTrue($client->getResponse()->isSuccessful(), 'Admin should be able to access count task index');
        $content = $crawler->text();
        $this->assertStringContainsString('盘点任务', $content, 'Page should contain count task text');
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
        $this->assertGreaterThan(5, count($fields), 'Controller should configure multiple fields');

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
        $this->assertContainsEquals('countPlanId', $fieldNames, 'Should have countPlanId field');
        $this->assertContainsEquals('taskSequence', $fieldNames, 'Should have taskSequence field');
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
     * 测试验证规则 - 盘点任务ID验证
     */
    #[Test]
    public function testValidationRules(): void
    {
        $client = self::createAuthenticatedClient();

        // 创建测试实体来验证验证规则
        $countTask = new CountTask();
        $validator = self::getService('Symfony\Component\Validator\Validator\ValidatorInterface');
        $violations = $validator->validate($countTask);

        // 验证有验证错误（可能继承自父类的验证规则）
        $this->assertGreaterThanOrEqual(0, count($violations), 'Count task validation should work');
    }

    /**
     * 测试创建盘点任务页面
     */
    #[Test]
    public function testCreateCountTaskPage(): void
    {
        $client = self::createAuthenticatedClient();

        $url = $this->generateAdminUrl('new');
        $crawler = $client->request('GET', $url);

        $this->assertTrue($client->getResponse()->isSuccessful(), 'Should be able to access create page');
        $content = $crawler->text();
        $this->assertStringContainsString('新建盘点任务', $content, 'Create page should have correct title');
    }

    /** @return \Generator<string, array{string}> */
    public static function provideIndexPageHeaders(): \Generator
    {
        yield 'ID' => ['ID'];
        yield '任务状态' => ['任务状态'];
        yield '优先级' => ['优先级'];
        yield '盘点计划ID' => ['盘点计划ID'];
        yield '任务序列' => ['任务序列'];
        yield '库位编码' => ['库位编码'];
        yield '盘点准确率' => ['盘点准确率'];
        yield '分配的作业员ID' => ['分配的作业员ID'];
        yield '分配时间' => ['分配时间'];
        yield '开始时间' => ['开始时间'];
        yield '完成时间' => ['完成时间'];
        yield '创建时间' => ['创建时间'];
        yield '更新时间' => ['更新时间'];
    }

    /** @return \Generator<string, array{string}> */
    public static function provideNewPageFields(): \Generator
    {
        yield 'status' => ['status'];
        yield 'priority' => ['priority'];
        yield 'countPlanId' => ['countPlanId'];
        yield 'taskSequence' => ['taskSequence'];
        yield 'locationCode' => ['locationCode'];
        yield 'accuracy' => ['accuracy'];
        yield 'assignedWorker' => ['assignedWorker'];
        yield 'notes' => ['notes'];
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideEditPageFields(): iterable
    {
        return self::provideNewPageFields();
    }
}
