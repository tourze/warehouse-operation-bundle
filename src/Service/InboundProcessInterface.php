<?php

namespace Tourze\WarehouseOperationBundle\Service;

use Tourze\WarehouseOperationBundle\Entity\InboundTask;

/**
 * 入库流程接口
 *
 * 提供收货→质检→上架的完整入库业务流程管理。
 */
interface InboundProcessInterface
{
    /**
     * 开始入库流程
     *
     * @param array<array<string, mixed>> $items 入库商品列表
     * @param int|null $warehouseId 目标仓库ID（可选）
     * @return InboundTask 入库任务对象
     */
    public function startInbound(array $items, ?int $warehouseId = null): InboundTask;

    /**
     * 执行收货作业
     *
     * @param int $taskId 任务ID
     * @param array<array<string, mixed>> $actualItems 实际收货商品数据
     * @return bool 执行是否成功
     */
    public function executeReceiving(int $taskId, array $actualItems): bool;

    /**
     * 执行质检作业
     *
     * @param int $taskId 任务ID
     * @param array<array<string, mixed>> $qualityResults 质检结果数据
     * @return bool 执行是否成功
     */
    public function executeQualityCheck(int $taskId, array $qualityResults): bool;

    /**
     * 执行上架作业
     *
     * @param int $taskId 任务ID
     * @param array<array<string, mixed>> $locationAssignments 位置分配数据
     * @return bool 执行是否成功
     */
    public function executePutaway(int $taskId, array $locationAssignments): bool;
}
