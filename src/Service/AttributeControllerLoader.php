<?php

declare(strict_types=1);

namespace Tourze\WarehouseOperationBundle\Service;

use Symfony\Bundle\FrameworkBundle\Routing\AttributeRouteControllerLoader;
use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\Routing\RouteCollection;
use Tourze\RoutingAutoLoaderBundle\Service\RoutingAutoLoaderInterface;
use Tourze\WarehouseOperationBundle\Controller\CountTaskCrudController;
use Tourze\WarehouseOperationBundle\Controller\InboundTaskCrudController;
use Tourze\WarehouseOperationBundle\Controller\LocationCrudController;
use Tourze\WarehouseOperationBundle\Controller\OutboundTaskCrudController;
use Tourze\WarehouseOperationBundle\Controller\QualityStandardCrudController;
use Tourze\WarehouseOperationBundle\Controller\QualityTaskCrudController;
use Tourze\WarehouseOperationBundle\Controller\ShelfCrudController;
use Tourze\WarehouseOperationBundle\Controller\TaskRuleCrudController;
use Tourze\WarehouseOperationBundle\Controller\TransferTaskCrudController;
use Tourze\WarehouseOperationBundle\Controller\WarehouseCrudController;
use Tourze\WarehouseOperationBundle\Controller\WorkerSkillCrudController;
use Tourze\WarehouseOperationBundle\Controller\ZoneCrudController;

/**
 * 仓库作业管理Bundle的属性控制器加载器
 */
#[Autoconfigure(public: true)]
#[AutoconfigureTag(name: 'routing.loader')]
class AttributeControllerLoader extends Loader implements RoutingAutoLoaderInterface
{
    private AttributeRouteControllerLoader $controllerLoader;

    public function __construct()
    {
        parent::__construct();
        $this->controllerLoader = new AttributeRouteControllerLoader();
    }

    public function load(mixed $resource, ?string $type = null): RouteCollection
    {
        return $this->autoload();
    }

    public function supports(mixed $resource, ?string $type = null): bool
    {
        return 'attribute_controller' === $type;
    }

    /**
     * @return list<class-string>
     */
    public function getControllers(): array
    {
        return [
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
    }

    public function autoload(): RouteCollection
    {
        $collection = new RouteCollection();
        $collection->addCollection($this->controllerLoader->load(WarehouseCrudController::class));
        $collection->addCollection($this->controllerLoader->load(ZoneCrudController::class));
        $collection->addCollection($this->controllerLoader->load(ShelfCrudController::class));
        $collection->addCollection($this->controllerLoader->load(LocationCrudController::class));
        $collection->addCollection($this->controllerLoader->load(InboundTaskCrudController::class));
        $collection->addCollection($this->controllerLoader->load(OutboundTaskCrudController::class));
        $collection->addCollection($this->controllerLoader->load(TransferTaskCrudController::class));
        $collection->addCollection($this->controllerLoader->load(QualityStandardCrudController::class));
        $collection->addCollection($this->controllerLoader->load(QualityTaskCrudController::class));
        $collection->addCollection($this->controllerLoader->load(TaskRuleCrudController::class));
        $collection->addCollection($this->controllerLoader->load(CountTaskCrudController::class));
        $collection->addCollection($this->controllerLoader->load(WorkerSkillCrudController::class));

        return $collection;
    }
}
