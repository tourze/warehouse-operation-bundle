<?php

declare(strict_types=1);

namespace Tourze\WarehouseOperationBundle\Tests\Service\Quality\Validator;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\WarehouseOperationBundle\Service\Quality\Validator\GenericCheckValidator;

/**
 * GenericCheckValidator 单元测试
 *
 * 测试通用检查验证器的功能，包括基本验证、值匹配、空值检查等核心验证逻辑。
 * 验证验证器的正确性和边界条件处理。
 * @internal
 */
#[CoversClass(GenericCheckValidator::class)]
#[RunTestsInSeparateProcesses]
class GenericCheckValidatorTest extends AbstractIntegrationTestCase
{
    private GenericCheckValidator $validator;

    protected function onSetUp(): void
    {
        $this->validator = parent::getService(GenericCheckValidator::class);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Quality\Validator\GenericCheckValidator::getSupportedCheckType
     */
    public function testGetSupportedCheckType(): void
    {
        $result = $this->validator->getSupportedCheckType();

        $this->assertEquals('generic_check', $result);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Quality\Validator\GenericCheckValidator::validate
     */
    public function testValidateWithValidNonEmptyValue(): void
    {
        $checkValue = 'valid_value';
        $criteria = [];
        $strictMode = false;

        $result = $this->validator->validate($checkValue, $criteria, $strictMode);

        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['defects']);
        $this->assertIsArray($result['defects']);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Quality\Validator\GenericCheckValidator::validate
     */
    public function testValidateWithRequiredEmptyValue(): void
    {
        $checkValue = '';
        $criteria = ['required' => true];
        $strictMode = false;

        $result = $this->validator->validate($checkValue, $criteria, $strictMode);

        $this->assertFalse($result['valid']);
        self::assertIsArray($result['defects']);
        /** @var array<int, mixed> $defects */
        $defects = $result['defects'];
        $this->assertCount(1, $defects);

        $defect = $defects[0];
        self::assertIsArray($defect);
        /** @var array<string, mixed> $defect */
        $this->assertEquals('missing_value', $defect['type']);
        $this->assertEquals('必需的检查项为空', $defect['message']);
        $this->assertFalse($defect['critical']); // strictMode = false
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Quality\Validator\GenericCheckValidator::validate
     */
    public function testValidateWithRequiredEmptyValueInStrictMode(): void
    {
        $checkValue = null;
        $criteria = ['required' => true];
        $strictMode = true;

        $result = $this->validator->validate($checkValue, $criteria, $strictMode);

        $this->assertFalse($result['valid']);
        self::assertIsArray($result['defects']);
        /** @var array<int, mixed> $defects */
        $defects = $result['defects'];
        $this->assertCount(1, $defects);

        $defect = $defects[0];
        self::assertIsArray($defect);
        /** @var array<string, mixed> $defect */
        $this->assertEquals('missing_value', $defect['type']);
        $this->assertTrue($defect['critical']); // strictMode = true
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Quality\Validator\GenericCheckValidator::validate
     */
    public function testValidateWithNonRequiredEmptyValue(): void
    {
        $checkValue = '';
        $criteria = ['required' => false];
        $strictMode = true;

        $result = $this->validator->validate($checkValue, $criteria, $strictMode);

        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['defects']);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Quality\Validator\GenericCheckValidator::validate
     */
    public function testValidateWithMatchingExpectedValue(): void
    {
        $checkValue = 'expected_value';
        $criteria = ['expected_value' => 'expected_value'];
        $strictMode = false;

        $result = $this->validator->validate($checkValue, $criteria, $strictMode);

        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['defects']);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Quality\Validator\GenericCheckValidator::validate
     */
    public function testValidateWithNonMatchingExpectedValue(): void
    {
        $checkValue = 'actual_value';
        $criteria = ['expected_value' => 'expected_value'];
        $strictMode = false;

        $result = $this->validator->validate($checkValue, $criteria, $strictMode);

        $this->assertFalse($result['valid']);
        self::assertIsArray($result['defects']);
        /** @var array<int, mixed> $defects */
        $defects = $result['defects'];
        $this->assertCount(1, $defects);

        $defect = $defects[0];
        self::assertIsArray($defect);
        /** @var array<string, mixed> $defect */
        $this->assertEquals('value_mismatch', $defect['type']);
        $this->assertEquals('值不匹配预期', $defect['message']);
        $this->assertEquals('expected_value', $defect['expected']);
        $this->assertEquals('actual_value', $defect['actual']);
        $this->assertFalse($defect['critical']); // strictMode = false
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Quality\Validator\GenericCheckValidator::validate
     */
    public function testValidateWithNonMatchingExpectedValueInStrictMode(): void
    {
        $checkValue = 'wrong_value';
        $criteria = ['expected_value' => 'correct_value'];
        $strictMode = true;

        $result = $this->validator->validate($checkValue, $criteria, $strictMode);

        $this->assertFalse($result['valid']);
        self::assertIsArray($result['defects']);
        /** @var array<int, mixed> $defects */
        $defects = $result['defects'];
        $this->assertCount(1, $defects);

        $defect = $defects[0];
        self::assertIsArray($defect);
        /** @var array<string, mixed> $defect */
        $this->assertEquals('value_mismatch', $defect['type']);
        $this->assertTrue($defect['critical']); // strictMode = true
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Quality\Validator\GenericCheckValidator::validate
     */
    public function testValidateWithMultipleDefects(): void
    {
        $checkValue = ''; // 空值且不匹配预期
        $criteria = [
            'required' => true,
            'expected_value' => 'expected_value',
        ];
        $strictMode = true;

        $result = $this->validator->validate($checkValue, $criteria, $strictMode);

        $this->assertFalse($result['valid']);
        self::assertIsArray($result['defects']);
        /** @var array<int, mixed> $defects */
        $defects = $result['defects'];
        $this->assertCount(2, $defects);

        // 验证包含缺失值缺陷
        $missingDefect = null;
        $mismatchDefect = null;
        self::assertIsIterable($defects);
        foreach ($defects as $defect) {
            self::assertIsArray($defect);
            /** @var array<string, mixed> $defect */
            if ('missing_value' === $defect['type']) {
                $missingDefect = $defect;
            } elseif ('value_mismatch' === $defect['type']) {
                $mismatchDefect = $defect;
            }
        }

        $this->assertNotNull($missingDefect);
        $this->assertNotNull($mismatchDefect);
        self::assertIsArray($missingDefect);
        self::assertIsArray($mismatchDefect);
        /** @var array<string, mixed> $missingDefect */
        /** @var array<string, mixed> $mismatchDefect */
        $this->assertTrue($missingDefect['critical']);
        $this->assertTrue($mismatchDefect['critical']);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Quality\Validator\GenericCheckValidator::validate
     */
    public function testValidateWithEmptyCriteria(): void
    {
        $checkValue = 'any_value';
        $criteria = [];
        $strictMode = true;

        $result = $this->validator->validate($checkValue, $criteria, $strictMode);

        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['defects']);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Quality\Validator\GenericCheckValidator::validate
     */
    public function testValidateWithDifferentDataTypes(): void
    {
        $testCases = [
            'string' => 'test_string',
            'integer' => 123,
            'float' => 45.67,
            'boolean_true' => true,
            'boolean_false' => false,
            'array' => ['key' => 'value'],
            'null' => null,
            'empty_string' => '',
            'empty_array' => [],
        ];

        foreach ($testCases as $type => $value) {
            $result = $this->validator->validate($value, [], false);

            // 根据测试用例类型判断是否为"空"值（与业务逻辑中的empty检查保持一致）
            $emptyTypes = ['null', 'boolean_false'];
            $isEmptyValue = in_array($type, $emptyTypes, true) || (is_string($value) && '' === $value) || (is_array($value) && [] === $value);

            if ($isEmptyValue) {
                // null, '', false, [] 被认为是 empty，但在非required模式下仍然有效
                $this->assertTrue($result['valid'], "Type {$type} should be valid when not required");
            } else {
                $this->assertTrue($result['valid'], "Type {$type} should be valid");
            }
        }
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Quality\Validator\GenericCheckValidator::validate
     */
    public function testValidateWithNumericExpectedValue(): void
    {
        $checkValue = 100;
        $criteria = ['expected_value' => 100];
        $strictMode = false;

        $result = $this->validator->validate($checkValue, $criteria, $strictMode);

        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['defects']);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Quality\Validator\GenericCheckValidator::validate
     */
    public function testValidateWithArrayExpectedValue(): void
    {
        $checkValue = ['a', 'b', 'c'];
        $criteria = ['expected_value' => ['a', 'b', 'c']];
        $strictMode = false;

        $result = $this->validator->validate($checkValue, $criteria, $strictMode);

        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['defects']);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Quality\Validator\GenericCheckValidator::validate
     */
    public function testValidateWithStrictComparisonForArrays(): void
    {
        $checkValue = ['a', 'b'];
        $criteria = ['expected_value' => ['a', 'b', 'c']]; // 不同的数组
        $strictMode = true;

        $result = $this->validator->validate($checkValue, $criteria, $strictMode);

        $this->assertFalse($result['valid']);
        self::assertIsArray($result['defects']);
        /** @var array<int, mixed> $defects */
        $defects = $result['defects'];
        $this->assertCount(1, $defects);
        self::assertIsArray($defects[0]);
        /** @var array<string, mixed> $defect */
        $defect = $defects[0];
        $this->assertEquals('value_mismatch', $defect['type']);
        $this->assertTrue($defect['critical']);
    }

    public function testValidatorBasicFunctionality(): void
    {
        // 验证验证器可以正确实例化
        $this->assertInstanceOf(GenericCheckValidator::class, $this->validator);

        // 验证基本功能工作正常
        $result = $this->validator->validate('test', [], false);
        $this->assertArrayHasKey('valid', $result);
        $this->assertArrayHasKey('defects', $result);
    }

    /**
     * 测试边界情况：零值
     */
    public function testValidateWithZeroValues(): void
    {
        $testCases = [
            0,      // integer zero
            0.0,    // float zero
            '0',    // string zero
        ];

        foreach ($testCases as $value) {
            // 零值不应该被认为是空的（对于required检查）
            $result = $this->validator->validate($value, ['required' => true], true);
            $this->assertTrue($result['valid'], 'Zero values should be valid for required checks');
        }
    }

    /**
     * 测试空白字符串的处理
     */
    public function testValidateWithWhitespaceValues(): void
    {
        $checkValue = '   '; // 只包含空格
        $criteria = ['required' => true];
        $strictMode = true;

        // PHP的empty()函数会将空白字符串认为是非空的
        $result = $this->validator->validate($checkValue, $criteria, $strictMode);

        $this->assertTrue($result['valid'], 'Whitespace-only strings should be considered non-empty');
        $this->assertEmpty($result['defects']);
    }

    /**
     * 测试类型严格比较
     */
    public function testValidateWithTypeStrictComparison(): void
    {
        $checkValue = '123';
        $criteria = ['expected_value' => 123]; // 字符串 vs 整数
        $strictMode = false;

        $result = $this->validator->validate($checkValue, $criteria, $strictMode);

        // 使用严格比较 (!==)，所以字符串'123'不等于整数123
        $this->assertFalse($result['valid']);
        self::assertIsArray($result['defects']);
        /** @var array<int, mixed> $defects */
        $defects = $result['defects'];
        $this->assertCount(1, $defects);
        self::assertIsArray($defects[0]);
        /** @var array<string, mixed> $defect */
        $defect = $defects[0];
        $this->assertEquals('value_mismatch', $defect['type']);
    }

    /**
     * 测试复杂场景：required + expected_value 都满足
     */
    public function testValidateWithBothRequiredAndExpectedValueSatisfied(): void
    {
        $checkValue = 'correct_value';
        $criteria = [
            'required' => true,
            'expected_value' => 'correct_value',
        ];
        $strictMode = true;

        $result = $this->validator->validate($checkValue, $criteria, $strictMode);

        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['defects']);
    }
}
