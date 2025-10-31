<?php

declare(strict_types=1);

namespace Tourze\WarehouseOperationBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Range;

/**
 * 任务优先级调整表单类型
 */
class TaskPriorityType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('priority', IntegerType::class, [
                'label' => '优先级',
                'help' => '1-100，数值越高优先级越高',
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'min' => 1,
                    'max' => 100,
                    'value' => $options['current_priority'],
                ],
                'constraints' => [
                    new Range([
                        'min' => 1,
                        'max' => 100,
                        'notInRangeMessage' => '优先级必须在 {{ min }}-{{ max }} 之间',
                    ]),
                ],
            ])
            ->add('reason', TextType::class, [
                'label' => '调整原因',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => '请输入调整原因（可选）',
                ],
            ])
            ->add('update', SubmitType::class, [
                'label' => '更新优先级',
                'attr' => [
                    'class' => 'btn btn-warning',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'current_priority' => 1,
            'data_class' => null,
        ]);

        $resolver->setAllowedTypes('current_priority', 'int');
    }
}