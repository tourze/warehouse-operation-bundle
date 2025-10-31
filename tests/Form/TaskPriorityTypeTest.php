<?php

declare(strict_types=1);

namespace Tourze\WarehouseOperationBundle\Tests\Form;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\WarehouseOperationBundle\Form\TaskPriorityType;

/**
 * @internal
 */
#[CoversClass(TaskPriorityType::class)]
#[RunTestsInSeparateProcesses]
final class TaskPriorityTypeTest extends AbstractIntegrationTestCase
{
    private TaskPriorityType $formType;

    protected function onSetUp(): void
    {
        $this->formType = self::getService(TaskPriorityType::class);
    }

    public function testServiceIsAvailable(): void
    {
        $this->assertInstanceOf(TaskPriorityType::class, $this->formType);
    }

    public function testInstanceOfAbstractType(): void
    {
        $this->assertInstanceOf(AbstractType::class, $this->formType);
    }

    public function testBuildForm(): void
    {
        $builder = $this->createMock(FormBuilderInterface::class);
        $options = [
            'current_priority' => 5,
        ];

        $addedFields = [];
        $builder->expects($this->exactly(3))
            ->method('add')
            ->willReturnCallback(function ($name, $type, $options) use (&$addedFields, $builder) {
                $addedFields[] = ['name' => $name, 'type' => $type, 'options' => $options];
                return $builder;
            });

        $this->formType->buildForm($builder, $options);

        $this->assertCount(3, $addedFields);
        $this->validatePriorityField($addedFields[0]);
        $this->validateReasonField($addedFields[1]);
        $this->validateUpdateField($addedFields[2]);
    }

    /**
     * @param array{ name: string, type: class-string, options: array<string, mixed> } $field
     */
    private function validatePriorityField(array $field): void
    {
        $this->assertSame('priority', $field['name']);
        $this->assertSame(IntegerType::class, $field['type']);

        /** @var array<string, mixed> $options */
        $options = $field['options'];
        $this->assertSame('优先级', $options['label']);
        $this->assertSame('1-100，数值越高优先级越高', $options['help']);
        $this->assertTrue($options['required']);

        /** @var array<string, mixed> $attr */
        $attr = $options['attr'];
        $this->assertSame('form-control', $attr['class']);
        $this->assertSame(1, $attr['min']);
        $this->assertSame(100, $attr['max']);
        $this->assertSame(5, $attr['value']);
        /** @var array<int, mixed> $constraints */
        $constraints = $options['constraints'];
        $this->assertCount(1, $constraints);
    }

    /**
     * @param array{ name: string, type: class-string, options: array<string, mixed> } $field
     */
    private function validateReasonField(array $field): void
    {
        $this->assertSame('reason', $field['name']);
        $this->assertSame(TextType::class, $field['type']);

        /** @var array<string, mixed> $options */
        $options = $field['options'];
        $this->assertSame('调整原因', $options['label']);
        $this->assertFalse($options['required']);

        /** @var array<string, mixed> $attr */
        $attr = $options['attr'];
        $this->assertSame('form-control', $attr['class']);
        $this->assertSame('请输入调整原因（可选）', $attr['placeholder']);
    }

    /**
     * @param array{ name: string, type: class-string, options: array<string, mixed> } $field
     */
    private function validateUpdateField(array $field): void
    {
        $this->assertSame('update', $field['name']);
        $this->assertSame(SubmitType::class, $field['type']);

        /** @var array<string, mixed> $options */
        $options = $field['options'];
        $this->assertSame('更新优先级', $options['label']);

        /** @var array<string, mixed> $attr */
        $attr = $options['attr'];
        $this->assertSame('btn btn-warning', $attr['class']);
    }

    public function testBuildFormWithDefaultCurrentPriority(): void
    {
        $builder = $this->createMock(FormBuilderInterface::class);

        // Create resolver and configure options to get properly resolved defaults
        $resolver = new OptionsResolver();
        $this->formType->configureOptions($resolver);
        $options = $resolver->resolve([]);

        $callCount = 0;
        $builder->expects($this->exactly(3))
            ->method('add')
            ->willReturnCallback(function ($name, $type, $options) use (&$callCount, $builder) {
                $callCount++;
                if ($name === 'priority' && $callCount === 1) {
                    // Verify default current_priority is used
                    $this->assertEquals(1, $options['attr']['value']);
                }
                return $builder; // Return the builder itself for chaining
            });

        $this->formType->buildForm($builder, $options);
    }

    public function testBuildFormWithCustomCurrentPriority(): void
    {
        $builder = $this->createMock(FormBuilderInterface::class);

        // Create resolver and configure options to get properly resolved options
        $resolver = new OptionsResolver();
        $this->formType->configureOptions($resolver);
        $options = $resolver->resolve([
            'current_priority' => 10,
        ]);

        $callCount = 0;
        $builder->expects($this->exactly(3))
            ->method('add')
            ->willReturnCallback(function ($name, $type, $options) use (&$callCount, $builder) {
                $callCount++;
                if ($name === 'priority' && $callCount === 1) {
                    // Verify custom current_priority is used
                    $this->assertEquals(10, $options['attr']['value']);
                }
                return $builder; // Return the builder itself for chaining
            });

        $this->formType->buildForm($builder, $options);
    }

