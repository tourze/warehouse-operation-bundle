<?php

declare(strict_types=1);

namespace Tourze\WarehouseOperationBundle\Tests\Form;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\WarehouseOperationBundle\Form\TaskPauseType;

/**
 * @internal
 */
#[CoversClass(TaskPauseType::class)]
#[RunTestsInSeparateProcesses]
final class TaskPauseTypeTest extends AbstractIntegrationTestCase
{
    private TaskPauseType $formType;

    protected function onSetUp(): void
    {
        $this->formType = static::getService(TaskPauseType::class);
    }

    public function testServiceIsAvailable(): void
    {
        $this->assertInstanceOf(TaskPauseType::class, $this->formType);
    }

    public function testInstanceOfAbstractType(): void
    {
        $this->assertInstanceOf(AbstractType::class, $this->formType);
    }

    public function testBuildForm(): void
    {
        $builder = $this->createMock(FormBuilderInterface::class);
        $options = [];

        // Test that the form builder's add method is called exactly 3 times
        $builder->expects($this->exactly(3))
            ->method('add')
            ->willReturnSelf();

        $this->formType->buildForm($builder, $options);
    }

    public function testReasonFieldConfiguration(): void
    {
        $builder = $this->createMock(FormBuilderInterface::class);
        $options = [];

        // Use spy approach to capture all calls
        $calls = [];
        $builder->method('add')->willReturnCallback(function(...$args) use (&$calls, $builder) {
            $calls[] = $args;
            return $builder; // Return the mock builder to support method chaining
        });

        $this->formType->buildForm($builder, $options);

        // Verify we have exactly 3 calls
        $this->assertCount(3, $calls);

        // Find and verify the reason field call
        $reasonCall = null;
        foreach ($calls as $call) {
            $this->assertIsArray($call, 'Form builder call should be an array');
            $this->assertArrayHasKey(0, $call, 'Form builder call should have field name at index 0');
            if ($call[0] === 'reason') {
                $reasonCall = $call;
                break;
            }
        }

        $this->assertNotNull($reasonCall, 'Reason field should be added');
        $this->assertArrayHasKey(0, $reasonCall, 'Reason call should have field name at index 0');
        $this->assertArrayHasKey(1, $reasonCall, 'Reason call should have field type at index 1');
        $this->assertArrayHasKey(2, $reasonCall, 'Reason call should have options at index 2');

        $this->assertEquals('reason', $reasonCall[0]);
        $this->assertEquals(ChoiceType::class, $reasonCall[1]);

        $options = $reasonCall[2];
        $this->assertIsArray($options, 'Options should be an array');
        $this->assertEquals('暂停原因', $options['label']);
        $this->assertEquals('请选择暂停原因', $options['placeholder']);
        $this->assertTrue($options['required']);
        $this->assertEquals('form-control', $options['attr']['class']);
        $this->assertIsArray($options['choices']);
        $this->assertCount(1, $options['constraints']);
    }

    public function testReasonFieldChoices(): void
    {
        $builder = $this->createMock(FormBuilderInterface::class);
        $options = [];

        // Use spy approach to capture all calls
        $calls = [];
        $builder->method('add')->willReturnCallback(function(...$args) use (&$calls, $builder) {
            $calls[] = $args;
            return $builder; // Return the mock builder to support method chaining
        });

        $this->formType->buildForm($builder, $options);

        // Find the reason field call
        $reasonCall = null;
        foreach ($calls as $call) {
            $this->assertIsArray($call, 'Form builder call should be an array');
            $this->assertArrayHasKey(0, $call, 'Form builder call should have field name at index 0');
            if ($call[0] === 'reason') {
                $reasonCall = $call;
                break;
            }
        }

        $this->assertNotNull($reasonCall, 'Reason field should be added');
        $this->assertArrayHasKey(2, $reasonCall, 'Reason call should have options at index 2');

        $expectedChoices = [
            '设备故障' => 'equipment_failure',
            '物料不足' => 'material_shortage',
            '人员调度' => 'personnel_dispatch',
            '质量问题' => 'quality_issue',
            '紧急插单' => 'urgent_insertion',
            '等待指示' => 'waiting_instruction',
            '其他原因' => 'other',
        ];

        $options = $reasonCall[2];
        $this->assertIsArray($options, 'Options should be an array');
        $this->assertArrayHasKey('choices', $options, 'Options should have choices key');
        $this->assertEquals($expectedChoices, $options['choices']);
    }

