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
use Tourze\WarehouseOperationBundle\Entity\Shelf;

/**
 * @template TEntity of Shelf
 * @extends AbstractCrudController<TEntity>
 */
#[AdminCrud(routePath: '/warehouse-operation/shelf', routeName: 'warehouse_operation_shelf')]
final class ShelfCrudController extends AbstractCrudController
{
    /**
     * @return class-string<Shelf>
     */
    public static function getEntityFqcn(): string
    {
        return Shelf::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('货架')
            ->setEntityLabelInPlural('货架')
            ->setPageTitle('index', '货架列表')
            ->setPageTitle('detail', '货架详情')
            ->setPageTitle('edit', '编辑货架')
            ->setPageTitle('new', '新建货架')
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')
            ->hideOnForm()
        ;

        yield AssociationField::new('zone', '库区')
            ->setHelp('所属库区，可选')
        ;

        yield TextField::new('title', '货架名称')
            ->setHelp('货架名称，必填，最多100个字符')
        ;

        yield AssociationField::new('locations', '存储位置')
            ->setHelp('货架下的存储位置列表')
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
            ->add('zone')
            ->add('title')
        ;
    }
}