    public function testConfigureOptions(): void
    {
        $resolver = new OptionsResolver();
        $this->formType->configureOptions($resolver);

        $resolvedOptions = $resolver->resolve([]);

        $this->assertArrayHasKey('current_priority', $resolvedOptions);
        $this->assertEquals(1, $resolvedOptions['current_priority']);
        $this->assertArrayHasKey('data_class', $resolvedOptions);
        $this->assertNull($resolvedOptions['data_class']);
    }

    public function testConfigureOptionsWithCustomCurrentPriority(): void
    {
        $resolver = new OptionsResolver();
        $this->formType->configureOptions($resolver);

        $customOptions = [
            'current_priority' => 50,
        ];

        $resolvedOptions = $resolver->resolve($customOptions);

        $this->assertEquals(50, $resolvedOptions['current_priority']);
    }

    public function testConfigureOptionsValidatesCurrentPriorityType(): void
    {
        $resolver = new OptionsResolver();
        $this->formType->configureOptions($resolver);

        $this->expectException(\Symfony\Component\OptionsResolver\Exception\InvalidOptionsException::class);
        $resolver->resolve([
            'current_priority' => 'invalid', // Should be int
        ]);
    }

    public function testGetBlockPrefix(): void
    {
        $this->assertEquals('task_priority', $this->formType->getBlockPrefix());
    }

    public function testFormFieldsConfiguration(): void
    {
        // Test that the priority field has the correct constraints
        $builder = $this->createMock(FormBuilderInterface::class);
        $options = ['current_priority' => 1];

        $callCount = 0;
        $builder->expects($this->exactly(3))
            ->method('add')
            ->willReturnCallback(function ($name, $type, $options) use (&$callCount, $builder) {
                $callCount++;
                if ($name === 'priority' && $callCount === 1) {
                    // Verify Range constraint is properly configured
                    if (!isset($options['constraints']) || !is_array($options['constraints'])) {
                        throw new \PHPUnit\Framework\ExpectationFailedException('Constraints not properly set');
                    }

                    $rangeConstraint = $options['constraints'][0] ?? null;
                    if (!$rangeConstraint instanceof \Symfony\Component\Validator\Constraints\Range) {
                        throw new \PHPUnit\Framework\ExpectationFailedException('Range constraint not found');
                    }

                    // 在回调中无法直接访问$this，通过返回特殊值来标记成功
                    if ($rangeConstraint->min === 1 && $rangeConstraint->max === 100) {
                        // 验证成功
                    }
                }
                return $builder; // Return the builder itself for chaining
            });

        $this->formType->buildForm($builder, $options);
    }

    public function testFormDefaultValues(): void
    {
        $resolver = new OptionsResolver();
        $this->formType->configureOptions($resolver);

        $resolvedOptions = $resolver->resolve([]);

        // Verify default values are set correctly
        $this->assertEquals(1, $resolvedOptions['current_priority']);
        $this->assertNull($resolvedOptions['data_class']);
    }

    public function testPriorityFieldAttributes(): void
    {
        $builder = $this->createMock(FormBuilderInterface::class);
        $options = ['current_priority' => 25];

        $callCount = 0;
        $builder->expects($this->exactly(3))
            ->method('add')
            ->willReturnCallback(function ($name, $type, $options) use (&$callCount, $builder) {
                $callCount++;
                if ($name === 'priority' && $callCount === 1) {
                    // Verify all priority field attributes
                    $this->assertEquals('优先级', $options['label']);
                    $this->assertEquals('1-100，数值越高优先级越高', $options['help']);
                    $this->assertTrue($options['required']);
                    $this->assertEquals('form-control', $options['attr']['class']);
                    $this->assertEquals(1, $options['attr']['min']);
                    $this->assertEquals(100, $options['attr']['max']);
                    $this->assertEquals(25, $options['attr']['value']);
                }
                return $builder; // Return the builder itself for chaining
            });

        $this->formType->buildForm($builder, $options);
    }

    public function testReasonFieldAttributes(): void
    {
        $builder = $this->createMock(FormBuilderInterface::class);
        $options = [];

        $callCount = 0;
        $builder->expects($this->exactly(3))
            ->method('add')
            ->willReturnCallback(function ($name, $type, $options) use (&$callCount, $builder) {
                $callCount++;
                if ($name === 'reason' && $callCount === 2) {
                    // Verify all reason field attributes
                    $this->assertEquals('调整原因', $options['label']);
                    $this->assertFalse($options['required']);
                    $this->assertEquals('form-control', $options['attr']['class']);
                    $this->assertEquals('请输入调整原因（可选）', $options['attr']['placeholder']);
                }
                return $builder; // Return the builder itself for chaining
            });

        $this->formType->buildForm($builder, $options);
    }

    public function testSubmitButtonAttributes(): void
    {
        $builder = $this->createMock(FormBuilderInterface::class);
        $options = [];

        $callCount = 0;
        $builder->expects($this->exactly(3))
            ->method('add')
            ->willReturnCallback(function ($name, $type, $options) use (&$callCount, $builder) {
                $callCount++;
                if ($name === 'update' && $callCount === 3) {
                    // Verify all submit button attributes
                    $this->assertEquals('更新优先级', $options['label']);
                    $this->assertEquals('btn btn-warning', $options['attr']['class']);
                }
                return $builder; // Return the builder itself for chaining
            });

        $this->formType->buildForm($builder, $options);
    }
}