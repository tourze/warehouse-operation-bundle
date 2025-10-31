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
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use Symfony\Component\HttpFoundation\Response;
use Tourze\WarehouseOperationBundle\Entity\QualityStandard;

/**
 * 质检标准管理控制器
 *
 * 提供质检标准的CRUD管理界面，支持动态质检规则配置、
 * 多维度质检项目管理等功能。基于EasyAdminBundle构建。
 *
 * @template TEntity of QualityStandard
 * @extends AbstractCrudController<TEntity>
 */
#[AdminCrud(routePath: '/warehouse-operation/quality-standard', routeName: 'warehouse_operation_quality_standard')]
final class QualityStandardAdminController extends AbstractCrudController
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
            ->setEntityLabelInSingular('质检标准')
            ->setEntityLabelInPlural('质检标准')
            ->setPageTitle('index', '质检标准管理')
            ->setPageTitle('detail', '质检标准详情')
            ->setPageTitle('edit', '编辑质检标准')
            ->setPageTitle('new', '创建质检标准')
            ->setDefaultSort(['isActive' => 'DESC', 'priority' => 'DESC', 'createdAt' => 'DESC'])
            ->setPaginatorPageSize(20)
            ->setSearchFields(['name', 'productCategory', 'description'])
            ->showEntityActionsInlined()
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')
            ->onlyOnIndex()
        ;

        yield TextField::new('name', '标准名称')
            ->setColumns('col-md-6')
            ->setHelp('质检标准的显示名称')
        ;

        yield ChoiceField::new('productCategory', '商品类别')
            ->setChoices([
                '食品' => 'food',
                '电子产品' => 'electronics',
                '服装' => 'clothing',
                '化妆品' => 'cosmetics',
                '医药用品' => 'medicine',
                '危险品' => 'hazardous',
                '冷链商品' => 'cold_storage',
                '贵重品' => 'valuables',
                '其他' => 'others',
            ])
            ->setColumns('col-md-6')
            ->allowMultipleChoices(false)
        ;

        yield TextareaField::new('description', '标准描述')
            ->setColumns('col-md-12')
            ->setMaxLength(1000)
            ->setNumOfRows(3)
            ->hideOnIndex()
            ->setHelp('详细描述质检标准的适用范围和要求')
        ;

        yield CodeEditorField::new('checkItems', '质检项目配置')
            ->hideOnIndex()
            ->setLanguage('javascript')
            ->setColumns('col-md-12')
            ->setHelp('JSON格式配置质检项目，例如：{"appearance": {"required": true, "weight": 0.3}, "quantity": {"required": true, "weight": 0.4}}')
            ->setFormTypeOptions([
                'attr' => [
                    'data-ea-json-field' => 'true',
                    'rows' => 10,
                ],
            ])
        ;

        yield NumberField::new('priority', '优先级')
            ->setColumns('col-md-3')
            ->setHelp('数值越高优先级越高')
            ->setFormTypeOptions(['attr' => ['min' => 1, 'max' => 100]])
        ;

        yield BooleanField::new('isActive', '启用状态')
            ->setColumns('col-md-3')
            ->renderAsSwitch()
        ;

        yield BooleanField::new('requireSampling', '需要抽检')
            ->setColumns('col-md-3')
            ->hideOnIndex()
            ->renderAsSwitch()
            ->setHelp('是否需要进行样品抽检')
        ;

        yield NumberField::new('samplingRate', '抽检率')
            ->setColumns('col-md-3')
            ->hideOnIndex()
            ->setNumDecimals(2)
            ->setHelp('抽检比例，0.1表示10%')
            ->setFormTypeOptions(['attr' => ['min' => 0, 'max' => 1, 'step' => 0.01]])
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
        $activateAction = Action::new('activate', '启用')
            ->linkToCrudAction('activateStandard')
            ->displayIf(fn (QualityStandard $standard) => !$standard->isActive())
            ->setCssClass('btn btn-success btn-sm')
        ;

        $deactivateAction = Action::new('deactivate', '停用')
            ->linkToCrudAction('deactivateStandard')
            ->displayIf(fn (QualityStandard $standard) => $standard->isActive())
            ->setCssClass('btn btn-warning btn-sm')
        ;

        $duplicateAction = Action::new('duplicate', '复制标准')
            ->linkToCrudAction('duplicateStandard')
            ->setCssClass('btn btn-info btn-sm')
        ;

        return $actions
            ->add(Crud::PAGE_INDEX, $activateAction)
            ->add(Crud::PAGE_INDEX, $deactivateAction)
            ->add(Crud::PAGE_INDEX, $duplicateAction)
            ->add(Crud::PAGE_DETAIL, $activateAction)
            ->add(Crud::PAGE_DETAIL, $deactivateAction)
            ->add(Crud::PAGE_DETAIL, $duplicateAction)
            ->add(Crud::PAGE_INDEX, Action::NEW)
            ->add(Crud::PAGE_INDEX, Action::EDIT)
            ->set(Crud::PAGE_INDEX, Action::DELETE)
            ->update(
                Crud::PAGE_INDEX,
                Action::NEW,
                fn (Action $action) => $action->setLabel('创建标准')
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
            ->add(ChoiceFilter::new('productCategory', '商品类别')
                ->setChoices([
                    '食品' => 'food',
                    '电子产品' => 'electronics',
                    '服装' => 'clothing',
                    '化妆品' => 'cosmetics',
                    '医药用品' => 'medicine',
                    '危险品' => 'hazardous',
                    '冷链商品' => 'cold_storage',
                    '贵重品' => 'valuables',
                    '其他' => 'others',
                ]))
            ->add(BooleanFilter::new('isActive', '启用状态'))
            ->add(BooleanFilter::new('requireSampling', '需要抽检'))
            ->add('name')
            ->add(DateTimeFilter::new('createdAt', '创建时间'))
        ;
    }

    /**
     * 启用质检标准
     */
    public function activateStandard(): Response
    {
        // TODO: 实现启用标准逻辑
        // 可以集成业务验证，确保启用前标准配置完整

        return $this->redirectToRoute('admin');
    }

    /**
     * 停用质检标准
     */
    public function deactivateStandard(): Response
    {
        // TODO: 实现停用标准逻辑
        // 需要检查是否有正在使用的质检任务

        return $this->redirectToRoute('admin');
    }

    /**
     * 复制质检标准
     */
    public function duplicateStandard(): Response
    {
        // TODO: 实现标准复制逻辑
        // 复制现有标准并修改名称

        return $this->redirectToRoute('admin');
    }
}
