<?php

declare(strict_types=1);

namespace Tourze\WarehouseOperationBundle\Tests\Service\Quality\Validator;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\WarehouseOperationBundle\Service\Quality\Validator\GenericCheckValidator;
use Tourze\WarehouseOperationBundle\Service\Quality\Validator\QualityCheckValidatorInterface;
use Tourze\WarehouseOperationBundle\Service\Quality\Validator\QualityCheckValidatorRegistry;
use Tourze\WarehouseOperationBundle\Service\Quality\Validator\QuantityCheckValidator;
use Tourze\WarehouseOperationBundle\Service\Quality\Validator\VisualCheckValidator;

/**
 * QualityCheckValidatorRegistry 单元测试
 *
 * 测试质检验证器注册表的功能，包括验证器注册、获取、支持检查等核心逻辑。
 * 验证注册表的正确性、默认行为和异常处理。
 * @internal
 */
#[CoversClass(QualityCheckValidatorRegistry::class)]
#[RunTestsInSeparateProcesses]
class QualityCheckValidatorRegistryTest extends AbstractIntegrationTestCase
{
    private QualityCheckValidatorRegistry $registry;

    protected function onSetUp(): void
    {
        $this->registry = parent::getService(QualityCheckValidatorRegistry::class);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Quality\Validator\QualityCheckValidatorRegistry::__construct
     */
    public function testConstructorRegistersDefaultValidators(): void
    {
        // 验证默认验证器已注册
        $this->assertTrue($this->registry->supports('visual_check'));
        $this->assertTrue($this->registry->supports('quantity_check'));
        $this->assertTrue($this->registry->supports('generic_check'));

        // 验证获取的验证器类型正确
        $this->assertInstanceOf(VisualCheckValidator::class, $this->registry->getValidator('visual_check'));
        $this->assertInstanceOf(QuantityCheckValidator::class, $this->registry->getValidator('quantity_check'));
        $this->assertInstanceOf(GenericCheckValidator::class, $this->registry->getValidator('generic_check'));
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Quality\Validator\QualityCheckValidatorRegistry::getValidator
     * @covers \Tourze\WarehouseOperationBundle\Service\Quality\Validator\QualityCheckValidatorRegistry::register
     */
    public function testRegisterAndGetValidator(): void
    {
        // 创建一个自定义验证器
        $customValidator = $this->createMockValidator('custom_check');

        // 注册自定义验证器
        $this->registry->register($customValidator);

        // 验证可以获取到注册的验证器
        $retrievedValidator = $this->registry->getValidator('custom_check');
        $this->assertSame($customValidator, $retrievedValidator);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Quality\Validator\QualityCheckValidatorRegistry::getValidator
     */
    public function testGetValidatorReturnsDefaultForUnsupportedType(): void
    {
        $validator = $this->registry->getValidator('unsupported_type');

        // 应该返回默认验证器（GenericCheckValidator）
        $this->assertInstanceOf(GenericCheckValidator::class, $validator);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Quality\Validator\QualityCheckValidatorRegistry::supports
     */
    public function testSupportsReturnsTrueForRegisteredTypes(): void
    {
        // 测试默认注册的类型
        $this->assertTrue($this->registry->supports('visual_check'));
        $this->assertTrue($this->registry->supports('quantity_check'));
        $this->assertTrue($this->registry->supports('generic_check'));

        // 注册新的验证器
        $customValidator = $this->createMockValidator('new_type');
        $this->registry->register($customValidator);

        // 验证新类型被支持
        $this->assertTrue($this->registry->supports('new_type'));
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Quality\Validator\QualityCheckValidatorRegistry::supports
     */
    public function testSupportsReturnsFalseForUnsupportedTypes(): void
    {
        $this->assertFalse($this->registry->supports('unsupported_type'));
        $this->assertFalse($this->registry->supports('non_existent_check'));
        $this->assertFalse($this->registry->supports(''));
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Quality\Validator\QualityCheckValidatorRegistry::register
     */
    public function testRegisterOverwritesExistingValidator(): void
    {
        // 创建两个不同的验证器实例
        $originalValidator = $this->createMockValidator('test_type');
        $newValidator = $this->createMockValidator('test_type');

        // 注册第一个验证器
        $this->registry->register($originalValidator);
        $retrieved1 = $this->registry->getValidator('test_type');
        $this->assertSame($originalValidator, $retrieved1);

        // 注册第二个验证器（相同类型）
        $this->registry->register($newValidator);
        $retrieved2 = $this->registry->getValidator('test_type');
        $this->assertSame($newValidator, $retrieved2);
        $this->assertNotSame($originalValidator, $retrieved2);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Quality\Validator\QualityCheckValidatorRegistry::getValidator
     */
    public function testGetValidatorConsistency(): void
    {
        // 多次获取同一类型的验证器应该返回相同实例
        $validator1 = $this->registry->getValidator('visual_check');
        $validator2 = $this->registry->getValidator('visual_check');

        $this->assertSame($validator1, $validator2);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Quality\Validator\QualityCheckValidatorRegistry::getValidator
     */
    public function testGetDefaultValidatorConsistency(): void
    {
        // 多次获取不支持类型的验证器应该返回相同的默认实例
        $validator1 = $this->registry->getValidator('unsupported1');
        $validator2 = $this->registry->getValidator('unsupported2');

        $this->assertSame($validator1, $validator2);
        $this->assertInstanceOf(GenericCheckValidator::class, $validator1);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Quality\Validator\QualityCheckValidatorRegistry::register
     */
    public function testRegisterMultipleValidators(): void
    {
        $validators = [
            'type1' => $this->createMockValidator('type1'),
            'type2' => $this->createMockValidator('type2'),
            'type3' => $this->createMockValidator('type3'),
        ];

        // 注册多个验证器
        foreach ($validators as $validator) {
            $this->registry->register($validator);
        }

        // 验证所有验证器都被正确注册
        foreach ($validators as $type => $expectedValidator) {
            $this->assertTrue($this->registry->supports($type));
            $this->assertSame($expectedValidator, $this->registry->getValidator($type));
        }
    }

    /**
     * 测试验证器的实际功能
     */
    public function testValidatorsFunctionality(): void
    {
        // 测试视觉检查验证器
        $visualValidator = $this->registry->getValidator('visual_check');
        $this->assertEquals('visual_check', $visualValidator->getSupportedCheckType());

        // 测试数量检查验证器
        $quantityValidator = $this->registry->getValidator('quantity_check');
        $this->assertEquals('quantity_check', $quantityValidator->getSupportedCheckType());

        // 测试通用检查验证器
        $genericValidator = $this->registry->getValidator('generic_check');
        $this->assertEquals('generic_check', $genericValidator->getSupportedCheckType());
    }

    public function testRegistryBasicFunctionality(): void
    {
        // 验证注册表可以正确实例化
        $this->assertInstanceOf(QualityCheckValidatorRegistry::class, $this->registry);

        // 验证基本功能工作正常
        $this->assertTrue($this->registry->supports('visual_check'));
        $this->assertFalse($this->registry->supports('unknown_type'));

        $validator = $this->registry->getValidator('visual_check');
        $this->assertInstanceOf(QualityCheckValidatorInterface::class, $validator);
    }

    /**
     * 测试边界情况：空字符串类型
     */
    public function testGetValidatorWithEmptyString(): void
    {
        $validator = $this->registry->getValidator('');

        // 空字符串应该返回默认验证器
        $this->assertInstanceOf(GenericCheckValidator::class, $validator);
        $this->assertFalse($this->registry->supports(''));
    }

    /**
     * 测试大小写敏感性
     */
    public function testCaseSensitivity(): void
    {
        // 验证器类型是大小写敏感的
        $this->assertTrue($this->registry->supports('visual_check'));
        $this->assertFalse($this->registry->supports('Visual_Check'));
        $this->assertFalse($this->registry->supports('VISUAL_CHECK'));

        // 获取不同大小写的验证器应该返回默认验证器
        $validator1 = $this->registry->getValidator('visual_check');
        $validator2 = $this->registry->getValidator('Visual_Check');

        $this->assertInstanceOf(VisualCheckValidator::class, $validator1);
        $this->assertInstanceOf(GenericCheckValidator::class, $validator2);

        // 验证两个验证器支持不同的检查类型
        $this->assertEquals('visual_check', $validator1->getSupportedCheckType());
        $this->assertEquals('generic_check', $validator2->getSupportedCheckType());
    }

    /**
     * 测试注册相同验证器多次
     */
    public function testRegisterSameValidatorMultipleTimes(): void
    {
        $validator = $this->createMockValidator('test_type');

        // 多次注册相同验证器
        $this->registry->register($validator);
        $this->registry->register($validator);
        $this->registry->register($validator);

        // 应该正常工作
        $this->assertTrue($this->registry->supports('test_type'));
        $this->assertSame($validator, $this->registry->getValidator('test_type'));
    }

    /**
     * 测试默认验证器覆盖
     */
    public function testOverrideDefaultValidator(): void
    {
        // 创建自定义的 generic_check 验证器
        $customGenericValidator = $this->createMockValidator('generic_check');

        // 覆盖默认的 generic_check 验证器
        $this->registry->register($customGenericValidator);

        // 验证已被覆盖
        $retrievedValidator = $this->registry->getValidator('generic_check');
        $this->assertSame($customGenericValidator, $retrievedValidator);
        $this->assertNotInstanceOf(GenericCheckValidator::class, $retrievedValidator);
    }

    /**
     * 创建模拟验证器
     */
    private function createMockValidator(string $checkType): QualityCheckValidatorInterface
    {
        $validator = $this->createMock(QualityCheckValidatorInterface::class);
        $validator->method('getSupportedCheckType')->willReturn($checkType);
        $validator->method('validate')->willReturn(['valid' => true, 'defects' => []]);

        return $validator;
    }
}
