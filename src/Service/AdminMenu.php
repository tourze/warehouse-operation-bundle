<?php

declare(strict_types=1);

namespace Tourze\WarehouseOperationBundle\Service;

use Knp\Menu\ItemInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Tourze\EasyAdminMenuBundle\Service\LinkGeneratorInterface;
use Tourze\EasyAdminMenuBundle\Service\MenuProviderInterface;
use Tourze\WarehouseOperationBundle\Entity\CountPlan;
use Tourze\WarehouseOperationBundle\Entity\CountTask;
use Tourze\WarehouseOperationBundle\Entity\InboundTask;
use Tourze\WarehouseOperationBundle\Entity\Location;
use Tourze\WarehouseOperationBundle\Entity\OutboundTask;
use Tourze\WarehouseOperationBundle\Entity\QualityStandard;
use Tourze\WarehouseOperationBundle\Entity\QualityTask;
use Tourze\WarehouseOperationBundle\Entity\Shelf;
use Tourze\WarehouseOperationBundle\Entity\TaskRule;
use Tourze\WarehouseOperationBundle\Entity\TransferTask;
use Tourze\WarehouseOperationBundle\Entity\Warehouse;
use Tourze\WarehouseOperationBundle\Entity\WarehouseTask;
use Tourze\WarehouseOperationBundle\Entity\WorkerSkill;
use Tourze\WarehouseOperationBundle\Entity\Zone;

/**
 * 仓库作业管理后台菜单提供者
 */
#[Autoconfigure(public: true)]
readonly class AdminMenu implements MenuProviderInterface
{
    public function __construct(
        private LinkGeneratorInterface $linkGenerator,
    ) {
    }

    public function __invoke(ItemInterface $item): void
    {
        if (null === $item->getChild('业务管理')) {
            $item->addChild('业务管理');
        }

        $businessMenu = $item->getChild('业务管理');
        if (null === $businessMenu) {
            return;
        }

        // 添加仓库管理子菜单
        if (null === $businessMenu->getChild('仓库管理')) {
            $businessMenu->addChild('仓库管理')
                ->setAttribute('icon', 'fas fa-warehouse')
            ;
        }

        $warehouseMenu = $businessMenu->getChild('仓库管理');
        if (null === $warehouseMenu) {
            return;
        }

        // 基础设施管理
        $warehouseMenu->addChild('基础设施')
            ->setAttribute('icon', 'fas fa-building')
        ;

        $infrastructureMenu = $warehouseMenu->getChild('基础设施');
        if (null !== $infrastructureMenu) {
            $infrastructureMenu->addChild('仓库管理')
                ->setUri($this->linkGenerator->getCurdListPage(Warehouse::class))
                ->setAttribute('icon', 'fas fa-warehouse')
            ;

            $infrastructureMenu->addChild('库区管理')
                ->setUri($this->linkGenerator->getCurdListPage(Zone::class))
                ->setAttribute('icon', 'fas fa-th-large')
            ;

            $infrastructureMenu->addChild('货架管理')
                ->setUri($this->linkGenerator->getCurdListPage(Shelf::class))
                ->setAttribute('icon', 'fas fa-layer-group')
            ;

            $infrastructureMenu->addChild('存储位置')
                ->setUri($this->linkGenerator->getCurdListPage(Location::class))
                ->setAttribute('icon', 'fas fa-map-marker-alt')
            ;
        }

        // 任务管理
        $warehouseMenu->addChild('任务管理')
            ->setAttribute('icon', 'fas fa-tasks')
        ;

        $taskMenu = $warehouseMenu->getChild('任务管理');
        if (null !== $taskMenu) {
            $taskMenu->addChild('所有任务')
                ->setUri($this->linkGenerator->getCurdListPage(WarehouseTask::class))
                ->setAttribute('icon', 'fas fa-list')
            ;

            $taskMenu->addChild('入库任务')
                ->setUri($this->linkGenerator->getCurdListPage(InboundTask::class))
                ->setAttribute('icon', 'fas fa-arrow-down')
            ;

            $taskMenu->addChild('出库任务')
                ->setUri($this->linkGenerator->getCurdListPage(OutboundTask::class))
                ->setAttribute('icon', 'fas fa-arrow-up')
            ;

            $taskMenu->addChild('转移任务')
                ->setUri($this->linkGenerator->getCurdListPage(TransferTask::class))
                ->setAttribute('icon', 'fas fa-exchange-alt')
            ;

            $taskMenu->addChild('质量任务')
                ->setUri($this->linkGenerator->getCurdListPage(QualityTask::class))
                ->setAttribute('icon', 'fas fa-search')
            ;

            $taskMenu->addChild('盘点任务')
                ->setUri($this->linkGenerator->getCurdListPage(CountTask::class))
                ->setAttribute('icon', 'fas fa-clipboard-check')
            ;
        }

        // 质量管理
        $warehouseMenu->addChild('质量管理')
            ->setAttribute('icon', 'fas fa-award')
        ;

        $qualityMenu = $warehouseMenu->getChild('质量管理');
        if (null !== $qualityMenu) {
            $qualityMenu->addChild('质量标准')
                ->setUri($this->linkGenerator->getCurdListPage(QualityStandard::class))
                ->setAttribute('icon', 'fas fa-certificate')
            ;
        }

        // 盘点管理
        $warehouseMenu->addChild('盘点管理')
            ->setAttribute('icon', 'fas fa-clipboard-list')
        ;

        $countMenu = $warehouseMenu->getChild('盘点管理');
        if (null !== $countMenu) {
            $countMenu->addChild('盘点计划')
                ->setUri($this->linkGenerator->getCurdListPage(CountPlan::class))
                ->setAttribute('icon', 'fas fa-calendar-alt')
            ;
        }

        // 人员管理
        $warehouseMenu->addChild('人员管理')
            ->setAttribute('icon', 'fas fa-users')
        ;

        $staffMenu = $warehouseMenu->getChild('人员管理');
        if (null !== $staffMenu) {
            $staffMenu->addChild('工人技能')
                ->setUri($this->linkGenerator->getCurdListPage(WorkerSkill::class))
                ->setAttribute('icon', 'fas fa-user-graduate')
            ;
        }

        // 规则配置
        $warehouseMenu->addChild('规则配置')
            ->setAttribute('icon', 'fas fa-cogs')
        ;

        $ruleMenu = $warehouseMenu->getChild('规则配置');
        if (null !== $ruleMenu) {
            $ruleMenu->addChild('任务规则')
                ->setUri($this->linkGenerator->getCurdListPage(TaskRule::class))
                ->setAttribute('icon', 'fas fa-sitemap')
            ;
        }
    }
}
