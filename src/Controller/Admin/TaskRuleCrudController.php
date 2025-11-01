<?php

declare(strict_types=1);

namespace Tourze\WarehouseOperationBundle\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminCrud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Tourze\WarehouseOperationBundle\Entity\TaskRule;

/**
 * @template TEntity of TaskRule
 * @extends AbstractCrudController<TEntity>
 */
#[AdminCrud(routePath: '/warehouse-operation/task-rule', routeName: 'warehouse_operation_task_rule')]
final class TaskRuleCrudController extends AbstractCrudController
{
    /**
     * @return class-string<TaskRule>
     */
    public static function getEntityFqcn(): string
    {
        return TaskRule::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('任务规则')
            ->setEntityLabelInPlural('任务规则')
            ->setPageTitle('index', '任务规则列表')
            ->setPageTitle('detail', '任务规则详情')
            ->setPageTitle('edit', '编辑任务规则')
            ->setPageTitle('new', '新建任务规则')
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')
            ->hideOnForm()
        ;

        yield TextField::new('name', '规则名称')
            ->setHelp('任务规则名称，必填，最多100个字符')
        ;

        yield ChoiceField::new('ruleType', '规则类型')
            ->setChoices([
                '优先级规则' => 'priority',
                '技能匹配规则' => 'skill_match',
                '负载均衡规则' => 'workload_balance',
                '约束规则' => 'constraint',
                '优化规则' => 'optimization',
            ])
            ->setHelp('任务规则类型，必填')
        ;

        yield TextareaField::new('description', '规则描述')
            ->setHelp('规则详细描述，可选，最多500个字符')
            ->hideOnIndex()
        ;

        yield ArrayField::new('conditions', '规则条件')
            ->setHelp('规则条件的JSON配置')
            ->hideOnIndex()
        ;

        yield ArrayField::new('actions', '规则动作')
            ->setHelp('规则动作的JSON配置')
            ->hideOnIndex()
        ;

        yield IntegerField::new('priority', '规则优先级')
            ->setHelp('规则优先级，范围1-100，数字越大优先级越高')
        ;

        yield BooleanField::new('isActive', '是否启用')
            ->setHelp('是否启用该规则')
        ;

        yield DateField::new('effectiveFrom', '生效开始日期')
            ->setHelp('规则生效开始日期，可选')
        ;

        yield DateField::new('effectiveTo', '生效结束日期')
            ->setHelp('规则生效结束日期，可选')
        ;

        yield TextareaField::new('notes', '备注')
            ->setHelp('规则备注信息，可选，最多1000个字符')
            ->hideOnIndex()
        ;

        yield DateTimeField::new('createTime', '创建时间')
            ->hideOnForm()
        ;

        yield DateTimeField::new('updateTime', '更新时间')
            ->hideOnForm()
        ;

        yield TextField::new('createdBy', '创建者')
            ->hideOnForm()
            ->hideOnIndex()
        ;

        yield TextField::new('updatedBy', '更新者')
            ->hideOnForm()
            ->hideOnIndex()
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('name')
            ->add('ruleType')
            ->add('isActive')
            ->add('priority')
        ;
    }
}
