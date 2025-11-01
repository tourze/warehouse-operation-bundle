<?php

declare(strict_types=1);

namespace Tourze\WarehouseOperationBundle\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminCrud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Tourze\EasyAdminEnumFieldBundle\Field\EnumField;
use Tourze\WarehouseOperationBundle\Entity\InboundTask;
use Tourze\WarehouseOperationBundle\Enum\TaskStatus;

/**
 * @template TEntity of InboundTask
 * @extends AbstractCrudController<TEntity>
 */
#[AdminCrud(routePath: '/warehouse-operation/inbound-task', routeName: 'warehouse_operation_inbound_task')]
final class InboundTaskCrudController extends AbstractCrudController
{
    /**
     * @return class-string<InboundTask>
     */
    public static function getEntityFqcn(): string
    {
        return InboundTask::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('入库任务')
            ->setEntityLabelInPlural('入库任务')
            ->setPageTitle('index', '入库任务列表')
            ->setPageTitle('detail', '入库任务详情')
            ->setPageTitle('edit', '编辑入库任务')
            ->setPageTitle('new', '新建入库任务')
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')
            ->hideOnForm()
        ;

        $statusField = EnumField::new('status', '任务状态');
        $statusField->setEnumCases(TaskStatus::cases());
        yield $statusField->setHelp('入库任务状态，默认为待分配');

        yield IntegerField::new('priority', '优先级')
            ->setHelp('任务优先级，范围1-100，默认为1')
        ;

        yield ArrayField::new('data', '任务数据')
            ->setHelp('入库任务相关的JSON数据')
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
            ->add('assignedWorker')
        ;
    }
}
