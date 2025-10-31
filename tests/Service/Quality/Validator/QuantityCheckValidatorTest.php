<?php

declare(strict_types=1);

namespace Tourze\WarehouseOperationBundle\Tests\Service\Quality\Validator;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\WarehouseOperationBundle\Service\Quality\Validator\QuantityCheckValidator;

/**
 * QuantityCheckValidator 单元测试
 *
 * 测试数量检查验证器的功能，包括数量比较、容差检查、差异计算等核心验证逻辑。
 * 验证验证器的正确性、边界条件处理和容差逻辑。
 * @internal
 */
#[CoversClass(QuantityCheckValidator::class)]
#[RunTestsInSeparateProcesses]
class QuantityCheckValidatorTest extends AbstractIntegrationTestCase
{
    private QuantityCheckValidator $validator;

    protected function onSetUp(): void
    {
        $this->validator = parent::getService(QuantityCheckValidator::class);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Quality\Validator\QuantityCheckValidator::getSupportedCheckType
     */
    public function testGetSupportedCheckType(): void
    {
        $result = $this->validator->getSupportedCheckType();

        $this->assertEquals('quantity_check', $result);
        // getSupportedCheckType返回值已确定为字符串类型，无需重复检查
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Quality\Validator\QuantityCheckValidator::validate
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
        $this->assertEquals('数量检查数据格式错误', $defect['message']);
        $this->assertTrue($defect['critical']);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Quality\Validator\QuantityCheckValidator::validate
     */
    public function testValidateWithExactMatch(): void
    {
        $checkValue = [
            'expected' => 100,
            'actual' => 100,
        ];
        $criteria = [];
        $strictMode = false;

        $result = $this->validator->validate($checkValue, $criteria, $strictMode);

        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['defects']);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Quality\Validator\QuantityCheckValidator::validate
     */
    public function testValidateWithDifferenceWithinDefaultTolerance(): void
    {
        $checkValue = [
            'expected' => 100,
            'actual' => 101, // 差异1，在默认容差1内
        ];
        $criteria = []; // 使用默认容差
        $strictMode = false;

        $result = $this->validator->validate($checkValue, $criteria, $strictMode);

        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['defects']);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Quality\Validator\QuantityCheckValidator::validate
     */
    public function testValidateWithDifferenceExceedingTolerance(): void
    {
        $checkValue = [
            'expected' => 100,
            'actual' => 95, // 差异5，超过默认容差1
        ];
        $criteria = [];
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
        $this->assertEquals('quantity_mismatch', $defect['type']);
        // message字段已确定为字符串类型，无需重复检查
        self::assertIsString($defect['message']);
        $this->assertStringContainsString('数量差异 5 超过容差 1', $defect['message']);
        $this->assertEquals(100, $defect['expected']);
        $this->assertEquals(95, $defect['actual']);
        $this->assertEquals(5, $defect['difference']);
        $this->assertTrue($defect['critical']); // 差异5 > 容差1*2
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Quality\Validator\QuantityCheckValidator::validate
     */
    public function testValidateWithCustomTolerance(): void
    {
        $checkValue = [
            'expected' => 100,
            'actual' => 95, // 差异5
        ];
        $criteria = [
            'tolerance' => 10, // 自定义容差10
        ];
        $strictMode = false;

        $result = $this->validator->validate($checkValue, $criteria, $strictMode);

        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['defects']);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Quality\Validator\QuantityCheckValidator::validate
     */
    public function testValidateWithStrictMode(): void
    {
        $checkValue = [
            'expected' => 100,
            'actual' => 99, // 差异1
        ];
        $criteria = [
            'tolerance' => 5,           // 非严格模式容差5
            'strict_tolerance' => 0,    // 严格模式容差0
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
        $this->assertEquals('quantity_mismatch', $defect['type']);
        // message字段已确定为字符串类型，无需重复检查
        self::assertIsString($defect['message']);
        $this->assertStringContainsString('数量差异 1 超过容差 0', $defect['message']);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Quality\Validator\QuantityCheckValidator::validate
     */
    public function testValidateWithStrictModeWithinTolerance(): void
    {
        $checkValue = [
            'expected' => 100,
            'actual' => 99, // 差异1
        ];
        $criteria = [
            'tolerance' => 5,           // 非严格模式容差5
            'strict_tolerance' => 2,    // 严格模式容差2
        ];
        $strictMode = true;

        $result = $this->validator->validate($checkValue, $criteria, $strictMode);

        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['defects']);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Quality\Validator\QuantityCheckValidator::validate
     */
    public function testValidateWithMissingExpectedValue(): void
    {
        $checkValue = [
            'actual' => 50,
            // 缺少 expected
        ];
        $criteria = [];
        $strictMode = false;

        $result = $this->validator->validate($checkValue, $criteria, $strictMode);

        // 缺少expected时，默认为0，差异为50，超过默认容差1
        $this->assertIsArray($result);
        $this->assertFalse($result['valid']);
        $this->assertIsArray($result['defects']);
        $this->assertCount(1, $result['defects']);

        $this->assertIsArray($result['defects'][0]);
        $defect = $result['defects'][0];
        $this->assertEquals(0, $defect['expected']); // 默认值
        $this->assertEquals(50, $defect['actual']);
        $this->assertEquals(50, $defect['difference']);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Quality\Validator\QuantityCheckValidator::validate
     */
    public function testValidateWithMissingActualValue(): void
    {
        $checkValue = [
            'expected' => 50,
            // 缺少 actual
        ];
        $criteria = [];
        $strictMode = false;

        $result = $this->validator->validate($checkValue, $criteria, $strictMode);

        // 缺少actual时，默认为0，差异为50
        $this->assertFalse($result['valid']);
        self::assertIsArray($result['defects']);
        $this->assertCount(1, $result['defects']);

        self::assertIsArray($result['defects'][0]);
        /** @var array<string, mixed> $defect */
        $defect = $result['defects'][0];
        $this->assertEquals(50, $defect['expected']);
        $this->assertEquals(0, $defect['actual']); // 默认值
        $this->assertEquals(50, $defect['difference']);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Quality\Validator\QuantityCheckValidator::validate
     */
    public function testValidateWithBothValuesMissing(): void
    {
        $checkValue = []; // 空数组
        $criteria = [];
        $strictMode = false;

        $result = $this->validator->validate($checkValue, $criteria, $strictMode);

        // 两个值都为0，无差异
        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['defects']);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Quality\Validator\QuantityCheckValidator::validate
     */
    public function testValidateWithCriticalDefectDetection(): void
    {
        $checkValue = [
            'expected' => 100,
            'actual' => 90, // 差异10
        ];
        $criteria = [
            'tolerance' => 5, // 容差5
        ];
        $strictMode = false;

        $result = $this->validator->validate($checkValue, $criteria, $strictMode);

        $this->assertFalse($result['valid']);
        self::assertIsArray($result['defects']);
        $this->assertCount(1, $result['defects']);

        self::assertIsArray($result['defects'][0]);
        /** @var array<string, mixed> $defect */
        $defect = $result['defects'][0];
        // 差异10 > 容差5*2，应该标记为critical
        $this->assertTrue($defect['critical']);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Quality\Validator\QuantityCheckValidator::validate
     */
    public function testValidateWithNonCriticalDefect(): void
    {
        $checkValue = [
            'expected' => 100,
            'actual' => 92, // 差异8
        ];
        $criteria = [
            'tolerance' => 5, // 容差5
        ];
        $strictMode = false;

        $result = $this->validator->validate($checkValue, $criteria, $strictMode);

        $this->assertFalse($result['valid']);
        self::assertIsArray($result['defects']);
        $this->assertCount(1, $result['defects']);

        self::assertIsArray($result['defects'][0]);
        /** @var array<string, mixed> $defect */
        $defect = $result['defects'][0];
        // 差异8 <= 容差5*2，不应该标记为critical
        $this->assertFalse($defect['critical']);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Quality\Validator\QuantityCheckValidator::validate
     */
    public function testValidateWithNegativeValues(): void
    {
        $checkValue = [
            'expected' => -10,
            'actual' => -12, // 差异2
        ];
        $criteria = [
            'tolerance' => 3,
        ];
        $strictMode = false;

        $result = $this->validator->validate($checkValue, $criteria, $strictMode);

        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['defects']);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Quality\Validator\QuantityCheckValidator::validate
     */
    public function testValidateWithFloatingPointValues(): void
    {
        $checkValue = [
            'expected' => 10.5,
            'actual' => 10.8, // 差异0.3
        ];
        $criteria = [
            'tolerance' => 0.5,
        ];
        $strictMode = false;

        $result = $this->validator->validate($checkValue, $criteria, $strictMode);

        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['defects']);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Quality\Validator\QuantityCheckValidator::validate
     */
    public function testValidateWithFloatingPointPrecisionIssues(): void
    {
        $checkValue = [
            'expected' => 0.1 + 0.2, // 浮点精度问题
            'actual' => 0.3,
        ];
        $criteria = [
            'tolerance' => 0.0001, // 很小的容差
        ];
        $strictMode = false;

        $result = $this->validator->validate($checkValue, $criteria, $strictMode);

        // 由于浮点精度问题，0.1 + 0.2 != 0.3
        // 但差异应该很小，在容差范围内
        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['defects']);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Quality\Validator\QuantityCheckValidator::validate
     */
    public function testValidateWithZeroToleranceStrictMode(): void
    {
        $checkValue = [
            'expected' => 100,
            'actual' => 100,
        ];
        $criteria = [
            'strict_tolerance' => 0, // 零容差
        ];
        $strictMode = true;

        $result = $this->validator->validate($checkValue, $criteria, $strictMode);

        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['defects']);
    }

    public function testValidatorBasicFunctionality(): void
    {
        // 验证验证器可以正确实例化
        $this->assertInstanceOf(QuantityCheckValidator::class, $this->validator);

        // 验证基本功能工作正常
        $result = $this->validator->validate(['expected' => 10, 'actual' => 10], [], false);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('valid', $result);
        $this->assertArrayHasKey('defects', $result);
    }

    /**
     * 测试边界情况：零值处理
     */
    public function testValidateWithZeroValues(): void
    {
        $checkValue = [
            'expected' => 0,
            'actual' => 0,
        ];
        $criteria = [];
        $strictMode = false;

        $result = $this->validator->validate($checkValue, $criteria, $strictMode);

        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['defects']);
    }

    /**
     * 测试大数值处理
     */
    public function testValidateWithLargeNumbers(): void
    {
        $checkValue = [
            'expected' => 1000000,
            'actual' => 999990, // 差异10
        ];
        $criteria = [
            'tolerance' => 20,
        ];
        $strictMode = false;

        $result = $this->validator->validate($checkValue, $criteria, $strictMode);

        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['defects']);
    }

    /**
     * 测试严格模式下的默认容差
     */
    public function testValidateStrictModeWithDefaultTolerance(): void
    {
        $checkValue = [
            'expected' => 100,
            'actual' => 99, // 差异1
        ];
        $criteria = []; // 没有指定严格容差
        $strictMode = true;

        $result = $this->validator->validate($checkValue, $criteria, $strictMode);

        // 严格模式下没有指定strict_tolerance时，应该使用默认的0
        $this->assertFalse($result['valid']);
        self::assertIsArray($result['defects']);
        $this->assertCount(1, $result['defects']);

        self::assertIsArray($result['defects'][0]);
        /** @var array<string, mixed> $defect */
        $defect = $result['defects'][0];
        self::assertIsString($defect['message']);
        $this->assertStringContainsString('超过容差 0', $defect['message']);
    }

    /**
     * 测试非严格模式下忽略严格容差
     */
    public function testValidateNonStrictModeIgnoresStrictTolerance(): void
    {
        $checkValue = [
            'expected' => 100,
            'actual' => 95, // 差异5
        ];
        $criteria = [
            'tolerance' => 10,         // 非严格容差10
            'strict_tolerance' => 2,   // 严格容差2（应该被忽略）
        ];
        $strictMode = false;

        $result = $this->validator->validate($checkValue, $criteria, $strictMode);

        // 非严格模式下应该使用tolerance=10，所以差异5应该通过
        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['defects']);
    }
}
