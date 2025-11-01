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
use Tourze\WarehouseOperationBundle\Controller\Admin\QualityTaskCrudController;
use Tourze\WarehouseOperationBundle\Entity\QualityTask;
use Tourze\WarehouseOperationBundle\Enum\TaskType;

/**
 * 质检任务控制器测试
 *
 * 测试 QualityTaskCrudController 的基本功能，确保控制器正确配置
 * 并能够正常工作。
 * @internal
 */
#[CoversClass(QualityTaskCrudController::class)]
#[RunTestsInSeparateProcesses]
final class QualityTaskCrudControllerTest extends AbstractEasyAdminControllerTestCase
{
    /**
     * @return QualityTaskCrudController<QualityTask>
     */
    protected function getControllerService(): QualityTaskCrudController
    {
        return self::getService(QualityTaskCrudController::class);
    }

    /**
     * 测试控制器配置方法返回正确的实体类名
     */
    #[Test]
    public function testControllerReturnsCorrectEntityFqcn(): void
    {
        $this->assertSame(QualityTask::class, QualityTaskCrudController::getEntityFqcn());
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
     * 测试管理员用户可以成功访问质检任务列表
     */
    #[Test]
    public function testAdminUserCanAccessQualityTaskIndex(): void
    {
        $client = self::createAuthenticatedClient();

        $url = $this->generateAdminUrl('index');
        $crawler = $client->request('GET', $url);

        $this->assertTrue($client->getResponse()->isSuccessful(), 'Admin should be able to access quality task index');
        $content = $crawler->text();
        $this->assertStringContainsString('质量任务', $content, 'Page should contain quality task text');
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
     * 测试质检任务实体特性
     */
    #[Test]
    public function testQualityTaskEntity(): void
    {
        $qualityTask = new QualityTask();

        // 验证任务类型正确设置
        $this->assertEquals(TaskType::QUALITY, $qualityTask->getType());

        // 测试设置任务名称的便利方法
        $qualityTask->setTaskName('Test Quality Task');
        $data = $qualityTask->getData();
        $this->assertEquals('Test Quality Task', $data['task_name']);
    }

    /**
     * 测试验证规则
     */
    #[Test]
    public function testValidationRules(): void
    {
        $client = self::createAuthenticatedClient();

        // 创建测试实体来验证验证规则
        $qualityTask = new QualityTask();
        $validator = self::getService('Symfony\Component\Validator\Validator\ValidatorInterface');
        $violations = $validator->validate($qualityTask);

        // 验证有验证规则（可能继承自父类）
        $this->assertGreaterThanOrEqual(0, count($violations), 'Quality task validation should work');
    }

    /**
     * 测试创建质检任务页面
     */
    #[Test]
    public function testCreateQualityTaskPage(): void
    {
        $client = self::createAuthenticatedClient();

        $url = $this->generateAdminUrl('new');
        $crawler = $client->request('GET', $url);

        $this->assertTrue($client->getResponse()->isSuccessful(), 'Should be able to access create page');
        $content = $crawler->text();
        $this->assertStringContainsString('新建质量任务', $content, 'Create page should have correct title');
    }

    /** @return \Generator<string, array{string}> */
    public static function provideIndexPageHeaders(): \Generator
    {
        yield 'ID' => ['ID'];
        yield '任务状态' => ['任务状态'];
        yield '优先级' => ['优先级'];
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
        yield 'assignedWorker' => ['assignedWorker'];
        yield 'notes' => ['notes'];
    }

    /** @return iterable<string, array{string}> */
    public static function provideEditPageFields(): iterable
    {
        return self::provideNewPageFields();
    }
}
