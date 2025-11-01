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
use Tourze\WarehouseOperationBundle\Entity\WorkerSkill;

/**
 * @template TEntity of WorkerSkill
 * @extends AbstractCrudController<TEntity>
 */
#[AdminCrud(routePath: '/warehouse-operation/worker-skill-basic', routeName: 'warehouse_operation_worker_skill_basic')]
final class WorkerSkillCrudController extends AbstractCrudController
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
            ->setEntityLabelInSingular('工人技能')
            ->setEntityLabelInPlural('工人技能')
            ->setPageTitle('index', '工人技能列表')
            ->setPageTitle('detail', '工人技能详情')
            ->setPageTitle('edit', '编辑工人技能')
            ->setPageTitle('new', '新建工人技能')
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')
            ->hideOnForm()
        ;

        yield IntegerField::new('workerId', '作业员ID')
            ->setHelp('作业员ID，必填，必须为正数')
        ;

        yield TextField::new('workerName', '作业员姓名')
            ->setHelp('作业员姓名，必填，最多100个字符')
        ;

        yield ChoiceField::new('skillCategory', '技能类别')
            ->setChoices([
                '拣选' => 'picking',
                '包装' => 'packing',
                '质检' => 'quality',
                '盘点' => 'counting',
                '设备操作' => 'equipment',
                '危险品处理' => 'hazardous',
                '冷库作业' => 'cold_storage',
            ])
            ->setHelp('技能类别，必填')
        ;

        yield IntegerField::new('skillLevel', '技能等级')
            ->setHelp('技能等级，范围1-10，数字越大等级越高')
        ;

        yield IntegerField::new('skillScore', '技能分数')
            ->setHelp('技能分数，范围1-100，数字越大技能越强')
        ;

        yield ArrayField::new('certifications', '认证信息')
            ->setHelp('技能认证信息配置')
            ->hideOnIndex()
            ->onlyOnDetail()
        ;

        yield DateField::new('certifiedAt', '认证日期')
            ->setHelp('技能认证日期，可选')
        ;

        yield DateField::new('expiresAt', '认证到期日期')
            ->setHelp('技能认证到期日期，可选')
        ;

        yield BooleanField::new('isActive', '是否启用')
            ->setHelp('是否启用该技能档案')
        ;

        yield TextareaField::new('notes', '备注')
            ->setHelp('技能备注信息，可选，最多500个字符')
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
            ->add('workerId')
            ->add('workerName')
            ->add('skillCategory')
            ->add('skillLevel')
            ->add('isActive')
        ;
    }
}
