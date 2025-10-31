<?php

namespace Tourze\WarehouseOperationBundle\Service;

use Tourze\WarehouseOperationBundle\Enum\LocationStatus;

/**
 * 位置管理接口
 *
 * 提供仓库位置的查找、占用和释放操作。
 */
interface LocationManagerInterface
{
    /**
     * 查找可用位置
     *
     * @param string $itemCode 商品代码
     * @param int $quantity 商品数量
     * @return array<array<string, mixed>> 可用位置列表
     */
    public function findAvailableLocations(string $itemCode, int $quantity): array;

    /**
     * 占用位置
     *
     * @param int $locationId 位置ID
     * @param string $itemCode 商品代码
     * @param int $quantity 占用数量
     * @return bool 占用是否成功
     */
    public function occupyLocation(int $locationId, string $itemCode, int $quantity): bool;

    /**
     * 释放位置
     *
     * @param int $locationId 位置ID
     * @param string $itemCode 商品代码
     * @param int $quantity 释放数量
     * @return bool 释放是否成功
     */
    public function releaseLocation(int $locationId, string $itemCode, int $quantity): bool;

    /**
     * 获取位置状态
     *
     * @param int $locationId 位置ID
     * @return LocationStatus 位置状态
     */
    public function getLocationStatus(int $locationId): LocationStatus;
}
