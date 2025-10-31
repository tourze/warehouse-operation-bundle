<?php

namespace Tourze\WarehouseOperationBundle\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminCrud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CodeEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use Symfony\Component\HttpFoundation\Response;
use Tourze\WarehouseOperationBundle\Entity\CountPlan;

/**
 * 盘点计划管理控制器
 *
 * 提供盘点计划的CRUD管理界面，支持计划执行监控、
 * 多种盘点模式配置等功能。基于EasyAdminBundle构建。
 *
 * @template TEntity of CountPlan
 * @extends AbstractCrudController<TEntity>
 */
#[AdminCrud(routePath: '/warehouse-operation/count-plan', routeName: 'warehouse_operation_count_plan')]
final class CountPlanAdminController extends AbstractCrudController
{
    /**
     * @return class-string<CountPlan>
     */
    public static function getEntityFqcn(): string
    {
        return CountPlan::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('盘点计划')
            ->setEntityLabelInPlural('盘点计划')
            ->setPageTitle('index', '盘点计划管理')
            ->setPageTitle('detail', '盘点计划详情')
            ->setPageTitle('edit', '编辑盘点计划')
            ->setPageTitle('new', '创建盘点计划')
            ->setDefaultSort(['isActive' => 'DESC', 'priority' => 'DESC', 'nextExecutionTime' => 'ASC'])
            ->setPaginatorPageSize(20)
            ->setSearchFields(['name', 'countType', 'description'])
            ->showEntityActionsInlined()
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')
            ->onlyOnIndex()
        ;

        yield TextField::new('name', '计划名称')
            ->setColumns('col-md-6')
            ->setHelp('盘点计划的显示名称')
        ;

        yield ChoiceField::new('countType', '盘点类型')
            ->setChoices([
                '全盘' => 'full',
                '循环盘' => 'cycle',
                'ABC盘点' => 'abc',
                '随机盘' => 'random',
                '抽盘' => 'spot',
            ])
            ->setColumns('col-md-6')
            ->renderAsBadges([
                'full' => 'primary',
                'cycle' => 'success',
                'abc' => 'info',
                'random' => 'warning',
                'spot' => 'secondary',
            ])
        ;

        yield TextareaField::new('description', '计划描述')
            ->setColumns('col-md-12')
            ->setMaxLength(1000)
            ->setNumOfRows(3)
            ->hideOnIndex()
            ->setHelp('详细描述盘点计划的目标和执行策略')
        ;

        yield CodeEditorField::new('scope', '盘点范围配置')
            ->hideOnIndex()
            ->setLanguage('javascript')
            ->setColumns('col-md-12')
            ->setHelp('JSON格式配置盘点范围，例如：{"zones": ["A01", "A02"], "categories": ["electronics"], "value_threshold": 1000}')
            ->setFormTypeOptions([
                'attr' => [
                    'data-ea-json-field' => 'true',
                    'rows' => 8,
                ],
            ])
        ;

        yield CodeEditorField::new('schedule', '执行计划配置')
            ->hideOnIndex()
            ->setLanguage('javascript')
            ->setColumns('col-md-12')
            ->setHelp('JSON格式配置执行计划，例如：{"frequency": "weekly", "day_of_week": "monday", "start_time": "08:00"}')
            ->setFormTypeOptions([
                'attr' => [
                    'data-ea-json-field' => 'true',
                    'rows' => 6,
                ],
            ])
        ;

        yield IntegerField::new('priority', '优先级')
            ->setColumns('col-md-4')
            ->setHelp('数值越高优先级越高')
            ->setFormTypeOptions(['attr' => ['min' => 1, 'max' => 100]])
        ;

        yield BooleanField::new('isActive', '启用状态')
            ->setColumns('col-md-4')
            ->renderAsSwitch()
        ;

        yield BooleanField::new('autoExecute', '自动执行')
            ->setColumns('col-md-4')
            ->hideOnIndex()
            ->renderAsSwitch()
            ->setHelp('是否按计划自动执行盘点任务')
        ;

        yield DateTimeField::new('nextExecutionTime', '下次执行时间')
            ->setColumns('col-md-6')
            ->setRequired(false)
            ->setHelp('计划下次自动执行的时间')
        ;

        yield DateTimeField::new('lastExecutionTime', '上次执行时间')
            ->setColumns('col-md-6')
            ->onlyOnDetail()
            ->setRequired(false)
        ;

        yield IntegerField::new('executionCount', '执行次数')
            ->hideOnIndex()
            ->onlyOnDetail()
            ->setHelp('计划已执行的次数')
        ;

        yield NumberField::new('estimatedDurationHours', '预计耗时(小时)')
            ->hideOnIndex()
            ->setNumDecimals(1)
            ->setFormTypeOptions(['attr' => ['min' => 0.1, 'max' => 168, 'step' => 0.1]])
        ;

        // 审计字段
        yield DateTimeField::new('createdAt', '创建时间')
            ->onlyOnDetail()
        ;

        yield DateTimeField::new('updatedAt', '更新时间')
            ->onlyOnDetail()
        ;

        yield TextField::new('createdBy', '创建人')
            ->onlyOnDetail()
        ;

        yield TextField::new('updatedBy', '更新人')
            ->onlyOnDetail()
        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        $executeNowAction = Action::new('executeNow', '立即执行')
            ->linkToCrudAction('executeCountPlan')
            ->displayIf(fn (CountPlan $plan) => $plan->isActive())
            ->setCssClass('btn btn-primary btn-sm')
        ;

        $activateAction = Action::new('activate', '启用')
            ->linkToCrudAction('activatePlan')
            ->displayIf(fn (CountPlan $plan) => !$plan->isActive())
            ->setCssClass('btn btn-success btn-sm')
        ;

        $deactivateAction = Action::new('deactivate', '停用')
            ->linkToCrudAction('deactivatePlan')
            ->displayIf(fn (CountPlan $plan) => $plan->isActive())
            ->setCssClass('btn btn-warning btn-sm')
        ;

        $duplicateAction = Action::new('duplicate', '复制计划')
            ->linkToCrudAction('duplicatePlan')
            ->setCssClass('btn btn-info btn-sm')
        ;

        $viewHistoryAction = Action::new('viewHistory', '执行历史')
            ->linkToCrudAction('viewExecutionHistory')
            ->setCssClass('btn btn-outline-secondary btn-sm')
        ;

        return $actions
            ->add(Crud::PAGE_INDEX, $executeNowAction)
            ->add(Crud::PAGE_INDEX, $activateAction)
            ->add(Crud::PAGE_INDEX, $deactivateAction)
            ->add(Crud::PAGE_INDEX, $duplicateAction)
            ->add(Crud::PAGE_INDEX, $viewHistoryAction)
            ->add(Crud::PAGE_DETAIL, $executeNowAction)
            ->add(Crud::PAGE_DETAIL, $activateAction)
            ->add(Crud::PAGE_DETAIL, $deactivateAction)
            ->add(Crud::PAGE_DETAIL, $duplicateAction)
            ->add(Crud::PAGE_DETAIL, $viewHistoryAction)
            ->add(Crud::PAGE_INDEX, Action::NEW)
            ->add(Crud::PAGE_INDEX, Action::EDIT)
            ->set(Crud::PAGE_INDEX, Action::DELETE)
            ->update(
                Crud::PAGE_INDEX,
                Action::NEW,
                fn (Action $action) => $action->setLabel('创建计划')
            )
            ->update(
                Crud::PAGE_INDEX,
                Action::EDIT,
                fn (Action $action) => $action->setLabel('编辑')
            )
            ->update(
                Crud::PAGE_INDEX,
                Action::DELETE,
                fn (Action $action) => $action->setLabel('删除')
                    ->displayIf(fn (CountPlan $plan) => !$plan->isActive())
            )
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(ChoiceFilter::new('countType', '盘点类型')
                ->setChoices([
                    '全盘' => 'full',
                    '循环盘' => 'cycle',
                    'ABC盘点' => 'abc',
                    '随机盘' => 'random',
                    '抽盘' => 'spot',
                ]))
            ->add(BooleanFilter::new('isActive', '启用状态'))
            ->add(BooleanFilter::new('autoExecute', '自动执行'))
            ->add('name')
            ->add(DateTimeFilter::new('nextExecutionTime', '下次执行时间'))
            ->add(DateTimeFilter::new('lastExecutionTime', '上次执行时间'))
            ->add(DateTimeFilter::new('createdAt', '创建时间'))
        ;
    }

    /**
     * 立即执行盘点计划
     */
    public function executeCountPlan(): Response
    {
        // TODO: 实现立即执行盘点计划逻辑
        // 可以集成 InventoryCountService 的 generateCountPlan 方法

        return $this->redirectToRoute('admin');
    }

    /**
     * 启用盘点计划
     */
    public function activatePlan(): Response
    {
        // TODO: 实现启用计划逻辑
        // 需要验证计划配置完整性

        return $this->redirectToRoute('admin');
    }

    /**
     * 停用盘点计划
     */
    public function deactivatePlan(): Response
    {
        // TODO: 实现停用计划逻辑
        // 需要处理正在执行的任务

        return $this->redirectToRoute('admin');
    }

    /**
     * 复制盘点计划
     */
    public function duplicatePlan(): Response
    {
        // TODO: 实现计划复制逻辑
        // 复制现有计划并修改名称和计划时间

        return $this->redirectToRoute('admin');
    }

    /**
     * 查看执行历史
     */
    public function viewExecutionHistory(): Response
    {
        // TODO: 实现执行历史查看逻辑
        // 展示计划的历史执行记录

        return $this->redirectToRoute('admin');
    }
}
