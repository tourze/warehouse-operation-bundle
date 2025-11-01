<?php

declare(strict_types=1);

namespace Tourze\WarehouseOperationBundle\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminCrud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Tourze\WarehouseOperationBundle\Entity\QualityStandard;

/**
 * @template TEntity of QualityStandard
 * @extends AbstractCrudController<TEntity>
 */
#[AdminCrud(routePath: '/warehouse-operation/quality-standard-basic', routeName: 'warehouse_operation_quality_standard_basic')]
final class QualityStandardCrudController extends AbstractCrudController
{
    /**
     * @return class-string<QualityStandard>
     */
    public static function getEntityFqcn(): string
    {
        return QualityStandard::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('质量标准')
            ->setEntityLabelInPlural('质量标准')
            ->setPageTitle('index', '质量标准列表')
            ->setPageTitle('detail', '质量标准详情')
            ->setPageTitle('edit', '编辑质量标准')
            ->setPageTitle('new', '新建质量标准')
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')
            ->hideOnForm()
        ;

        yield TextField::new('name', '标准名称')
            ->setHelp('质量标准名称，必填，最多100个字符')
        ;

        yield TextField::new('productCategory', '商品类别')
            ->setHelp('适用的商品类别，必填，最多50个字符')
        ;

        yield TextareaField::new('description', '标准描述')
            ->setHelp('质量标准详细描述，可选，最多1000个字符')
            ->hideOnIndex()
        ;

        yield ArrayField::new('checkItems', '质检项目配置')
            ->setHelp('质检项目的JSON配置')
            ->hideOnIndex()
        ;

        yield BooleanField::new('isActive', '是否启用')
            ->setHelp('是否启用该质量标准')
        ;

        yield IntegerField::new('priority', '优先级')
            ->setHelp('优先级，范围1-100，数字越大优先级越高')
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
            ->add('productCategory')
            ->add('isActive')
            ->add('priority')
        ;
    }
}