    public function testReasonFieldConstraints(): void
    {
        $builder = $this->createMock(FormBuilderInterface::class);
        $options = [];

        // Use spy approach to capture all calls
        $calls = [];
        $builder->method('add')->willReturnCallback(function(...$args) use (&$calls, $builder) {
            $calls[] = $args;
            return $builder; // Return the mock builder to support method chaining
        });

        $this->formType->buildForm($builder, $options);

        // Find the reason field call
        $reasonCall = null;
        foreach ($calls as $call) {
            $this->assertIsArray($call, 'Form builder call should be an array');
            $this->assertArrayHasKey(0, $call, 'Form builder call should have field name at index 0');
            if ($call[0] === 'reason') {
                $reasonCall = $call;
                break;
            }
        }

        $this->assertNotNull($reasonCall, 'Reason field should be added');
        $this->assertArrayHasKey(2, $reasonCall, 'Reason call should have options at index 2');

        $options = $reasonCall[2];
        $this->assertIsArray($options, 'Options should be an array');
        $this->assertArrayHasKey('constraints', $options, 'Options should have constraints key');

        $constraints = $options['constraints'];
        $this->assertIsArray($constraints);
        $this->assertCount(1, $constraints);
        $this->assertArrayHasKey(0, $constraints, 'Constraints array should have an element at index 0');

        $notBlankConstraint = $constraints[0];
        $this->assertInstanceOf(\Symfony\Component\Validator\Constraints\NotBlank::class, $notBlankConstraint);
        $this->assertEquals('请选择暂停原因', $notBlankConstraint->message);
    }

    public function testDescriptionFieldConfiguration(): void
    {
        $builder = $this->createMock(FormBuilderInterface::class);
        $options = [];

        // Use a spy approach to capture all calls and verify the second call
        $calls = [];
        $builder->expects($this->exactly(3))
            ->method('add')
            ->willReturnCallback(function(...$args) use (&$calls, $builder) {
                $calls[] = $args;
                return $builder; // Return the mock builder to support method chaining
            });

        $this->formType->buildForm($builder, $options);

        // Verify we have exactly 3 calls
        $this->assertCount(3, $calls);

        // Find the description field call (should be the second one)
        $descriptionCall = null;
        foreach ($calls as $call) {
            $this->assertIsArray($call, 'Form builder call should be an array');
            $this->assertArrayHasKey(0, $call, 'Form builder call should have field name at index 0');
            if ($call[0] === 'description') {
                $descriptionCall = $call;
                break;
            }
        }

        $this->assertNotNull($descriptionCall, 'Description field should be added');
        $this->assertArrayHasKey(0, $descriptionCall, 'Description call should have field name at index 0');
        $this->assertArrayHasKey(1, $descriptionCall, 'Description call should have field type at index 1');
        $this->assertArrayHasKey(2, $descriptionCall, 'Description call should have options at index 2');

        $this->assertEquals('description', $descriptionCall[0]);
        $this->assertEquals(TextareaType::class, $descriptionCall[1]);

        $options = $descriptionCall[2];
        $this->assertIsArray($options, 'Options should be an array');
        $this->assertEquals('详细说明', $options['label']);
        $this->assertFalse($options['required']);
        $this->assertEquals('form-control', $options['attr']['class']);
        $this->assertEquals(3, $options['attr']['rows']);
        $this->assertEquals('请详细说明暂停情况（可选）', $options['attr']['placeholder']);
    }

    public function testDescriptionFieldConstraints(): void
    {
        $builder = $this->createMock(FormBuilderInterface::class);
        $options = [];

        // Use a spy approach to capture all calls and verify the second call
        $calls = [];
        $builder->method('add')->willReturnCallback(function(...$args) use (&$calls, $builder) {
            $calls[] = $args;
            return $builder; // Return the mock builder to support method chaining
        });

        $this->formType->buildForm($builder, $options);

        // Verify we have exactly 3 calls
        $this->assertCount(3, $calls);

        // Find the description field call
        $descriptionCall = null;
        foreach ($calls as $call) {
            $this->assertIsArray($call, 'Form builder call should be an array');
            $this->assertArrayHasKey(0, $call, 'Form builder call should have field name at index 0');
            if ($call[0] === 'description') {
                $descriptionCall = $call;
                break;
            }
        }

        $this->assertNotNull($descriptionCall, 'Description field should be added');
        $this->assertArrayHasKey(2, $descriptionCall, 'Description call should have options at index 2');

        $options = $descriptionCall[2];
        $this->assertIsArray($options, 'Options should be an array');
        $this->assertArrayHasKey('constraints', $options, 'Options should have constraints key');

        $constraints = $options['constraints'];
        $this->assertIsArray($constraints);
        $this->assertCount(1, $constraints);
        $this->assertArrayHasKey(0, $constraints, 'Constraints array should have an element at index 0');

        $lengthConstraint = $constraints[0];
        $this->assertInstanceOf(\Symfony\Component\Validator\Constraints\Length::class, $lengthConstraint);
        $this->assertEquals(500, $lengthConstraint->max);
        $this->assertEquals('详细说明不能超过500个字符', $lengthConstraint->maxMessage);
    }

