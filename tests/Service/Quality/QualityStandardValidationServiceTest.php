<?php

declare(strict_types=1);

namespace Tourze\WarehouseOperationBundle\Tests\Service\Quality;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tourze\WarehouseOperationBundle\Entity\QualityStandard;
use Tourze\WarehouseOperationBundle\Service\Quality\QualityStandardValidationService;

/**
 * QualityStandardValidationService 单元测试
 *
 * 测试质检标准验证服务的完整功能，包括标准验证、检查项验证、必需检查项验证、
 * 检查项配置验证和基本字段验证等核心业务逻辑。
 * 验证服务的正确性、验证规则和错误处理。
 *
 * @internal
 */
#[CoversClass(QualityStandardValidationService::class)]
final class QualityStandardValidationServiceTest extends TestCase
{
    private QualityStandardValidationService $service;

    protected function setUp(): void
    {
        $this->service = new QualityStandardValidationService();
    }

    protected function tearDown(): void
    {
        unset($this->service);
    }

    /**
     * 测试完全有效的质检标准
     */
    public function testValidateQualityStandardWithValidStandard(): void
    {
        $standard = new QualityStandard();
        $standard->setName('完整质检标准');
        $standard->setProductCategory('电子产品');
        $standard->setPriority(50);
        $standard->setCheckItems([
            'visual_check' => [
                'enabled' => true,
                'weight' => 30,
                'criteria' => ['无划痕', '无变形'],
            ],
            'quantity_check' => [
                'enabled' => true,
                'weight' => 40,
                'criteria' => ['数量准确'],
            ],
            'function_check' => [
                'enabled' => true,
                'weight' => 30,
                'criteria' => ['功能正常'],
            ],
        ]);

        $result = $this->service->validateQualityStandard($standard);

        $this->assertTrue($result['is_valid']);
        $this->assertEmpty($result['validation_errors']);
        $this->assertArrayHasKey('warnings', $result);
        $this->assertArrayHasKey('suggestions', $result);
    }

    /**
     * 测试空检查项的验证错误
     */
    public function testValidateQualityStandardWithEmptyCheckItems(): void
    {
        $standard = new QualityStandard();
        $standard->setName('无检查项标准');
        $standard->setProductCategory('测试类别');
        $standard->setPriority(50);
        $standard->setCheckItems([]);

        $result = $this->service->validateQualityStandard($standard);

        $this->assertFalse($result['is_valid']);
        self::assertIsArray($result['validation_errors']);
        self::assertIsIterable($result['validation_errors']);
        $this->assertContains('质检标准必须包含至少一个检查项', $result['validation_errors']);
    }

    /**
     * 测试缺少必需检查项的警告
     */
    public function testValidateQualityStandardWithMissingRequiredChecks(): void
    {
        $standard = new QualityStandard();
        $standard->setName('缺少必需检查项');
        $standard->setProductCategory('测试类别');
        $standard->setPriority(50);
        $standard->setCheckItems([
            'function_check' => [
                'enabled' => true,
                'weight' => 100,
                'criteria' => ['测试'],
            ],
        ]);

        $result = $this->service->validateQualityStandard($standard);

        $this->assertTrue($result['is_valid']); // 有检查项，所以有效
        self::assertIsArray($result['warnings']);
        self::assertIsIterable($result['warnings']);
        $this->assertContains('建议添加 visual_check 检查项', $result['warnings']);
        $this->assertContains('建议添加 quantity_check 检查项', $result['warnings']);
    }

