<?php

namespace Tourze\WarehouseOperationBundle\Service;

use Tourze\WarehouseOperationBundle\Entity\OutboundTask;
use Tourze\WarehouseOperationBundle\Model\PickingResult;

/**
 * 出库流程接口
 *
 * 提供拣货→打包→发货的完整出库业务流程管理。
 */
interface OutboundProcessInterface
{
    /**
     * 开始出库流程
     *
     * @param array<array<string, mixed>> $items 出库商品列表
     * @param string $outboundType 出库类型
     * @return OutboundTask 出库任务对象
     */
    public function startOutbound(array $items, string $outboundType): OutboundTask;

    /**
     * 执行拣货作业
     *
     * @param int $taskId 任务ID
     * @return PickingResult 拣货结果
     */
    public function executePicking(int $taskId): PickingResult;

    /**
     * 执行打包作业
     *
     * @param int $taskId 任务ID
     * @param array<array<string, mixed>> $packages 包装数据
     * @return bool 执行是否成功
     */
    public function executePacking(int $taskId, array $packages): bool;

    /**
     * 执行发货作业
     *
     * @param int $taskId 任务ID
     * @param array<string, mixed> $shippingInfo 发货信息
     * @return bool 执行是否成功
     */
    public function executeShipping(int $taskId, array $shippingInfo): bool;
}
