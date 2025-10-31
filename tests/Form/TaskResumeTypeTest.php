<?php

declare(strict_types=1);

namespace Tourze\WarehouseOperationBundle\Tests\Form;

use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Test\TypeTestCase;
use Tourze\WarehouseOperationBundle\Form\TaskResumeType;

/**
 * TaskResumeType 表单类型测试
 *
 * 测试任务恢复表单的构建、配置和验证功能
 * @internal
 */
#[CoversClass(TaskResumeType::class)]
final class TaskResumeTypeTest extends TypeTestCase
{
    private TaskResumeType $formType;

    protected function setUp(): void
    {
        parent::setUp();
        $this->formType = new TaskResumeType();
    }

    /**
     * 测试表单包含正确的字段
     */
    public function testFormHasExpectedFields(): void
    {
        $form = $this->factory->create(TaskResumeType::class);

        // 验证表单包含 description 字段
        $this->assertTrue($form->has('description'), '表单应该包含 description 字段');

        // 验证表单包含 resume 提交按钮
        $this->assertTrue($form->has('resume'), '表单应该包含 resume 提交按钮');

        // 验证字段类型
        $descriptionField = $form->get('description');
        $this->assertInstanceOf(
            TextareaType::class,
            $descriptionField->getConfig()->getType()->getInnerType(),
            'description 字段应该是 TextareaType 类型'
        );

        $resumeField = $form->get('resume');
        $this->assertInstanceOf(
            SubmitType::class,
            $resumeField->getConfig()->getType()->getInnerType(),
            'resume 字段应该是 SubmitType 类型'
        );
    }

    /**
     * 测试表单配置选项
     */
    public function testFormOptionsConfiguration(): void
    {
        $form = $this->factory->create(TaskResumeType::class);
        $config = $form->getConfig();

        // 验证 data_class 配置为 null（不绑定到实体）
        $this->assertNull($config->getOption('data_class'), 'data_class 应该配置为 null');
    }

    /**
     * 测试恢复选项配置
     */
    public function testResumeOptionConfiguration(): void
    {
        $form = $this->factory->create(TaskResumeType::class);
        $resumeField = $form->get('resume');
        $resumeConfig = $resumeField->getConfig();

        // 验证按钮标签
        $this->assertEquals('确认恢复', $resumeConfig->getOption('label'), '恢复按钮标签应该是"确认恢复"');

        // 验证按钮 CSS 类
        $attributes = $resumeConfig->getOption('attr', []);
        /** @var array<string, mixed> $attributes */
        $this->assertArrayHasKey('class', $attributes, '恢复按钮应该有 class 属性');
        $this->assertIsString($attributes['class'], 'class属性应该是字符串');
        $this->assertStringContainsString('btn btn-success', $attributes['class'], '恢复按钮应该包含 btn-success 样式');
    }

    /**
     * 测试描述字段配置
     */
    public function testDescriptionFieldConfiguration(): void
    {
        $form = $this->factory->create(TaskResumeType::class);
        $descriptionField = $form->get('description');
        $descriptionConfig = $descriptionField->getConfig();

        // 验证字段标签
        $this->assertEquals('恢复说明', $descriptionConfig->getOption('label'), '描述字段标签应该是"恢复说明"');

        // 验证字段不是必填的
        $this->assertFalse($descriptionConfig->getRequired(), '描述字段不应该是必填的');

        // 验证字段属性
        $attributes = $descriptionConfig->getOption('attr', []);
        /** @var array<string, mixed> $attributes */
        $this->assertArrayHasKey('class', $attributes, '描述字段应该有 class 属性');
        $this->assertIsString($attributes['class'], 'class属性应该是字符串');
        $this->assertStringContainsString('form-control', $attributes['class'], '描述字段应该包含 form-control 样式');
        $this->assertEquals(3, $attributes['rows'], '描述字段应该设置为 3 行');
        $this->assertEquals('请说明恢复情况（可选）', $attributes['placeholder'], '描述字段占位符应该正确');
    }

