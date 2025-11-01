<?php

declare(strict_types=1);

namespace Tourze\WarehouseOperationBundle\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminCrud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Tourze\WarehouseOperationBundle\Entity\Warehouse;

/**
 * @template TEntity of Warehouse
 * @extends AbstractCrudController<TEntity>
 */
#[AdminCrud(routePath: '/warehouse-operation/warehouse', routeName: 'warehouse_operation_warehouse')]
final class WarehouseCrudController extends AbstractCrudController
{
    /**
     * @return class-string<Warehouse>
     */
    public static function getEntityFqcn(): string
    {
        return Warehouse::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('仓库')
            ->setEntityLabelInPlural('仓库')
            ->setPageTitle('index', '仓库列表')
            ->setPageTitle('detail', '仓库详情')
            ->setPageTitle('edit', '编辑仓库')
            ->setPageTitle('new', '新建仓库')
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')
            ->hideOnForm()
        ;

        yield TextField::new('code', '代号')
            ->setHelp('仓库代号，必填，最多64个字符，必须唯一')
        ;

        yield TextField::new('name', '名称')
            ->setHelp('仓库名称，必填，最多100个字符')
        ;

        yield TextField::new('contactName', '联系人')
            ->setHelp('联系人姓名，可选，最多60个字符')
        ;

        yield TextField::new('contactTel', '联系电话')
            ->setHelp('联系电话，可选，最多120个字符')
        ;

        yield AssociationField::new('zones', '库区')
            ->setHelp('仓库下的库区列表')
            ->hideOnIndex()
            ->hideOnForm()
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
            ->add('code')
            ->add('name')
            ->add('contactName')
        ;
    }
}
