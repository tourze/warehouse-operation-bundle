<?php

declare(strict_types=1);

namespace Tourze\WarehouseOperationBundle\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminCrud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Tourze\WarehouseOperationBundle\Entity\Zone;

/**
 * @template TEntity of Zone
 * @extends AbstractCrudController<TEntity>
 */
#[AdminCrud(routePath: '/warehouse-operation/zone', routeName: 'warehouse_operation_zone')]
final class ZoneCrudController extends AbstractCrudController
{
    /**
     * @return class-string<Zone>
     */
    public static function getEntityFqcn(): string
    {
        return Zone::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('库区')
            ->setEntityLabelInPlural('库区')
            ->setPageTitle('index', '库区列表')
            ->setPageTitle('detail', '库区详情')
            ->setPageTitle('edit', '编辑库区')
            ->setPageTitle('new', '新建库区')
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')
            ->hideOnForm()
        ;

        yield AssociationField::new('warehouse', '仓库')
            ->setHelp('所属仓库，必填')
        ;

        yield TextField::new('title', '库区名称')
            ->setHelp('库区名称，必填，最多60个字符')
        ;

        yield NumberField::new('acreage', '面积')
            ->setHelp('库区面积，可选，精确到小数点后2位')
        ;

        yield TextField::new('type', '类型')
            ->setHelp('库区类型，必填，最多60个字符')
        ;

        yield AssociationField::new('shelves', '货架')
            ->setHelp('库区下的货架列表')
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
            ->add('warehouse')
            ->add('title')
            ->add('type')
        ;
    }
}
