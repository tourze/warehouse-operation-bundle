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
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\NumericFilter;
use Symfony\Component\HttpFoundation\Response;
use Tourze\WarehouseOperationBundle\Entity\WorkerSkill;

/**
 * 作业员技能管理控制器
 *
 * 提供作业员技能档案的CRUD管理界面，支持技能认证、
 * 技能评估、培训记录等功能。基于EasyAdminBundle构建。
 *
 * @template TEntity of WorkerSkill
 * @extends AbstractCrudController<TEntity>
 */
#[AdminCrud(routePath: '/warehouse-operation/worker-skill', routeName: 'warehouse_operation_worker_skill')]
final class WorkerSkillAdminController extends AbstractCrudController
{
    /**
     * @return class-string<WorkerSkill>
     */
    public static function getEntityFqcn(): string
    {
        return WorkerSkill::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('作业员技能')
            ->setEntityLabelInPlural('作业员技能')
            ->setPageTitle('index', '作业员技能管理')
            ->setPageTitle('detail', '技能详情')
            ->setPageTitle('edit', '编辑技能')
            ->setPageTitle('new', '添加技能')
            ->setDefaultSort(['workerId' => 'ASC', 'skillCategory' => 'ASC', 'skillLevel' => 'DESC'])
            ->setPaginatorPageSize(25)
            ->setSearchFields(['workerId', 'workerName', 'skillCategory', 'notes'])
            ->showEntityActionsInlined()
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')
            ->onlyOnIndex()
        ;

        yield IntegerField::new('workerId', '作业员ID')
            ->setColumns('col-md-3')
            ->setHelp('作业员的唯一标识')
        ;

        yield TextField::new('workerName', '作业员姓名')
            ->setColumns('col-md-3')
            ->setMaxLength(100)
        ;

        yield ChoiceField::new('skillCategory', '技能类别')
            ->setChoices([
                '拣货' => 'picking',
                '包装' => 'packing',
                '质检' => 'quality',
                '盘点' => 'counting',
                '设备操作' => 'equipment',
                '危险品处理' => 'hazardous',
                '冷库作业' => 'cold_storage',
            ])
            ->setColumns('col-md-3')
            ->renderAsBadges([
                'picking' => 'success',
                'packing' => 'info',
                'quality' => 'warning',
                'counting' => 'primary',
                'equipment' => 'secondary',
                'hazardous' => 'danger',
                'cold_storage' => 'light',
            ])
        ;

        yield IntegerField::new('skillLevel', '技能等级')
            ->setColumns('col-md-3')
            ->setHelp('1-10，等级越高越熟练')
            ->setFormTypeOptions(['attr' => ['min' => 1, 'max' => 10]])
        ;

        yield IntegerField::new('skillScore', '技能分数')
            ->setColumns('col-md-4')
            ->setHelp('1-100，综合评估分数')
            ->setFormTypeOptions(['attr' => ['min' => 1, 'max' => 100]])
        ;

        yield BooleanField::new('isCertified', '已认证')
            ->setColumns('col-md-4')
            ->renderAsSwitch()
            ->setHelp('是否通过官方认证')
        ;

        yield BooleanField::new('isActive', '有效状态')
            ->setColumns('col-md-4')
            ->renderAsSwitch()
        ;

        yield DateField::new('certifiedDate', '认证日期')
            ->hideOnIndex()
            ->setRequired(false)
            ->setHelp('技能认证通过的日期')
        ;

        yield DateField::new('expiryDate', '过期日期')
            ->hideOnIndex()
            ->setRequired(false)
            ->setHelp('技能认证的到期日期')
        ;

        yield TextareaField::new('notes', '备注')
            ->hideOnIndex()
            ->setMaxLength(500)
            ->setNumOfRows(3)
            ->setRequired(false)
            ->setHelp('技能相关的备注信息')
        ;

        // 统计字段
        yield IntegerField::new('experienceMonths', '经验月数')
            ->hideOnIndex()
            ->setRequired(false)
            ->setHelp('从事该技能工作的月数')
        ;

        yield IntegerField::new('completedTasks', '完成任务数')
            ->onlyOnDetail()
            ->setHelp('使用该技能完成的任务总数')
        ;

        yield IntegerField::new('errorCount', '错误次数')
            ->onlyOnDetail()
            ->setHelp('操作错误的累计次数')
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
        $certifyAction = Action::new('certify', '认证通过')
            ->linkToCrudAction('certifySkill')
            ->displayIf(fn (WorkerSkill $skill) => !$skill->isCertified() && $skill->isActive())
            ->setCssClass('btn btn-success btn-sm')
        ;

        $revokeCertificationAction = Action::new('revokeCertification', '撤销认证')
            ->linkToCrudAction('revokeCertification')
            ->displayIf(fn (WorkerSkill $skill) => $skill->isCertified())
            ->setCssClass('btn btn-warning btn-sm')
        ;

        $upgradeSkillAction = Action::new('upgradeSkill', '技能升级')
            ->linkToCrudAction('upgradeSkill')
            ->displayIf(fn (WorkerSkill $skill) => $skill->isActive() && $skill->getSkillLevel() < 10)
            ->setCssClass('btn btn-info btn-sm')
        ;

        $viewPerformanceAction = Action::new('viewPerformance', '查看表现')
            ->linkToCrudAction('viewSkillPerformance')
            ->setCssClass('btn btn-outline-secondary btn-sm')
        ;

        return $actions
            ->add(Crud::PAGE_INDEX, $certifyAction)
            ->add(Crud::PAGE_INDEX, $revokeCertificationAction)
            ->add(Crud::PAGE_INDEX, $upgradeSkillAction)
            ->add(Crud::PAGE_INDEX, $viewPerformanceAction)
            ->add(Crud::PAGE_DETAIL, $certifyAction)
            ->add(Crud::PAGE_DETAIL, $revokeCertificationAction)
            ->add(Crud::PAGE_DETAIL, $upgradeSkillAction)
            ->add(Crud::PAGE_DETAIL, $viewPerformanceAction)
            ->add(Crud::PAGE_INDEX, Action::NEW)
            ->add(Crud::PAGE_INDEX, Action::EDIT)
            ->update(
                Crud::PAGE_INDEX,
                Action::NEW,
                fn (Action $action) => $action->setLabel('添加技能')
            )
            ->update(
                Crud::PAGE_INDEX,
                Action::EDIT,
                fn (Action $action) => $action->setLabel('编辑')
            )
            ->set(Crud::PAGE_INDEX, Action::DELETE)
            ->update(
                Crud::PAGE_INDEX,
                Action::DELETE,
                fn (Action $action) => $action->setLabel('删除')
                    ->displayIf(fn (WorkerSkill $skill) => !$skill->isActive())
            )
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(ChoiceFilter::new('skillCategory', '技能类别')
                ->setChoices([
                    '拣货' => 'picking',
                    '包装' => 'packing',
                    '质检' => 'quality',
                    '盘点' => 'counting',
                    '设备操作' => 'equipment',
                    '危险品处理' => 'hazardous',
                    '冷库作业' => 'cold_storage',
                ]))
            ->add(NumericFilter::new('skillLevel', '技能等级'))
            ->add(NumericFilter::new('skillScore', '技能分数'))
            ->add(BooleanFilter::new('isCertified', '已认证'))
            ->add(BooleanFilter::new('isActive', '有效状态'))
            ->add('workerId')
            ->add('workerName')
            ->add(DateTimeFilter::new('certifiedDate', '认证日期'))
            ->add(DateTimeFilter::new('expiryDate', '过期日期'))
            ->add(DateTimeFilter::new('createdAt', '创建时间'))
        ;
    }

    /**
     * 技能认证通过
     */
    public function certifySkill(): Response
    {
        // TODO: 实现技能认证通过逻辑
        // 更新认证状态和认证日期

        return $this->redirectToRoute('admin');
    }

    /**
     * 撤销技能认证
     */
    public function revokeCertification(): Response
    {
        // TODO: 实现撤销认证逻辑
        // 需要记录撤销原因

        return $this->redirectToRoute('admin');
    }

    /**
     * 技能升级
     */
    public function upgradeSkill(): Response
    {
        // TODO: 实现技能升级逻辑
        // 基于表现数据自动或手动升级技能等级

        return $this->redirectToRoute('admin');
    }

    /**
     * 查看技能表现
     */
    public function viewSkillPerformance(): Response
    {
        // TODO: 实现查看技能表现逻辑
        // 展示该技能的历史表现数据和统计

        return $this->redirectToRoute('admin');
    }
}
