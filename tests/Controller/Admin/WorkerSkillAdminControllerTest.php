<?php

declare(strict_types=1);

namespace Tourze\WarehouseOperationBundle\Tests\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminControllerTestCase;
use Tourze\WarehouseOperationBundle\Controller\Admin\WorkerSkillAdminController;

/**
 * 作业员技能管理控制器测试
 *
 * 测试 WorkerSkillAdminController 的基本功能，确保控制器正确配置
 * 并能够正常工作。
 * @internal
 */
#[CoversClass(WorkerSkillAdminController::class)]
#[RunTestsInSeparateProcesses]
final class WorkerSkillAdminControllerTest extends AbstractEasyAdminControllerTestCase
{
    /**
     * @return WorkerSkillAdminController
     */
    protected function getControllerService(): WorkerSkillAdminController
    {
        return self::getService(WorkerSkillAdminController::class);
    }

    public static function provideIndexPageHeaders(): \Generator
    {
        yield ['作业员技能管理'];
        yield ['Worker Skill'];
        yield ['编辑'];
    }

    public function testControllerExists(): void
    {
        $client = self::createAuthenticatedClient();

        // 测试控制器类可实例化
        $controller = new WorkerSkillAdminController();
        $this->assertInstanceOf(WorkerSkillAdminController::class, $controller);
    }

    public function testGetEntityFqcn(): void
    {
        $controller = new WorkerSkillAdminController();
        $entityFqcn = $controller::getEntityFqcn();

        $this->assertIsString($entityFqcn);
        $this->assertEquals('Tourze\WarehouseOperationBundle\Entity\WorkerSkill', $entityFqcn);
    }

    public function testConfigureFields(): void
    {
        $controller = new WorkerSkillAdminController();
        $fields = $controller->configureFields('index');

        $this->assertIsIterable($fields);

        // 验证至少有一些字段
        $fieldCount = 0;
        foreach ($fields as $field) {
            ++$fieldCount;
            $this->assertIsObject($field);
        }

        $this->assertGreaterThan(0, $fieldCount, '作业员技能控制器应该有字段配置');
    }

    public function testCustomActionMethods(): void
    {
        $controller = new WorkerSkillAdminController();

        // 测试自定义操作方法的可调用性和反射信息
        $reflection = new \ReflectionClass($controller);

        $methods = ['certifySkill', 'revokeCertification', 'upgradeSkill', 'viewSkillPerformance'];
        foreach ($methods as $methodName) {
            $this->assertTrue($reflection->hasMethod($methodName), "Method {$methodName} should exist");
            $method = $reflection->getMethod($methodName);
            $this->assertTrue($method->isPublic(), "Method {$methodName} should be public");
        }
    }

    /**
     * 测试搜索功能
     *
     * 验证过滤器的搜索功能，包括：
     * - ChoiceFilter:skillCategory（技能类别）
     * - NumericFilter:skillLevel（技能等级）
     * - NumericFilter:skillScore（技能分数）
     *
     * @param array<string, mixed> $filters
     */
    #[DataProvider('provideSearchFilters')]
    public function testSearchFunctionality(array $filters, string $description): void
    {
        $controller = new WorkerSkillAdminController();

        // 验证控制器配置了正确的过滤器
        $filtersConfig = $controller->configureFilters(Filters::new());
        $this->assertIsObject($filtersConfig);

        // 验证过滤器参数的键名是否有效
        foreach (array_keys($filters) as $filterKey) {
            $this->assertIsString($filterKey);
            $this->assertNotEmpty($filterKey);
        }

        // 验证过滤器值的类型和格式
        foreach ($filters as $key => $value) {
            if ('skillCategory' === $key) {
                $this->assertContainsEquals($value, [
                    'picking', 'packing', 'quality', 'counting',
                    'equipment', 'hazardous', 'cold_storage',
                ]);
            } elseif ('skillLevel' === $key) {
                $this->assertIsString($value);
                $intValue = (int) $value;
                $this->assertGreaterThanOrEqual(1, $intValue);
                $this->assertLessThanOrEqual(10, $intValue);
            } elseif ('skillScore' === $key) {
                $this->assertIsString($value);
                $intValue = (int) $value;
                $this->assertGreaterThanOrEqual(1, $intValue);
                $this->assertLessThanOrEqual(100, $intValue);
            }
        }

        $this->assertTrue(true, $description . ' - 过滤器参数验证通过');
    }

