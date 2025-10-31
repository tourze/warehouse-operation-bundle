<?php

declare(strict_types=1);

namespace Tourze\WarehouseOperationBundle\Tests\Form;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\WarehouseOperationBundle\Form\TaskAssignmentType;

/**
 * @internal
 */
#[CoversClass(TaskAssignmentType::class)]
#[RunTestsInSeparateProcesses]
final class TaskAssignmentTypeTest extends AbstractIntegrationTestCase
{
    private TaskAssignmentType $formType;

    protected function onSetUp(): void
    {
        $this->formType = static::getService(TaskAssignmentType::class);
    }

    public function testServiceIsAvailable(): void
    {
        $this->assertInstanceOf(TaskAssignmentType::class, $this->formType);
    }

    public function testInstanceOfAbstractType(): void
    {
        $this->assertInstanceOf(AbstractType::class, $this->formType);
    }

    public function testBuildForm(): void
    {
        $builder = $this->createMock(FormBuilderInterface::class);
        $options = [
            'worker_choices' => ['worker1' => 'Worker 1', 'worker2' => 'Worker 2']
        ];

        // Test that the form builder's add method is called exactly 2 times
        $builder->expects($this->exactly(2))
            ->method('add')
            ->willReturnSelf();

        $this->formType->buildForm($builder, $options);
    }

    public function testBuildFormWithCorrectFields(): void
    {
        $builder = $this->createMock(FormBuilderInterface::class);
        $options = [
            'worker_choices' => ['worker1' => 'Worker 1']
        ];

        $addedFields = [];

        $builder->expects($this->exactly(2))
            ->method('add')
            ->willReturnCallback(function($name, $type, $options = []) use (&$addedFields, $builder) {
                $addedFields[] = [
                    'name' => $name,
                    'type' => $type,
                    'options' => $options
                ];
                return $builder; // Return the same builder to allow chaining
            });

        $this->formType->buildForm($builder, $options);

        // Verify that exactly 2 fields were added
        $this->assertCount(2, $addedFields);
        $this->assertArrayHasKey(0, $addedFields, 'Should have first field at index 0');
        $this->assertArrayHasKey(1, $addedFields, 'Should have second field at index 1');

        // Verify the workerId field
        $workerField = $addedFields[0];
        $this->assertIsArray($workerField, 'Worker field should be an array');
        $this->assertArrayHasKey('name', $workerField, 'Worker field should have name key');
        $this->assertArrayHasKey('type', $workerField, 'Worker field should have type key');
        $this->assertArrayHasKey('options', $workerField, 'Worker field should have options key');

        $this->assertSame('workerId', $workerField['name']);
        $this->assertSame(ChoiceType::class, $workerField['type']);

        $workerOptions = $workerField['options'];
        $this->assertIsArray($workerOptions, 'Worker options should be an array');
        $this->assertArrayHasKey('label', $workerOptions);
        $this->assertArrayHasKey('choices', $workerOptions);
        $this->assertArrayHasKey('placeholder', $workerOptions);
        $this->assertArrayHasKey('required', $workerOptions);
        $this->assertArrayHasKey('attr', $workerOptions);

        $this->assertSame('选择作业员', $workerOptions['label']);
        $this->assertSame(['worker1' => 'Worker 1'], $workerOptions['choices']);
        $this->assertSame('请选择作业员', $workerOptions['placeholder']);
        $this->assertTrue($workerOptions['required']);

        $workerAttr = $workerOptions['attr'];
        $this->assertIsArray($workerAttr, 'Worker attr should be an array');
        $this->assertArrayHasKey('class', $workerAttr);
        $this->assertSame('form-control', $workerAttr['class']);

        // Verify the submit field
        $submitField = $addedFields[1];
        $this->assertIsArray($submitField, 'Submit field should be an array');
        $this->assertArrayHasKey('name', $submitField, 'Submit field should have name key');
        $this->assertArrayHasKey('type', $submitField, 'Submit field should have type key');
        $this->assertArrayHasKey('options', $submitField, 'Submit field should have options key');

        $this->assertSame('assign', $submitField['name']);
        $this->assertSame(SubmitType::class, $submitField['type']);

        $submitOptions = $submitField['options'];
        $this->assertIsArray($submitOptions, 'Submit options should be an array');
        $this->assertArrayHasKey('label', $submitOptions);
        $this->assertArrayHasKey('attr', $submitOptions);

        $this->assertSame('确认分配', $submitOptions['label']);

        $submitAttr = $submitOptions['attr'];
        $this->assertIsArray($submitAttr, 'Submit attr should be an array');
        $this->assertArrayHasKey('class', $submitAttr);
        $this->assertSame('btn btn-primary', $submitAttr['class']);
    }

    public function testConfigureOptions(): void
    {
        $resolver = $this->createMock(OptionsResolver::class);

        // Expect setDefaults to be called with correct default values
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with(\PHPUnit\Framework\Assert::callback(fn($defaults) => is_array($defaults)));

        // Expect setAllowedTypes to be called for worker_choices
        $resolver->expects($this->once())
            ->method('setAllowedTypes')
            ->with('worker_choices', 'array');

        $this->formType->configureOptions($resolver);
    }

    public function testFormWithEmptyWorkerChoices(): void
    {
        $builder = $this->createMock(FormBuilderInterface::class);
        $options = [
            'worker_choices' => []
        ];

        $addedFields = [];

        $builder->expects($this->exactly(2))
            ->method('add')
            ->willReturnCallback(function($name, $type, $options = []) use (&$addedFields, $builder) {
                $addedFields[] = [
                    'name' => $name,
                    'type' => $type,
                    'options' => $options
                ];
                return $builder;
            });

        $this->formType->buildForm($builder, $options);

        // Verify that empty choices are properly handled
        $this->assertArrayHasKey(0, $addedFields, 'Should have first field at index 0');
        $this->assertArrayHasKey('name', $addedFields[0], 'First field should have name key');
        $this->assertArrayHasKey('options', $addedFields[0], 'First field should have options key');

        $this->assertSame('workerId', $addedFields[0]['name']);

        $firstFieldOptions = $addedFields[0]['options'];
        $this->assertIsArray($firstFieldOptions, 'First field options should be an array');
        $this->assertArrayHasKey('choices', $firstFieldOptions, 'Options should have choices key');
        $this->assertSame([], $firstFieldOptions['choices']);
    }

    public function testFormWithMultipleWorkerChoices(): void
    {
        $builder = $this->createMock(FormBuilderInterface::class);
        $workerChoices = [
            'worker_1' => '张三',
            'worker_2' => '李四',
            'worker_3' => '王五'
        ];
        $options = [
            'worker_choices' => $workerChoices
        ];

        $addedFields = [];
        $builder->expects($this->exactly(2))
            ->method('add')
            ->willReturnCallback(function($name, $type, $options = []) use (&$addedFields, $builder) {
                $addedFields[] = [
                    'name' => $name,
                    'type' => $type,
                    'options' => $options
                ];
                return $builder;
            });

        $this->formType->buildForm($builder, $options);

        // Verify that all worker choices are passed correctly
        $this->assertArrayHasKey(0, $addedFields, 'Should have first field at index 0');
        $this->assertArrayHasKey('options', $addedFields[0], 'First field should have options key');

        $firstFieldOptions = $addedFields[0]['options'];
        $this->assertIsArray($firstFieldOptions, 'First field options should be an array');
        $this->assertArrayHasKey('choices', $firstFieldOptions, 'Options should have choices key');

        $this->assertSame($workerChoices, $firstFieldOptions['choices']);
    }

  }