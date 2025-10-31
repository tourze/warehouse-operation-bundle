<?php

namespace Tourze\WarehouseOperationBundle\Service\Extension;

use Tourze\WarehouseOperationBundle\Entity\Location;

/**
 * 分配策略接口
 *
 * 定义库位分配策略的标准接口，支持多种分配算法的扩展。
 */
interface AllocationStrategyInterface
{
    /**
     * 获取策略名称
     *
     * @return string 策略的唯一名称
     */
    public function getName(): string;

    /**
     * 分配库位
     *
     * @param int $warehouseId 仓库ID
     * @param string $sku 商品SKU
     * @param int $quantity 数量
     * @return Location|null 分配的库位，null表示没有可用库位
     */
    public function allocateLocation(int $warehouseId, string $sku, int $quantity): ?Location;

    /**
     * 获取策略优先级
     *
     * @return int 优先级，数字越小优先级越高
     */
    public function getPriority(): int;
}