    /**
     * 测试包含所有必需检查项
     */
    public function testValidateQualityStandardWithAllRequiredChecks(): void
    {
        $standard = new QualityStandard();
        $standard->setName('完整检查项');
        $standard->setProductCategory('测试类别');
        $standard->setPriority(50);
        $standard->setCheckItems([
            'visual_check' => [
                'enabled' => true,
                'weight' => 50,
            ],
            'quantity_check' => [
                'enabled' => true,
                'weight' => 50,
            ],
        ]);

        $result = $this->service->validateQualityStandard($standard);

        $this->assertTrue($result['is_valid']);
        // 不应该有关于缺少 visual_check 或 quantity_check 的警告
        self::assertIsArray($result['warnings']);
        self::assertIsIterable($result['warnings']);
        foreach ($result['warnings'] as $warning) {
            self::assertIsString($warning);
            $this->assertStringNotContainsString('建议添加 visual_check', $warning);
            $this->assertStringNotContainsString('建议添加 quantity_check', $warning);
        }
    }

    /**
     * 测试检查项配置格式错误
     */
    public function testValidateQualityStandardWithInvalidCheckItemConfig(): void
    {
        $standard = new QualityStandard();
        $standard->setName('错误配置');
        $standard->setProductCategory('测试类别');
        $standard->setPriority(50);
        $standard->setCheckItems([
            'visual_check' => 'invalid_config', // 应该是数组
            'quantity_check' => [
                'enabled' => true,
            ],
        ]);

        $result = $this->service->validateQualityStandard($standard);

        $this->assertFalse($result['is_valid']);
        self::assertIsArray($result['validation_errors']);
        self::assertIsIterable($result['validation_errors']);
        $this->assertContains('检查项 visual_check 配置格式错误', $result['validation_errors']);
    }

    /**
     * 测试检查项缺少enabled配置的警告
     */
    public function testValidateQualityStandardWithMissingEnabledConfig(): void
    {
        $standard = new QualityStandard();
        $standard->setName('缺少enabled配置');
        $standard->setProductCategory('测试类别');
        $standard->setPriority(50);
        $standard->setCheckItems([
            'visual_check' => [
                'weight' => 100,
                // 缺少 enabled
            ],
        ]);

        $result = $this->service->validateQualityStandard($standard);

        $this->assertTrue($result['is_valid']); // 只是警告，不是错误
        self::assertIsArray($result['warnings']);
        self::assertIsIterable($result['warnings']);
        $this->assertContains('检查项 visual_check 缺少 enabled 配置', $result['warnings']);
    }

    /**
     * 测试检查项缺少权重的建议
     */
    public function testValidateQualityStandardWithMissingWeight(): void
    {
        $standard = new QualityStandard();
        $standard->setName('缺少权重');
        $standard->setProductCategory('测试类别');
        $standard->setPriority(50);
        $standard->setCheckItems([
            'visual_check' => [
                'enabled' => true,
                // 缺少 weight
            ],
        ]);

        $result = $this->service->validateQualityStandard($standard);

        $this->assertTrue($result['is_valid']);
        self::assertIsArray($result['suggestions']);
        self::assertIsIterable($result['suggestions']);
        $this->assertContains('建议为检查项 visual_check 设置权重', $result['suggestions']);
    }

    /**
     * 测试检查项判定标准为空的警告
     */
    public function testValidateQualityStandardWithEmptyCriteria(): void
    {
        $standard = new QualityStandard();
        $standard->setName('空判定标准');
        $standard->setProductCategory('测试类别');
        $standard->setPriority(50);
        $standard->setCheckItems([
            'visual_check' => [
                'enabled' => true,
                'weight' => 100,
                'criteria' => [], // 空数组
            ],
        ]);

        $result = $this->service->validateQualityStandard($standard);

        $this->assertTrue($result['is_valid']);
        self::assertIsArray($result['warnings']);
        self::assertIsIterable($result['warnings']);
        $this->assertContains('检查项 visual_check 的判定标准为空', $result['warnings']);
    }

    /**
     * 测试空标准名称的验证错误
     */
    public function testValidateQualityStandardWithEmptyName(): void
    {
        $standard = new QualityStandard();
        $standard->setName('');
        $standard->setProductCategory('测试类别');
        $standard->setPriority(50);
        $standard->setCheckItems([
            'visual_check' => [
                'enabled' => true,
            ],
        ]);

        $result = $this->service->validateQualityStandard($standard);

        $this->assertFalse($result['is_valid']);
        self::assertIsArray($result['validation_errors']);
        self::assertIsIterable($result['validation_errors']);
        $this->assertContains('质检标准名称不能为空', $result['validation_errors']);
    }

