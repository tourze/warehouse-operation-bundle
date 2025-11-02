<?php

declare(strict_types=1);

namespace Tourze\WarehouseOperationBundle\Service;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\Routing\RouteCollection;
use Tourze\WarehouseOperationBundle\Controller\Admin\CountTaskCrudController;
use Tourze\WarehouseOperationBundle\Controller\Admin\InboundTaskCrudController;
use Tourze\WarehouseOperationBundle\Controller\Admin\LocationCrudController;
use Tourze\WarehouseOperationBundle\Controller\Admin\OutboundTaskCrudController;
use Tourze\WarehouseOperationBundle\Controller\Admin\QualityStandardCrudController;
use Tourze\WarehouseOperationBundle\Controller\Admin\QualityTaskCrudController;
use Tourze\WarehouseOperationBundle\Controller\Admin\ShelfCrudController;
use Tourze\WarehouseOperationBundle\Controller\Admin\TaskRuleCrudController;
use Tourze\WarehouseOperationBundle\Controller\Admin\TransferTaskCrudController;
use Tourze\WarehouseOperationBundle\Controller\Admin\WarehouseCrudController;
use Tourze\WarehouseOperationBundle\Controller\Admin\WorkerSkillCrudController;
use Tourze\WarehouseOperationBundle\Controller\Admin\ZoneCrudController;

/**
 * 用于加载和管理仓库操作的管理控制器服务
 */
#[Autoconfigure(public: true)]
final class AttributeControllerLoader
{
    /**
     * @var array<class-string>
     */
    private array $controllers = [
        WarehouseCrudController::class,
        ZoneCrudController::class,
        ShelfCrudController::class,
        LocationCrudController::class,
        InboundTaskCrudController::class,
        OutboundTaskCrudController::class,
        TransferTaskCrudController::class,
        QualityStandardCrudController::class,
        QualityTaskCrudController::class,
        TaskRuleCrudController::class,
        CountTaskCrudController::class,
        WorkerSkillCrudController::class,
    ];

    /**
     * 获取所有可用的控制器
     *
     * @return array<class-string>
     */
    public function getControllers(): array
    {
        return $this->controllers;
    }

    /**
     * Auto-load route collection (placeholder implementation).
     */
    public function autoload(): void
    {
        // This method exists to satisfy the loader interface requirements
        // Implementation would depend on specific routing requirements
    }

    /**
     * 检查此加载器是否支持给定的资源
     */
    public function supports(mixed $resource, ?string $type = null): bool
    {
        return $type === 'warehouse_admin_controllers';
    }
}