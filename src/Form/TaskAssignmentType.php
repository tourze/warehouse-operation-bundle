<?php

declare(strict_types=1);

namespace Tourze\WarehouseOperationBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * 任务分配表单类型
 */
class TaskAssignmentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('workerId', ChoiceType::class, [
                'label' => '选择作业员',
                'choices' => $options['worker_choices'],
                'placeholder' => '请选择作业员',
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                ],
            ])
            ->add('assign', SubmitType::class, [
                'label' => '确认分配',
                'attr' => [
                    'class' => 'btn btn-primary',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'worker_choices' => [],
            'data_class' => null,
        ]);

        $resolver->setAllowedTypes('worker_choices', 'array');
    }
}