    /**
     * 测试空商品类别的验证错误
     */
    public function testValidateQualityStandardWithEmptyProductCategory(): void
    {
        $standard = new QualityStandard();
        $standard->setName('测试标准');
        $standard->setProductCategory('');
        $standard->setPriority(50);
        $standard->setCheckItems([
            'visual_check' => [
                'enabled' => true,
            ],
        ]);

        $result = $this->service->validateQualityStandard($standard);

        $this->assertFalse($result['is_valid']);
        self::assertIsArray($result['validation_errors']);
        self::assertIsIterable($result['validation_errors']);
        $this->assertContains('商品类别不能为空', $result['validation_errors']);
    }

    /**
     * @return array<string, array{priority: int, shouldHaveError: bool}>
     */
    public static function priorityDataProvider(): array
    {
        return [
            '优先级为0（无效）' => [
                'priority' => 0,
                'shouldHaveError' => true,
            ],
            '优先级为1（有效）' => [
                'priority' => 1,
                'shouldHaveError' => false,
            ],
            '优先级为50（有效）' => [
                'priority' => 50,
                'shouldHaveError' => false,
            ],
            '优先级为100（有效）' => [
                'priority' => 100,
                'shouldHaveError' => false,
            ],
            '优先级为101（无效）' => [
                'priority' => 101,
                'shouldHaveError' => true,
            ],
            '优先级为-1（无效）' => [
                'priority' => -1,
                'shouldHaveError' => true,
            ],
        ];
    }

    /**
     * 测试优先级边界验证
     */
    #[DataProvider('priorityDataProvider')]
    public function testValidateQualityStandardWithPriorityBoundaries(int $priority, bool $shouldHaveError): void
    {
        $standard = new QualityStandard();
        $standard->setName('优先级测试');
        $standard->setProductCategory('测试类别');
        $standard->setPriority($priority);
        $standard->setCheckItems([
            'visual_check' => [
                'enabled' => true,
            ],
        ]);

        $result = $this->service->validateQualityStandard($standard);

        if ($shouldHaveError) {
            $this->assertFalse($result['is_valid']);
            self::assertIsArray($result['validation_errors']);
            self::assertIsIterable($result['validation_errors']);
            $this->assertContains('优先级必须在1-100之间', $result['validation_errors']);
        } else {
            // 有效的优先级不应该导致验证失败（可能有其他警告）
            $hasPriorityError = false;
            self::assertIsArray($result['validation_errors']);
            self::assertIsIterable($result['validation_errors']);
            foreach ($result['validation_errors'] as $error) {
                self::assertIsString($error);
                if (str_contains($error, '优先级')) {
                    $hasPriorityError = true;
                    break;
                }
            }
            $this->assertFalse($hasPriorityError, '有效的优先级不应产生错误');
        }
    }

    /**
     * 测试多个验证错误同时存在
     */
    public function testValidateQualityStandardWithMultipleErrors(): void
    {
        $standard = new QualityStandard();
        $standard->setName(''); // 错误1：空名称
        $standard->setProductCategory(''); // 错误2：空类别
        $standard->setPriority(200); // 错误3：优先级超出范围
        $standard->setCheckItems([]); // 错误4：空检查项

        $result = $this->service->validateQualityStandard($standard);

        $this->assertFalse($result['is_valid']);
        self::assertIsArray($result['validation_errors']);
        $this->assertGreaterThanOrEqual(4, count($result['validation_errors']));
        self::assertIsIterable($result['validation_errors']);
        $this->assertContains('质检标准名称不能为空', $result['validation_errors']);
        $this->assertContains('商品类别不能为空', $result['validation_errors']);
        $this->assertContains('优先级必须在1-100之间', $result['validation_errors']);
        $this->assertContains('质检标准必须包含至少一个检查项', $result['validation_errors']);
    }

