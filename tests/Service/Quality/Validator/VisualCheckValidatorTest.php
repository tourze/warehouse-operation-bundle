<?php

declare(strict_types=1);

namespace Tourze\WarehouseOperationBundle\Tests\Service\Quality\Validator;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\WarehouseOperationBundle\Service\Quality\Validator\VisualCheckValidator;

/**
 * VisualCheckValidator 单元测试
 *
 * 测试视觉检查验证器的功能，包括外观检查、损坏程度判断、条件验证等核心验证逻辑。
 * 验证验证器的正确性、损坏等级比较和边界条件处理。
 * @internal
 */
#[CoversClass(VisualCheckValidator::class)]
#[RunTestsInSeparateProcesses]
class VisualCheckValidatorTest extends AbstractIntegrationTestCase
{
    private VisualCheckValidator $validator;

    protected function onSetUp(): void
    {
        $this->validator = parent::getService(VisualCheckValidator::class);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Quality\Validator\VisualCheckValidator::getSupportedCheckType
     */
    public function testGetSupportedCheckType(): void
    {
        $result = $this->validator->getSupportedCheckType();

        $this->assertEquals('visual_check', $result);
        // getSupportedCheckType返回值已确定为字符串类型，无需重复检查
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Quality\Validator\VisualCheckValidator::validate
     */
    public function testValidateWithInvalidDataFormat(): void
    {
        $checkValue = 'not_an_array';
        $criteria = [];
        $strictMode = false;

        $result = $this->validator->validate($checkValue, $criteria, $strictMode);

        $this->assertIsArray($result);
        $this->assertFalse($result['valid']);
        $this->assertIsArray($result['defects']);
        $this->assertCount(1, $result['defects']);

        $this->assertIsArray($result['defects'][0]);
        $defect = $result['defects'][0];
        $this->assertEquals('invalid_data', $defect['type']);
        $this->assertEquals('视觉检查数据格式错误', $defect['message']);
        $this->assertTrue($defect['critical']);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Quality\Validator\VisualCheckValidator::validate
     */
    public function testValidateWithValidCondition(): void
    {
        $checkValue = [
            'condition' => 'good',
        ];
        $criteria = [
            'allowed_conditions' => ['perfect', 'good', 'damaged'],
        ];
        $strictMode = false;

        $result = $this->validator->validate($checkValue, $criteria, $strictMode);

        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['defects']);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Quality\Validator\VisualCheckValidator::validate
     */
    public function testValidateWithInvalidCondition(): void
    {
        $checkValue = [
            'condition' => 'terrible',
        ];
        $criteria = [
            'allowed_conditions' => ['perfect', 'good', 'damaged'],
        ];
        $strictMode = false;

        $result = $this->validator->validate($checkValue, $criteria, $strictMode);

        $this->assertIsArray($result);
        $this->assertFalse($result['valid']);
        $this->assertIsArray($result['defects']);
        $this->assertCount(1, $result['defects']);

        $this->assertIsArray($result['defects'][0]);
        $defect = $result['defects'][0];
        self::assertIsArray($defect);
        /** @var array<string, mixed> $defect */
        $this->assertEquals('invalid_condition', $defect['type']);
        // message字段已确定为字符串类型，无需重复检查
        self::assertIsString($defect['message']);
        $this->assertStringContainsString("状况 'terrible' 不在允许范围内", $defect['message']);
        $this->assertTrue($defect['critical']);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Quality\Validator\VisualCheckValidator::validate
     */
    public function testValidateWithDefaultAllowedConditions(): void
    {
        $checkValue = [
            'condition' => 'good',
        ];
        $criteria = []; // 使用默认允许条件
        $strictMode = false;

        $result = $this->validator->validate($checkValue, $criteria, $strictMode);

        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['defects']);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Quality\Validator\VisualCheckValidator::validate
     */
    public function testValidateWithNoDamage(): void
    {
        $checkValue = [
            'condition' => 'perfect',
            'damage' => false,
        ];
        $criteria = [];
        $strictMode = false;

        $result = $this->validator->validate($checkValue, $criteria, $strictMode);

        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['defects']);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Quality\Validator\VisualCheckValidator::validate
     */
    public function testValidateWithAcceptableDamage(): void
    {
        $checkValue = [
            'condition' => 'damaged',
            'damage' => true,
            'damage_level' => 'minor',
        ];
        $criteria = [
            'max_damage' => 'moderate', // 允许最多中等损坏
        ];
        $strictMode = false;

        $result = $this->validator->validate($checkValue, $criteria, $strictMode);

        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['defects']);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Quality\Validator\VisualCheckValidator::validate
     */
    public function testValidateWithExcessiveDamage(): void
    {
        $checkValue = [
            'condition' => 'damaged',
            'damage' => true,
            'damage_level' => 'severe',
        ];
        $criteria = [
            'max_damage' => 'moderate', // 只允许最多中等损坏
        ];
        $strictMode = false;

        $result = $this->validator->validate($checkValue, $criteria, $strictMode);

        $this->assertIsArray($result);
        $this->assertFalse($result['valid']);
        $this->assertIsArray($result['defects']);
        $this->assertCount(1, $result['defects']);

        $this->assertIsArray($result['defects'][0]);
        $defect = $result['defects'][0];
        self::assertIsArray($defect);
        /** @var array<string, mixed> $defect */
        $this->assertEquals('excessive_damage', $defect['type']);
        // message字段已确定为字符串类型，无需重复检查
        self::assertIsString($defect['message']);
        $this->assertStringContainsString("损坏程度 'severe' 超过允许的 'moderate'", $defect['message']);
        $this->assertTrue($defect['critical']);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Quality\Validator\VisualCheckValidator::validate
     */
    public function testValidateWithStrictModeNoDamageAllowed(): void
    {
        $checkValue = [
            'condition' => 'damaged',
            'damage' => true,
            'damage_level' => 'minor',
        ];
        $criteria = [
            'max_damage' => 'minor',           // 非严格模式允许轻微损坏
            'max_damage_strict' => 'none',     // 严格模式不允许任何损坏
        ];
        $strictMode = true;

        $result = $this->validator->validate($checkValue, $criteria, $strictMode);

        $this->assertIsArray($result);
        $this->assertFalse($result['valid']);
        $this->assertIsArray($result['defects']);
        $this->assertCount(1, $result['defects']);

        $this->assertIsArray($result['defects'][0]);
        $defect = $result['defects'][0];
        self::assertIsArray($defect);
        /** @var array<string, mixed> $defect */
        $this->assertEquals('excessive_damage', $defect['type']);
        // message字段已确定为字符串类型，无需重复检查
        self::assertIsString($defect['message']);
        $this->assertStringContainsString("损坏程度 'minor' 超过允许的 'none'", $defect['message']);
        $this->assertTrue($defect['critical']);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Quality\Validator\VisualCheckValidator::validate
     */
    public function testValidateWithStrictModeAcceptableDamage(): void
    {
        $checkValue = [
            'condition' => 'damaged',
            'damage' => true,
            'damage_level' => 'minor',
        ];
        $criteria = [
            'max_damage' => 'moderate',        // 非严格模式允许中等损坏
            'max_damage_strict' => 'minor',    // 严格模式允许轻微损坏
        ];
        $strictMode = true;

        $result = $this->validator->validate($checkValue, $criteria, $strictMode);

        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['defects']);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Quality\Validator\VisualCheckValidator::validate
     */
    public function testValidateWithUnknownDamageLevel(): void
    {
        $checkValue = [
            'condition' => 'damaged',
            'damage' => true,
            'damage_level' => 'unknown_level',
        ];
        $criteria = [
            'max_damage' => 'moderate',
        ];
        $strictMode = false;

        $result = $this->validator->validate($checkValue, $criteria, $strictMode);

        // 未知损坏等级应该被认为是0（none），所以应该通过验证
        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['defects']);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Quality\Validator\VisualCheckValidator::validate
     */
    public function testValidateWithMissingDamageLevel(): void
    {
        $checkValue = [
            'condition' => 'damaged',
            'damage' => true,
            // 缺少 damage_level
        ];
        $criteria = [
            'max_damage' => 'moderate',
        ];
        $strictMode = false;

        $result = $this->validator->validate($checkValue, $criteria, $strictMode);

        // 缺少damage_level时默认为'unknown'，被认为是0，所以通过验证
        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['defects']);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Quality\Validator\VisualCheckValidator::validate
     */
    public function testValidateWithMultipleDefects(): void
    {
        $checkValue = [
            'condition' => 'terrible',        // 无效条件
            'damage' => true,
            'damage_level' => 'severe',       // 过度损坏
        ];
        $criteria = [
            'allowed_conditions' => ['perfect', 'good'],
            'max_damage' => 'minor',
        ];
        $strictMode = false;

        $result = $this->validator->validate($checkValue, $criteria, $strictMode);

        $this->assertIsArray($result);
        $this->assertFalse($result['valid']);
        $this->assertIsArray($result['defects']);
        $this->assertCount(2, $result['defects']);

        $defectTypes = array_column($result['defects'], 'type');
        $this->assertIsArray($defectTypes);
        $this->assertContainsEquals('invalid_condition', $defectTypes);
        $this->assertContainsEquals('excessive_damage', $defectTypes);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Quality\Validator\VisualCheckValidator::validate
     */
    public function testValidateWithEmptyCondition(): void
    {
        $checkValue = [
            'condition' => '',
        ];
        $criteria = [
            'allowed_conditions' => ['perfect', 'good', 'damaged'],
        ];
        $strictMode = false;

        $result = $this->validator->validate($checkValue, $criteria, $strictMode);

        $this->assertIsArray($result);
        $this->assertFalse($result['valid']);
        $this->assertIsArray($result['defects']);
        $this->assertCount(1, $result['defects']);
        $this->assertIsArray($result['defects'][0]);
        $this->assertEquals('invalid_condition', $result['defects'][0]['type']);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Quality\Validator\VisualCheckValidator::validate
     */
    public function testValidateWithMissingCondition(): void
    {
        $checkValue = [
            // 缺少 condition
        ];
        $criteria = [
            'allowed_conditions' => ['perfect', 'good', 'damaged'],
        ];
        $strictMode = false;

        $result = $this->validator->validate($checkValue, $criteria, $strictMode);

        $this->assertFalse($result['valid']);
        self::assertIsArray($result['defects']);
        $this->assertCount(1, $result['defects']);
        self::assertIsArray($result['defects'][0]);
        /** @var array<string, mixed> $defect0 */
        $defect0 = $result['defects'][0];
        $this->assertEquals('invalid_condition', $defect0['type']);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Quality\Validator\VisualCheckValidator::validate
     */
    public function testValidateWithDefaultMaxDamage(): void
    {
        $checkValue = [
            'condition' => 'damaged',
            'damage' => true,
            'damage_level' => 'minor',
        ];
        $criteria = []; // 使用默认最大损坏程度
        $strictMode = false;

        $result = $this->validator->validate($checkValue, $criteria, $strictMode);

        // 默认max_damage是'minor'，所以'minor'应该通过
        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['defects']);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Quality\Validator\VisualCheckValidator::validate
     */
    public function testValidateWithDefaultStrictMaxDamage(): void
    {
        $checkValue = [
            'condition' => 'damaged',
            'damage' => true,
            'damage_level' => 'minor',
        ];
        $criteria = []; // 使用默认严格最大损坏程度
        $strictMode = true;

        $result = $this->validator->validate($checkValue, $criteria, $strictMode);

        // 默认max_damage_strict是'none'，所以任何损坏都不应该通过
        $this->assertFalse($result['valid']);
        self::assertIsArray($result['defects']);
        $this->assertCount(1, $result['defects']);
        self::assertIsArray($result['defects'][0]);
        /** @var array<string, mixed> $defect0 */
        $defect0 = $result['defects'][0];
        $this->assertEquals('excessive_damage', $defect0['type']);
    }

    public function testValidatorBasicFunctionality(): void
    {
        // 验证验证器可以正确实例化
        $this->assertInstanceOf(VisualCheckValidator::class, $this->validator);

        // 验证基本功能工作正常
        $result = $this->validator->validate(['condition' => 'good'], [], false);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('valid', $result);
        $this->assertArrayHasKey('defects', $result);
    }

    /**
     * 测试损坏等级比较逻辑
     */
    public function testDamageLevelComparison(): void
    {
        $testCases = [
            // [实际等级, 最大允许等级, 应该通过]
            ['none', 'none', true],
            ['none', 'minor', true],
            ['none', 'moderate', true],
            ['none', 'major', true],
            ['none', 'severe', true],

            ['minor', 'none', false],
            ['minor', 'minor', true],
            ['minor', 'moderate', true],
            ['minor', 'major', true],
            ['minor', 'severe', true],

            ['moderate', 'none', false],
            ['moderate', 'minor', false],
            ['moderate', 'moderate', true],
            ['moderate', 'major', true],
            ['moderate', 'severe', true],

            ['major', 'none', false],
            ['major', 'minor', false],
            ['major', 'moderate', false],
            ['major', 'major', true],
            ['major', 'severe', true],

            ['severe', 'none', false],
            ['severe', 'minor', false],
            ['severe', 'moderate', false],
            ['severe', 'major', false],
            ['severe', 'severe', true],
        ];

        foreach ($testCases as [$actualLevel, $maxLevel, $shouldPass]) {
            $checkValue = [
                'condition' => 'damaged',
                'damage' => true,
                'damage_level' => $actualLevel,
            ];
            $criteria = [
                'max_damage' => $maxLevel,
            ];

            $result = $this->validator->validate($checkValue, $criteria, false);

            if ($shouldPass) {
                $this->assertTrue($result['valid'], "损坏等级 '{$actualLevel}' 应该在最大允许 '{$maxLevel}' 范围内");
            } else {
                $this->assertFalse($result['valid'], "损坏等级 '{$actualLevel}' 不应该在最大允许 '{$maxLevel}' 范围内");
            }
        }
    }

    /**
     * 测试边界情况：空数组
     */
    public function testValidateWithEmptyArray(): void
    {
        $checkValue = [];
        $criteria = [];
        $strictMode = false;

        $result = $this->validator->validate($checkValue, $criteria, $strictMode);

        // 空条件应该失败
        $this->assertFalse($result['valid']);
        self::assertIsArray($result['defects']);
        $this->assertCount(1, $result['defects']);
        self::assertIsArray($result['defects'][0]);
        /** @var array<string, mixed> $defect0 */
        $defect0 = $result['defects'][0];
        $this->assertEquals('invalid_condition', $defect0['type']);
    }

    /**
     * 测试大小写敏感性
     */
    public function testValidateWithCaseSensitiveConditions(): void
    {
        $checkValue = [
            'condition' => 'Good', // 大写G
        ];
        $criteria = [
            'allowed_conditions' => ['perfect', 'good', 'damaged'],
        ];
        $strictMode = false;

        $result = $this->validator->validate($checkValue, $criteria, $strictMode);

        // 条件检查是大小写敏感的
        $this->assertFalse($result['valid']);
        self::assertIsArray($result['defects']);
        $this->assertCount(1, $result['defects']);
        self::assertIsArray($result['defects'][0]);
        /** @var array<string, mixed> $defect0 */
        $defect0 = $result['defects'][0];
        $this->assertEquals('invalid_condition', $defect0['type']);
    }

    /**
     * 测试特殊损坏等级
     */
    public function testValidateWithCustomDamageLevels(): void
    {
        $checkValue = [
            'condition' => 'damaged',
            'damage' => true,
            'damage_level' => 'custom_level', // 自定义等级
        ];
        $criteria = [
            'max_damage' => 'minor',
        ];
        $strictMode = false;

        $result = $this->validator->validate($checkValue, $criteria, $strictMode);

        // 未知的损坏等级被当作0（none），所以应该通过
        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['defects']);
    }
}
