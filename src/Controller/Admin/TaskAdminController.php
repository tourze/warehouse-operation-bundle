<?php

namespace Tourze\WarehouseOperationBundle\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminCrud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\NumericFilter;
use Symfony\Component\HttpFoundation\Response;
use Tourze\WarehouseOperationBundle\Entity\WarehouseTask;
use Tourze\WarehouseOperationBundle\Enum\TaskStatus;
use Tourze\WarehouseOperationBundle\Enum\TaskType;

/**
 * 仓库任务管理控制器
 *
 * 提供仓库任务的完整CRUD管理界面，支持任务状态管理、优先级调整、
 * 作业员分配等核心功能。基于EasyAdminBundle构建管理界面。
 *
 * @template TEntity of WarehouseTask
 * @extends AbstractCrudController<TEntity>
 */
#[AdminCrud(routePath: '/warehouse-operation/task', routeName: 'warehouse_operation_task')]
final class TaskAdminController extends AbstractCrudController
{
    /**
     * @return class-string<WarehouseTask>
     */
    public static function getEntityFqcn(): string
    {
        return WarehouseTask::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('仓库任务')
            ->setEntityLabelInPlural('仓库任务')
            ->setPageTitle('index', '仓库任务管理')
            ->setPageTitle('detail', '任务详情')
            ->setPageTitle('edit', '编辑任务')
            ->setPageTitle('new', '创建任务')
            ->setDefaultSort(['priority' => 'DESC', 'createdAt' => 'DESC'])
            ->setPaginatorPageSize(25)
            ->setSearchFields(['id', 'description', 'location', 'assignedWorker'])
            ->showEntityActionsInlined()
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')
            ->onlyOnIndex()
        ;

        yield ChoiceField::new('type', '任务类型')
            ->setChoices([
                '入库任务' => TaskType::INBOUND,
                '出库任务' => TaskType::OUTBOUND,
                '质检任务' => TaskType::QUALITY,
                '盘点任务' => TaskType::COUNT,
                '调拨任务' => TaskType::TRANSFER,
            ])
            ->setFormTypeOptions([
                'choice_label' => fn (TaskType $choice) => match ($choice) {
                    TaskType::INBOUND => '入库任务',
                    TaskType::OUTBOUND => '出库任务',
                    TaskType::QUALITY => '质检任务',
                    TaskType::COUNT => '盘点任务',
                    TaskType::TRANSFER => '调拨任务',
                },
            ])
            ->hideOnForm()
        ;

        yield ChoiceField::new('status', '任务状态')
            ->setChoices([
                '待处理' => TaskStatus::PENDING,
                '已分配' => TaskStatus::ASSIGNED,
                '进行中' => TaskStatus::IN_PROGRESS,
                '已暂停' => TaskStatus::PAUSED,
                '已完成' => TaskStatus::COMPLETED,
                '已取消' => TaskStatus::CANCELLED,
                '已失败' => TaskStatus::FAILED,
                '发现差异' => TaskStatus::DISCREPANCY_FOUND,
            ])
            ->setFormTypeOptions([
                'choice_label' => fn (TaskStatus $choice) => match ($choice) {
                    TaskStatus::PENDING => '待处理',
                    TaskStatus::ASSIGNED => '已分配',
                    TaskStatus::IN_PROGRESS => '进行中',
                    TaskStatus::PAUSED => '已暂停',
                    TaskStatus::COMPLETED => '已完成',
                    TaskStatus::CANCELLED => '已取消',
                    TaskStatus::FAILED => '已失败',
                    TaskStatus::DISCREPANCY_FOUND => '发现差异',
                },
            ])
            ->renderAsBadges([
                TaskStatus::PENDING->value => 'secondary',
                TaskStatus::ASSIGNED->value => 'info',
                TaskStatus::IN_PROGRESS->value => 'primary',
                TaskStatus::PAUSED->value => 'warning',
                TaskStatus::COMPLETED->value => 'success',
                TaskStatus::CANCELLED->value => 'secondary',
                TaskStatus::FAILED->value => 'danger',
                TaskStatus::DISCREPANCY_FOUND->value => 'warning',
            ])
        ;

        yield IntegerField::new('priority', '优先级')
            ->setHelp('1-100，数值越高优先级越高')
            ->setFormTypeOptions(['attr' => ['min' => 1, 'max' => 100]])
        ;

        yield TextField::new('description', '任务描述')
            ->setMaxLength(255)
        ;

        yield TextField::new('location', '作业位置')
            ->setMaxLength(100)
        ;

        yield IntegerField::new('assignedWorker', '分配作业员ID')
            ->hideOnIndex()
            ->setRequired(false)
        ;

        yield TextareaField::new('taskData', '任务数据')
            ->hideOnIndex()
            ->setHelp('任务相关的JSON数据')
            ->setRequired(false)
        ;

        yield BooleanField::new('isEmergency', '紧急任务')
            ->hideOnIndex()
        ;

        yield DateTimeField::new('dueDate', '到期时间')
            ->hideOnIndex()
            ->setRequired(false)
        ;

        yield DateTimeField::new('estimatedStartTime', '预计开始时间')
            ->hideOnIndex()
            ->setRequired(false)
        ;

        yield DateTimeField::new('estimatedEndTime', '预计结束时间')
            ->hideOnIndex()
            ->setRequired(false)
        ;

        yield DateTimeField::new('actualStartTime', '实际开始时间')
            ->onlyOnDetail()
            ->setRequired(false)
        ;

        yield DateTimeField::new('actualEndTime', '实际结束时间')
            ->onlyOnDetail()
            ->setRequired(false)
        ;

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
        $assignWorkerAction = Action::new('assignWorker', '分配作业员')
            ->linkToCrudAction('assignWorker')
            ->displayIf(
                fn (WarehouseTask $task) => in_array($task->getStatus(), [TaskStatus::PENDING, TaskStatus::ASSIGNED], true)
            )
        ;

        $changePriorityAction = Action::new('changePriority', '调整优先级')
            ->linkToCrudAction('changePriority')
            ->displayIf(
                fn (WarehouseTask $task) => TaskStatus::COMPLETED !== $task->getStatus()
                && TaskStatus::CANCELLED !== $task->getStatus()
            )
        ;

        $pauseAction = Action::new('pause', '暂停任务')
            ->linkToCrudAction('pauseTask')
            ->displayIf(
                fn (WarehouseTask $task) => TaskStatus::IN_PROGRESS === $task->getStatus()
            )
        ;

        $resumeAction = Action::new('resume', '恢复任务')
            ->linkToCrudAction('resumeTask')
            ->displayIf(
                fn (WarehouseTask $task) => TaskStatus::PAUSED === $task->getStatus()
            )
        ;

        return $actions
            ->add(Crud::PAGE_INDEX, $assignWorkerAction)
            ->add(Crud::PAGE_INDEX, $changePriorityAction)
            ->add(Crud::PAGE_INDEX, $pauseAction)
            ->add(Crud::PAGE_INDEX, $resumeAction)
            ->add(Crud::PAGE_DETAIL, $assignWorkerAction)
            ->add(Crud::PAGE_DETAIL, $changePriorityAction)
            ->add(Crud::PAGE_DETAIL, $pauseAction)
            ->add(Crud::PAGE_DETAIL, $resumeAction)
            ->set(Crud::PAGE_INDEX, Action::DELETE)
            ->update(
                Crud::PAGE_INDEX,
                Action::NEW,
                fn (Action $action) => $action->setLabel('创建任务')
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
            )
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(ChoiceFilter::new('type', '任务类型')
                ->setChoices([
                    '入库任务' => TaskType::INBOUND,
                    '出库任务' => TaskType::OUTBOUND,
                    '质检任务' => TaskType::QUALITY,
                    '盘点任务' => TaskType::COUNT,
                    '调拨任务' => TaskType::TRANSFER,
                ]))
            ->add(ChoiceFilter::new('status', '任务状态')
                ->setChoices([
                    '待处理' => TaskStatus::PENDING,
                    '已分配' => TaskStatus::ASSIGNED,
                    '进行中' => TaskStatus::IN_PROGRESS,
                    '已暂停' => TaskStatus::PAUSED,
                    '已完成' => TaskStatus::COMPLETED,
                    '已取消' => TaskStatus::CANCELLED,
                    '已失败' => TaskStatus::FAILED,
                ]))
            ->add(NumericFilter::new('priority', '优先级'))
            ->add('assignedWorker')
            ->add('location')
            ->add('isEmergency')
            ->add(DateTimeFilter::new('createdAt', '创建时间'))
            ->add(DateTimeFilter::new('dueDate', '到期时间'))
        ;
    }

    /**
     * 分配作业员操作
     */
    public function assignWorker(): Response
    {
        // TODO: 实现作业员分配逻辑
        // 可以集成 TaskSchedulingService 的 assignWorkerBySkill 方法

        return $this->redirectToRoute('admin');
    }

    /**
     * 调整优先级操作
     */
    public function changePriority(): Response
    {
        // TODO: 实现优先级调整逻辑
        // 可以集成 TaskSchedulingService 的 recalculatePriorities 方法

        return $this->redirectToRoute('admin');
    }

    /**
     * 暂停任务操作
     */
    public function pauseTask(): Response
    {
        // TODO: 实现任务暂停逻辑

        return $this->redirectToRoute('admin');
    }

    /**
     * 恢复任务操作
     */
    public function resumeTask(): Response
    {
        // TODO: 实现任务恢复逻辑

        return $this->redirectToRoute('admin');
    }

    /**
     * 获取可用作业员列表
     *
     * @return array<int, string>
     */
    public function getAvailableWorkers(): array
    {
        // TODO: 实现获取可用作业员逻辑
        // 返回测试数据格式：[1 => '张三', 2 => '李四', 3 => '王五']
        return [
            1 => '张三',
            2 => '李四',
            3 => '王五',
        ];
    }
}