    /**
     * 测试带验证上下文的验证（验证上下文参数当前未使用，但应接受）
     */
    public function testValidateQualityStandardWithValidationContext(): void
    {
        $standard = new QualityStandard();
        $standard->setName('上下文测试');
        $standard->setProductCategory('测试类别');
        $standard->setPriority(50);
        $standard->setCheckItems([
            'visual_check' => [
                'enabled' => true,
            ],
        ]);

        $validationContext = [
            'strict_mode' => true,
            'additional_rules' => ['some_rule'],
        ];

        $result = $this->service->validateQualityStandard($standard, $validationContext);

        // 即使传入了上下文，基本验证应该正常工作
        $this->assertArrayHasKey('is_valid', $result);
        $this->assertArrayHasKey('validation_errors', $result);
        $this->assertArrayHasKey('warnings', $result);
        $this->assertArrayHasKey('suggestions', $result);
    }

    /**
     * 测试复杂场景：部分检查项有效，部分无效
     */
    public function testValidateQualityStandardWithMixedCheckItems(): void
    {
        $standard = new QualityStandard();
        $standard->setName('混合检查项');
        $standard->setProductCategory('测试类别');
        $standard->setPriority(50);
        $standard->setCheckItems([
            'visual_check' => [
                'enabled' => true,
                'weight' => 30,
                'criteria' => ['完整'],
            ],
            'quantity_check' => 'invalid', // 无效配置
            'function_check' => [
                'weight' => 40,
                // 缺少 enabled
            ],
            'package_check' => [
                'enabled' => true,
                'criteria' => [], // 空判定标准
            ],
        ]);

        $result = $this->service->validateQualityStandard($standard);

        $this->assertFalse($result['is_valid']); // 因为有配置格式错误

        // 验证错误
        self::assertIsArray($result['validation_errors']);
        self::assertIsIterable($result['validation_errors']);
        $this->assertContains('检查项 quantity_check 配置格式错误', $result['validation_errors']);

        // 验证警告
        self::assertIsArray($result['warnings']);
        self::assertIsIterable($result['warnings']);
        $this->assertContains('检查项 function_check 缺少 enabled 配置', $result['warnings']);
        $this->assertContains('检查项 package_check 的判定标准为空', $result['warnings']);
    }

    /**
     * 测试返回结构完整性
     */
    public function testValidateQualityStandardReturnStructure(): void
    {
        $standard = new QualityStandard();
        $standard->setName('结构测试');
        $standard->setProductCategory('测试类别');
        $standard->setPriority(50);
        $standard->setCheckItems([
            'visual_check' => [
                'enabled' => true,
            ],
        ]);

        $result = $this->service->validateQualityStandard($standard);

        // 验证返回的所有键
        $this->assertArrayHasKey('is_valid', $result);
        $this->assertArrayHasKey('validation_errors', $result);
        $this->assertArrayHasKey('warnings', $result);
        $this->assertArrayHasKey('suggestions', $result);

        // 验证值类型
        $this->assertIsBool($result['is_valid']);
        $this->assertIsArray($result['validation_errors']);
        $this->assertIsArray($result['warnings']);
        $this->assertIsArray($result['suggestions']);
    }

    /**
     * 测试服务实例化
     */
    public function testServiceInstantiation(): void
    {
        $this->assertInstanceOf(QualityStandardValidationService::class, $this->service);
    }