    /**
     * 测试表单默认配置
     */
    public function testFormDefaultConfiguration(): void
    {
        // 验证表单可以正常提交空数据
        $form1 = $this->factory->create(TaskResumeType::class);
        $form1->submit([]);
        $this->assertTrue($form1->isSynchronized(), '表单应该能够同步空数据');

        // 验证表单可以正常提交有效的描述数据
        $form2 = $this->factory->create(TaskResumeType::class);
        $form2->submit([
            'description' => '任务已恢复正常状态',
        ]);
        $this->assertTrue($form2->isSynchronized(), '表单应该能够同步有效的描述数据');

        // 验证提交后的数据
        $data = $form2->getData();
        $this->assertIsArray($data, '表单数据应该是数组类型');
        $this->assertArrayHasKey('description', $data, '数据应该包含 description 字段');
        $this->assertEquals('任务已恢复正常状态', $data['description'], 'description 数据应该正确');
    }

    /**
     * 测试表单提交长数据（验证同步性）
     */
    public function testFormSubmitLongDescriptionData(): void
    {
        $form = $this->factory->create(TaskResumeType::class);

        // 创建一个较长的描述
        $longDescription = str_repeat('这是一个描述文本。', 20); // 约 200 个字符

        $form->submit([
            'description' => $longDescription,
        ]);

        $this->assertTrue($form->isSynchronized(), '表单应该能够同步数据');
        $this->assertTrue($form->isValid(), '表单应该是有效的');

        // 验证数据同步正确
        $data = $form->getData();
        /** @var array<string, mixed> $data */
        $this->assertEquals($longDescription, $data['description'], '长描述数据应该被正确保存');
    }

    /**
     * 测试表单提交空字符串数据
     */
    public function testFormSubmitEmptyStringData(): void
    {
        $form = $this->factory->create(TaskResumeType::class);

        $form->submit([
            'description' => '',
        ]);

        $this->assertTrue($form->isSynchronized(), '表单应该能够同步空字符串数据');
        $this->assertTrue($form->isValid(), '空字符串应该是有效的（因为字段不是必填的）');

        $data = $form->getData();
        /** @var array<string, mixed> $data */
        $this->assertEquals('', $data['description'], '空字符串应该被正确保存');
    }

    /**
     * 测试表单提交 null 数据
     */
    public function testFormSubmitNullData(): void
    {
        $form = $this->factory->create(TaskResumeType::class);

        $form->submit([
            'description' => null,
        ]);

        $this->assertTrue($form->isSynchronized(), '表单应该能够同步 null 数据');
        $this->assertTrue($form->isValid(), 'null 数据应该是有效的（因为字段不是必填的）');

        $data = $form->getData();
        /** @var array<string, mixed> $data */
        $this->assertNull($data['description'], 'null 数据应该被正确保存');
    }

    /**
     * 测试 buildForm 方法
     */
    public function testBuildForm(): void
    {
        $builder = $this->createMock(FormBuilderInterface::class);

        $callCount = 0;
        $builder->expects($this->exactly(2))
            ->method('add')
            ->willReturnCallback(function ($name, $type, $options) use (&$callCount, $builder) {
                $callCount++;

                if ($callCount === 1 && $name === 'description') {
                    $this->assertSame(TextareaType::class, $type);
                    $this->assertSame('恢复说明', $options['label']);
                    $this->assertFalse($options['required']);
                    $this->assertSame('form-control', $options['attr']['class']);
                    $this->assertSame(3, $options['attr']['rows']);
                    $this->assertSame('请说明恢复情况（可选）', $options['attr']['placeholder']);
                } elseif ($callCount === 2 && $name === 'resume') {
                    $this->assertSame(SubmitType::class, $type);
                    $this->assertSame('确认恢复', $options['label']);
                    $this->assertSame('btn btn-success', $options['attr']['class']);
                }

                return $builder;
            });

        $this->formType->buildForm($builder, []);
        $this->assertSame(2, $callCount, '应该添加了两个字段');
    }

    /**
     * 测试 configureOptions 方法
     */
    public function testConfigureOptions(): void
    {
        $resolver = new OptionsResolver();

        $this->formType->configureOptions($resolver);

        $resolved = $resolver->resolve([]);

        $this->assertArrayHasKey('data_class', $resolved);
        $this->assertNull($resolved['data_class'], 'data_class 应该设置为 null');
    }

    /**
     * 测试表单 getBlockPrefix 方法
     */
    public function testGetBlockPrefix(): void
    {
        $this->assertEquals('task_resume', $this->formType->getBlockPrefix(), 'getBlockPrefix 应该返回 task_resume');
    }
}