    public function testPauseButtonConfiguration(): void
    {
        $builder = $this->createMock(FormBuilderInterface::class);
        $options = [];

        // Use a spy approach to capture all calls and verify the third call
        $calls = [];
        $builder->method('add')->willReturnCallback(function(...$args) use (&$calls, $builder) {
            $calls[] = $args;
            return $builder; // Return the mock builder to support method chaining
        });

        $this->formType->buildForm($builder, $options);

        // Verify we have exactly 3 calls
        $this->assertCount(3, $calls);

        // Find the pause button call (should be the third one)
        $pauseCall = null;
        foreach ($calls as $call) {
            $this->assertIsArray($call, 'Form builder call should be an array');
            $this->assertArrayHasKey(0, $call, 'Form builder call should have field name at index 0');
            if ($call[0] === 'pause') {
                $pauseCall = $call;
                break;
            }
        }

        $this->assertNotNull($pauseCall, 'Pause button should be added');
        $this->assertArrayHasKey(0, $pauseCall, 'Pause call should have field name at index 0');
        $this->assertArrayHasKey(1, $pauseCall, 'Pause call should have field type at index 1');
        $this->assertArrayHasKey(2, $pauseCall, 'Pause call should have options at index 2');

        $this->assertEquals('pause', $pauseCall[0]);
        $this->assertEquals(SubmitType::class, $pauseCall[1]);

        $options = $pauseCall[2];
        $this->assertIsArray($options, 'Options should be an array');
        $this->assertArrayHasKey('label', $options, 'Options should have label key');
        $this->assertEquals('确认暂停', $options['label']);
        $this->assertArrayHasKey('attr', $options, 'Options should have attr key');
        $this->assertIsArray($options['attr'], 'Options attr should be an array');
        $this->assertArrayHasKey('class', $options['attr'], 'Options attr should have class key');
        $this->assertEquals('btn btn-warning', $options['attr']['class']);
    }

    public function testConfigureOptions(): void
    {
        $resolver = new OptionsResolver();
        $this->formType->configureOptions($resolver);

        $resolvedOptions = $resolver->resolve([]);

        $this->assertArrayHasKey('data_class', $resolvedOptions);
        $this->assertNull($resolvedOptions['data_class']);
    }

    public function testGetBlockPrefix(): void
    {
        $this->assertEquals('task_pause', $this->formType->getBlockPrefix());
    }

    public function testFormConfiguration(): void
    {
        $resolver = new OptionsResolver();
        $this->formType->configureOptions($resolver);

        $resolvedOptions = $resolver->resolve([]);

        // Verify default configuration
        $this->assertNull($resolvedOptions['data_class']);

        // Verify the form can handle additional options without errors - test with valid options only
        $customOptions = [
            'data_class' => \stdClass::class,
        ];

        $resolvedCustomOptions = $resolver->resolve($customOptions);
        $this->assertEquals(\stdClass::class, $resolvedCustomOptions['data_class']);
    }

    public function testFormFieldsHaveCorrectRequiredStatus(): void
    {
        $builder = $this->createMock(FormBuilderInterface::class);
        $options = [];

        // Use a spy approach to capture all calls
        $calls = [];
        $builder->method('add')->willReturnCallback(function(...$args) use (&$calls, $builder) {
            $calls[] = $args;
            return $builder; // Return the mock builder to support method chaining
        });

        $this->formType->buildForm($builder, $options);

        // Verify we have exactly 3 calls
        $this->assertCount(3, $calls);

        // Check required status for each field
        $reasonCall = null;
        $descriptionCall = null;
        $pauseCall = null;

        foreach ($calls as $call) {
            $this->assertIsArray($call, 'Form builder call should be an array');
            $this->assertArrayHasKey(0, $call, 'Form builder call should have field name at index 0');
            switch ($call[0]) {
                case 'reason':
                    $reasonCall = $call;
                    break;
                case 'description':
                    $descriptionCall = $call;
                    break;
                case 'pause':
                    $pauseCall = $call;
                    break;
            }
        }

        // Verify reason field is required
        $this->assertNotNull($reasonCall, 'Reason field should be added');
        $this->assertArrayHasKey(2, $reasonCall, 'Reason call should have options at index 2');
        $this->assertTrue($reasonCall[2]['required'], 'Reason field should be required');

        // Verify description field is optional
        $this->assertNotNull($descriptionCall, 'Description field should be added');
        $this->assertArrayHasKey(2, $descriptionCall, 'Description call should have options at index 2');
        $this->assertFalse($descriptionCall[2]['required'], 'Description field should be optional');

        // Verify pause button is present
        $this->assertNotNull($pauseCall, 'Pause button should be added');
    }
}