    /**
     * 测试检查项配置有criteria但不是数组（不应警告）
     */
    public function testValidateQualityStandardWithNonArrayCriteria(): void
    {
        $standard = new QualityStandard();
        $standard->setName('非数组criteria');
        $standard->setProductCategory('测试类别');
        $standard->setPriority(50);
        $standard->setCheckItems([
            'visual_check' => [
                'enabled' => true,
                'weight' => 100,
                'criteria' => 'some_string', // 不是数组，不会触发空数组警告
            ],
        ]);

        $result = $this->service->validateQualityStandard($standard);

        $this->assertTrue($result['is_valid']);
        // 不应该有关于 criteria 为空的警告
        self::assertIsArray($result['warnings']);
        self::assertIsIterable($result['warnings']);
        foreach ($result['warnings'] as $warning) {
            self::assertIsString($warning);
            $this->assertStringNotContainsString('判定标准为空', $warning);
        }
    }

    /**
     * 测试所有警告和建议类型
     */
    public function testValidateQualityStandardAllWarningsAndSuggestions(): void
    {
        $standard = new QualityStandard();
        $standard->setName('所有警告和建议');
        $standard->setProductCategory('测试类别');
        $standard->setPriority(50);
        $standard->setCheckItems([
            'custom_check' => [
                // 缺少 enabled -> 警告
                // 缺少 weight -> 建议
                'criteria' => [], // 空 criteria -> 警告
            ],
            // 缺少 visual_check -> 警告
            // 缺少 quantity_check -> 警告
        ]);

        $result = $this->service->validateQualityStandard($standard);

        $this->assertTrue($result['is_valid']); // 有检查项，只是警告和建议

        // 验证警告数量
        self::assertIsArray($result['warnings']);
        $this->assertGreaterThanOrEqual(4, count($result['warnings']));

        // 验证建议数量
        self::assertIsArray($result['suggestions']);
        $this->assertGreaterThanOrEqual(1, count($result['suggestions']));
    }

    /**
     * 测试完全符合最佳实践的标准（无错误、无警告、无建议）
     */
    public function testValidateQualityStandardPerfectConfiguration(): void
    {
        $standard = new QualityStandard();
        $standard->setName('完美配置');
        $standard->setProductCategory('测试类别');
        $standard->setPriority(50);
        $standard->setCheckItems([
            'visual_check' => [
                'enabled' => true,
                'weight' => 30,
                'criteria' => ['无瑕疵'],
            ],
            'quantity_check' => [
                'enabled' => true,
                'weight' => 40,
                'criteria' => ['数量准确'],
            ],
            'additional_check' => [
                'enabled' => true,
                'weight' => 30,
                'criteria' => ['其他检查'],
            ],
        ]);

        $result = $this->service->validateQualityStandard($standard);

        $this->assertTrue($result['is_valid']);
        $this->assertEmpty($result['validation_errors']);
        // 应该没有关于必需检查项的警告
        $visualCheckWarning = false;
        $quantityCheckWarning = false;
        self::assertIsArray($result['warnings']);
        self::assertIsIterable($result['warnings']);
        foreach ($result['warnings'] as $warning) {
            self::assertIsString($warning);
            if (str_contains($warning, 'visual_check')) {
                $visualCheckWarning = true;
            }
            if (str_contains($warning, 'quantity_check')) {
                $quantityCheckWarning = true;
            }
        }
        $this->assertFalse($visualCheckWarning);
        $this->assertFalse($quantityCheckWarning);
    }

    /**
     * 测试边界情况：检查项只有一个且是必需的
     */
    public function testValidateQualityStandardWithSingleRequiredCheckItem(): void
    {
        $standard = new QualityStandard();
        $standard->setName('单一必需检查项');
        $standard->setProductCategory('测试类别');
        $standard->setPriority(50);
        $standard->setCheckItems([
            'visual_check' => [
                'enabled' => true,
                'weight' => 100,
                'criteria' => ['检查'],
            ],
        ]);

        $result = $this->service->validateQualityStandard($standard);

        $this->assertTrue($result['is_valid']);
        // 应该有关于缺少 quantity_check 的警告
        self::assertIsArray($result['warnings']);
        self::assertIsIterable($result['warnings']);
        $this->assertContains('建议添加 quantity_check 检查项', $result['warnings']);
    }
}
