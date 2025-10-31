<?php

declare(strict_types=1);

namespace Tourze\WarehouseOperationBundle\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminCrud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Tourze\EasyAdminEnumFieldBundle\Field\EnumField;
use Tourze\WarehouseOperationBundle\Entity\CountTask;
use Tourze\WarehouseOperationBundle\Enum\TaskStatus;

/**
 * @template TEntity of CountTask
 * @extends AbstractCrudController<TEntity>
 */
#[AdminCrud(routePath: '/warehouse-operation/count-task', routeName: 'warehouse_operation_count_task')]
final class CountTaskCrudController extends AbstractCrudController
{
    /**
     * @return class-string<CountTask>
     */
    public static function getEntityFqcn(): string
    {
        return CountTask::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('盘点任务')
            ->setEntityLabelInPlural('盘点任务')
            ->setPageTitle('index', '盘点任务列表')
            ->setPageTitle('detail', '盘点任务详情')
            ->setPageTitle('edit', '编辑盘点任务')
            ->setPageTitle('new', '新建盘点任务')
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')
            ->hideOnForm()
        ;

        $statusField = EnumField::new('status', '任务状态');
        $statusField->setEnumCases(TaskStatus::cases());
        yield $statusField->setHelp('盘点任务状态，默认为待分配');

        yield IntegerField::new('priority', '优先级')
            ->setHelp('任务优先级，范围1-100，默认为1')
        ;

        yield IntegerField::new('countPlanId', '盘点计划ID')
            ->setHelp('关联的盘点计划ID，可选')
        ;

        yield IntegerField::new('taskSequence', '任务序列')
            ->setHelp('盘点任务序列号，可选')
        ;

        yield TextField::new('locationCode', '库位编码')
            ->setHelp('盘点库位编码，可选，最多50个字符')
        ;

        yield NumberField::new('accuracy', '盘点准确率')
            ->setHelp('盘点准确率(百分比)，范围0-100')
            ->setNumDecimals(2)
        ;

        yield ArrayField::new('data', '任务数据')
            ->setHelp('盘点任务相关的JSON数据')
            ->hideOnIndex()
        ;

        yield IntegerField::new('assignedWorker', '分配的作业员ID')
            ->setHelp('分配的作业员ID，可选')
        ;

        yield DateTimeField::new('assignedAt', '分配时间')
            ->setHelp('任务分配时间')
            ->hideOnForm()
        ;

        yield DateTimeField::new('startedAt', '开始时间')
            ->setHelp('任务开始时间')
            ->hideOnForm()
        ;

        yield DateTimeField::new('completedAt', '完成时间')
            ->setHelp('任务完成时间')
            ->hideOnForm()
        ;

        yield TextareaField::new('notes', '备注')
            ->setHelp('任务备注信息，可选，最多1000个字符')
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
            ->add('status')
            ->add('priority')
            ->add('countPlanId')
            ->add('locationCode')
            ->add('assignedWorker')
        ;
    }
}
