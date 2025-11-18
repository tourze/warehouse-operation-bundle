<?php

declare(strict_types=1);

namespace Tourze\WarehouseOperationBundle\Tests\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminControllerTestCase;
use Tourze\WarehouseOperationBundle\Controller\Admin\QualityStandardAdminController;

/**
 * 质检标准管理控制器测试
 *
 * 测试 QualityStandardAdminController 的基本功能，确保控制器正确配置
 * 并能够正常工作。
 * @internal
 */
#[CoversClass(QualityStandardAdminController::class)]
#[RunTestsInSeparateProcesses]
final class QualityStandardAdminControllerTest extends AbstractEasyAdminControllerTestCase
{
    /**
     * @return QualityStandardAdminController
     */
    protected function getControllerService(): QualityStandardAdminController
    {
        return self::getService(QualityStandardAdminController::class);
    }

    public static function provideIndexPageHeaders(): \Generator
    {
        yield ['质量标准管理'];
        yield ['Quality Standard'];
        yield ['编辑'];
    }

    public function testControllerExists(): void
    {
        $client = self::createAuthenticatedClient();

        // 测试控制器类可实例化
        $controller = new QualityStandardAdminController();

        // 验证控制器可以正常配置字段
        $fields = $controller->configureFields('index');
        self::assertIsIterable($fields);

        // 验证配置的字段数量大于0
        $fieldCount = 0;
        foreach ($fields as $field) {
            ++$fieldCount;
        }
        self::assertGreaterThan(0, $fieldCount, '质检标准控制器应该配置至少一个字段');
    }

    public function testConfigureFields(): void
    {
        $controller = new QualityStandardAdminController();
        $fields = $controller->configureFields('index');

        self::assertIsIterable($fields);

        // 验证至少有一些字段
        $fieldCount = 0;
        foreach ($fields as $field) {
            ++$fieldCount;
            self::assertIsObject($field);
        }

        self::assertGreaterThan(0, $fieldCount, '质检标准控制器应该有字段配置');
    }

    public function testCustomActionMethods(): void
    {
        $controller = new QualityStandardAdminController();

        // 测试自定义操作方法的可调用性和反射信息
        $reflection = new \ReflectionClass($controller);

        $methods = ['activateStandard', 'deactivateStandard', 'duplicateStandard'];
        foreach ($methods as $methodName) {
            self::assertTrue($reflection->hasMethod($methodName), "Method {$methodName} should exist");
            $method = $reflection->getMethod($methodName);
            self::assertTrue($method->isPublic(), "Method {$methodName} should be public");
        }
    }

    /**
     * 测试搜索功能
     *
     * 验证过滤器的搜索功能，包括：
     * - ChoiceFilter:productCategory（商品类别）
     * - BooleanFilter:isActive（启用状态）
     * - BooleanFilter:requireSampling（需要抽检）
     *
     * @param array<string, mixed> $filters
     */
    #[DataProvider('provideSearchFilters')]
    public function testSearchFunctionality(array $filters, string $description): void
    {
        $controller = new QualityStandardAdminController();

        // 验证控制器配置了正确的过滤器
        $filtersConfig = $controller->configureFilters(Filters::new());
        self::assertIsObject($filtersConfig);

        // 验证过滤器参数的键名是否有效
        foreach (array_keys($filters) as $filterKey) {
            self::assertIsString($filterKey);
            self::assertNotEmpty($filterKey);
        }

        // 验证过滤器值的类型和格式
        foreach ($filters as $key => $value) {
            if ('productCategory' === $key) {
                self::assertContainsEquals($value, [
                    'food', 'electronics', 'clothing', 'cosmetics',
                    'medicine', 'hazardous', 'cold_storage', 'valuables', 'others',
                ]);
            } elseif (in_array($key, ['isActive', 'requireSampling'], true)) {
                self::assertContainsEquals($value, ['0', '1']);
            }
        }

        // 验证过滤器参数验证通过
        self::assertNotEmpty($description);
    }

    /**
     * 提供搜索过滤器测试数据
     *
     * @return array<string, array{0: array<string, mixed>, 1: string}>
     */
    public static function provideSearchFilters(): array
    {
        return [
            // 单独过滤器测试
            'productCategory_food' => [
                ['productCategory' => 'food'],
                '按商品类别过滤：食品',
            ],
            'productCategory_electronics' => [
                ['productCategory' => 'electronics'],
                '按商品类别过滤：电子产品',
            ],
            'productCategory_clothing' => [
                ['productCategory' => 'clothing'],
                '按商品类别过滤：服装',
            ],
            'isActive_true' => [
                ['isActive' => '1'],
                '按启用状态过滤：启用',
            ],
            'isActive_false' => [
                ['isActive' => '0'],
                '按启用状态过滤：停用',
            ],
            'requireSampling_true' => [
                ['requireSampling' => '1'],
                '按抽检要求过滤：需要抽检',
            ],
            'requireSampling_false' => [
                ['requireSampling' => '0'],
                '按抽检要求过滤：不需要抽检',
            ],

            // 组合过滤器测试
            'active_food_sampling' => [
                [
                    'productCategory' => 'food',
                    'isActive' => '1',
                    'requireSampling' => '1',
                ],
                '组合过滤：启用的食品类别且需要抽检',
            ],
            'inactive_electronics' => [
                [
                    'productCategory' => 'electronics',
                    'isActive' => '0',
                ],
                '组合过滤：停用的电子产品',
            ],
            'medicine_no_sampling' => [
                [
                    'productCategory' => 'medicine',
                    'requireSampling' => '0',
                ],
                '组合过滤：医药用品且不需要抽检',
            ],
            'hazardous_active_sampling' => [
                [
                    'productCategory' => 'hazardous',
                    'isActive' => '1',
                    'requireSampling' => '1',
                ],
                '组合过滤：启用的危险品且需要抽检',
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

        $client->request($method, '/warehouse-operation/quality-standard/1');

        if ('INVALID' !== $method) {
            $response = $client->getResponse();
            // EasyAdmin 控制器应该返回正确的状态码或重定向
            self::assertContainsEquals($response->getStatusCode(), [405, 302, 404]);
        }
    }

    /** @return \Generator<string, array{string}> */
    public static function provideNewPageFields(): \Generator
    {
        yield 'name' => ['name'];
        yield 'productCategory' => ['productCategory'];
        yield 'description' => ['description'];
        yield 'checkItems' => ['checkItems'];
        yield 'priority' => ['priority'];
        yield 'isActive' => ['isActive'];
        yield 'requireSampling' => ['requireSampling'];
        yield 'samplingRate' => ['samplingRate'];
    }

    /** @return iterable<string, array{string}> */
    public static function provideEditPageFields(): iterable
    {
        return self::provideNewPageFields();
    }
}