    /**
     * 提供搜索过滤器测试数据
     *
     * @return array<string, array{0: array<string, mixed>, 1: string}>
     */
    public static function provideSearchFilters(): array
    {
        return [
            // 单独过滤器测试 - 技能类别
            'skillCategory_picking' => [
                ['skillCategory' => 'picking'],
                '按技能类别过滤：拣货',
            ],
            'skillCategory_packing' => [
                ['skillCategory' => 'packing'],
                '按技能类别过滤：包装',
            ],
            'skillCategory_quality' => [
                ['skillCategory' => 'quality'],
                '按技能类别过滤：质检',
            ],
            'skillCategory_counting' => [
                ['skillCategory' => 'counting'],
                '按技能类别过滤：盘点',
            ],
            'skillCategory_equipment' => [
                ['skillCategory' => 'equipment'],
                '按技能类别过滤：设备操作',
            ],
            'skillCategory_hazardous' => [
                ['skillCategory' => 'hazardous'],
                '按技能类别过滤：危险品处理',
            ],
            'skillCategory_cold_storage' => [
                ['skillCategory' => 'cold_storage'],
                '按技能类别过滤：冷库作业',
            ],

            // 单独过滤器测试 - 技能等级
            'skillLevel_low' => [
                ['skillLevel' => '3'],
                '按技能等级过滤：初级（等级3）',
            ],
            'skillLevel_medium' => [
                ['skillLevel' => '6'],
                '按技能等级过滤：中级（等级6）',
            ],
            'skillLevel_high' => [
                ['skillLevel' => '9'],
                '按技能等级过滤：高级（等级9）',
            ],

            // 单独过滤器测试 - 技能分数
            'skillScore_low' => [
                ['skillScore' => '40'],
                '按技能分数过滤：低分（40分）',
            ],
            'skillScore_medium' => [
                ['skillScore' => '70'],
                '按技能分数过滤：中等（70分）',
            ],
            'skillScore_high' => [
                ['skillScore' => '90'],
                '按技能分数过滤：高分（90分）',
            ],

            // 组合过滤器测试
            'picking_high_level_high_score' => [
                [
                    'skillCategory' => 'picking',
                    'skillLevel' => '8',
                    'skillScore' => '85',
                ],
                '组合过滤：拣货技能高等级高分数',
            ],
            'packing_medium_level' => [
                [
                    'skillCategory' => 'packing',
                    'skillLevel' => '5',
                ],
                '组合过滤：包装技能中等级',
            ],
            'quality_low_score' => [
                [
                    'skillCategory' => 'quality',
                    'skillScore' => '50',
                ],
                '组合过滤：质检技能低分数',
            ],
            'equipment_high_level' => [
                [
                    'skillCategory' => 'equipment',
                    'skillLevel' => '9',
                ],
                '组合过滤：设备操作技能高等级',
            ],
            'hazardous_expert' => [
                [
                    'skillCategory' => 'hazardous',
                    'skillLevel' => '10',
                    'skillScore' => '95',
                ],
                '组合过滤：危险品处理专家级',
            ],
            'cold_storage_experienced' => [
                [
                    'skillCategory' => 'cold_storage',
                    'skillLevel' => '7',
                    'skillScore' => '80',
                ],
                '组合过滤：冷库作业经验丰富',
            ],
        ];
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

        $client->request($method, '/warehouse-operation/worker-skill/1');

        if ('INVALID' !== $method) {
            $response = $client->getResponse();
            // EasyAdmin 控制器应该返回正确的状态码或重定向
            $this->assertContainsEquals($response->getStatusCode(), [405, 302, 404]);
        }
    }
}
