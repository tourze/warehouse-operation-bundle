<?php

declare(strict_types=1);

namespace Tourze\WarehouseOperationBundle\Tests\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\Test;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminControllerTestCase;
use Tourze\WarehouseOperationBundle\Controller\WorkerSkillCrudController;
use Tourze\WarehouseOperationBundle\Entity\WorkerSkill;

/**
 * 工人技能控制器测试
 *
 * 测试 WorkerSkillCrudController 的基本功能，确保控制器正确配置
 * 并能够正常工作。
 * @internal
 */
#[CoversClass(WorkerSkillCrudController::class)]
#[RunTestsInSeparateProcesses]
final class WorkerSkillCrudControllerTest extends AbstractEasyAdminControllerTestCase
{
    /**
     * @return WorkerSkillCrudController<WorkerSkill>
     */
    protected function getControllerService(): WorkerSkillCrudController
    {
        return self::getService(WorkerSkillCrudController::class);
    }

    /**
     * 测试控制器配置方法返回正确的实体类名
     */
    #[Test]
    public function testControllerReturnsCorrectEntityFqcn(): void
    {
        $this->assertSame(WorkerSkill::class, WorkerSkillCrudController::getEntityFqcn());
    }

    /**
     * 测试未登录用户访问应抛出AccessDeniedException
     *
     * 此测试已覆盖在父类的testUnauthenticatedAccessDenied测试中，
     * 因此这里标记为跳过避免重复测试
     */
    #[Test]
    public function testUnauthenticatedUserShouldThrowAccessDeniedException(): void
    {
        self::markTestSkipped('This test is covered by parent class testUnauthenticatedAccessDenied');
    }

    /**
     * 测试管理员用户可以成功访问工人技能列表
     *
     * 此测试已覆盖在父类的testIndexPageShowsConfiguredColumns测试中，
     * 因此这里标记为跳过避免重复测试
     */
    #[Test]
    public function testAdminUserCanAccessWorkerSkillIndex(): void
    {
        self::markTestSkipped('This test is covered by parent class testIndexPageShowsConfiguredColumns');
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
        $fieldNames = array_map(function ($field): string {
            if ($field instanceof FieldInterface) {
                return $field->getAsDto()->getProperty();
            }

            return '';
        }, $fields);

        $this->assertContainsEquals('id', $fieldNames, 'Should have id field');
        $this->assertContainsEquals('workerId', $fieldNames, 'Should have workerId field');
        $this->assertContainsEquals('workerName', $fieldNames, 'Should have workerName field');
        $this->assertContainsEquals('skillCategory', $fieldNames, 'Should have skillCategory field');
        $this->assertContainsEquals('skillLevel', $fieldNames, 'Should have skillLevel field');
        $this->assertContainsEquals('isActive', $fieldNames, 'Should have isActive field');
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
     * 测试验证规则 - 工人技能必填字段
     */
    #[Test]
    public function testValidationRules(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);

        // 创建测试实体来验证验证规则
        $workerSkill = new WorkerSkill();
        $validator = self::getService('Symfony\Component\Validator\Validator\ValidatorInterface');
        $violations = $validator->validate($workerSkill);

        // 验证必填字段有验证错误
        $this->assertGreaterThan(0, count($violations), 'Worker skill should have validation errors');

        // 检查是否有workerId字段的验证错误
        $hasWorkerIdError = false;
        foreach ($violations as $violation) {
            if ('workerId' === $violation->getPropertyPath()) {
                $hasWorkerIdError = true;
                $this->assertNotEmpty($violation->getMessage(), 'WorkerId field should have validation error message');
                break;
            }
        }
        $this->assertTrue($hasWorkerIdError, 'Should have workerId field validation error');
    }

    /**
     * 测试创建工人技能页面
     *
     * 此测试已覆盖在父类的testNewPageShowsConfiguredFields测试中，
     * 因此这里标记为跳过避免重复测试
     */
    #[Test]
    public function testCreateWorkerSkillPage(): void
    {
        self::markTestSkipped('This test is covered by parent class testNewPageShowsConfiguredFields');
    }

    /** @return \Generator<string, array{string}> */
    public static function provideIndexPageHeaders(): \Generator
    {
        yield 'ID' => ['ID'];
        yield '作业员ID' => ['作业员ID'];
        yield '作业员姓名' => ['作业员姓名'];
        yield '技能类别' => ['技能类别'];
        yield '技能等级' => ['技能等级'];
        yield '技能分数' => ['技能分数'];
        yield '认证日期' => ['认证日期'];
        yield '认证到期日期' => ['认证到期日期'];
        yield '是否启用' => ['是否启用'];
        yield '创建时间' => ['创建时间'];
        yield '更新时间' => ['更新时间'];
    }

    /** @return \Generator<string, array{string}> */
    public static function provideNewPageFields(): \Generator
    {
        yield 'workerId' => ['workerId'];
        yield 'workerName' => ['workerName'];
        yield 'skillCategory' => ['skillCategory'];
        yield 'skillLevel' => ['skillLevel'];
        yield 'skillScore' => ['skillScore'];
        yield 'certifiedAt' => ['certifiedAt'];
        yield 'expiresAt' => ['expiresAt'];
        yield 'isActive' => ['isActive'];
        yield 'notes' => ['notes'];
    }

    /** @return iterable<string, array{string}> */
    public static function provideEditPageFields(): iterable
    {
        return self::provideNewPageFields();
    }
}
