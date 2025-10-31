<?php

declare(strict_types=1);

namespace Tourze\WarehouseOperationBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * 任务暂停表单类型
 */
class TaskPauseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('reason', ChoiceType::class, [
                'label' => '暂停原因',
                'choices' => [
                    '设备故障' => 'equipment_failure',
                    '物料不足' => 'material_shortage',
                    '人员调度' => 'personnel_dispatch',
                    '质量问题' => 'quality_issue',
                    '紧急插单' => 'urgent_insertion',
                    '等待指示' => 'waiting_instruction',
                    '其他原因' => 'other',
                ],
                'placeholder' => '请选择暂停原因',
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => '请选择暂停原因',
                    ]),
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => '详细说明',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3,
                    'placeholder' => '请详细说明暂停情况（可选）',
                ],
                'constraints' => [
                    new Length([
                        'max' => 500,
                        'maxMessage' => '详细说明不能超过500个字符',
                    ]),
                ],
            ])
            ->add('pause', SubmitType::class, [
                'label' => '确认暂停',
                'attr' => [
                    'class' => 'btn btn-warning',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
        ]);
    }
}