<?php

namespace Tourze\WarehouseOperationBundle\Model;

/**
 * 拣货结果DTO
 *
 * 包含拣货任务的执行结果，包括商品列表、位置信息和拣货指令。
 */
class PickingResult
{
    /**
     * @param array<string> $items 商品代码列表
     * @param array<string> $locations 位置代码列表
     * @param array<string> $instructions 拣货指令列表
     */
    public function __construct(
        private readonly array $items,
        private readonly array $locations,
        private readonly array $instructions = [],
    ) {
    }

    /**
     * 获取商品代码列表
     *
     * @return array<string>
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * 获取位置代码列表
     *
     * @return array<string>
     */
    public function getLocations(): array
    {
        return $this->locations;
    }

    /**
     * 获取拣货指令列表
     *
     * @return array<string>
     */
    public function getInstructions(): array
    {
        return $this->instructions;
    }
}
