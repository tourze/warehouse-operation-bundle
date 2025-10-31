<?php

namespace Tourze\WarehouseOperationBundle\Service;

use Tourze\WarehouseOperationBundle\Entity\Location;

/**
 * 路径优化服务接口
 *
 * 负责作业路径优化和批量任务路径规划的核心业务逻辑。
 * 提供多种路径优化策略，减少作业员移动距离，提升拣货效率。
 */
interface PathOptimizationServiceInterface
{
    /**
     * 计算最优作业路径
     *
     * 根据位置列表和优化策略，计算最优的访问路径。
     *
     * @param Location[] $locations 需要访问的位置列表
     * @param string $strategy 路径策略
     *   - shortest: 最短路径
     *   - s_shape: S型路径
     *   - z_shape: Z型路径
     *   - dynamic: 动态优化
     * @param array<string, mixed> $constraints 约束条件
     *   - max_distance: 最大距离限制
     *   - avoid_zones: 避开的区域
     *   - equipment_restrictions: 设备限制
     * @return array<string, mixed> 优化路径结果
     *   - optimized_sequence: 优化后的位置访问顺序
     *   - total_distance: 总距离
     *   - estimated_time: 预计用时
     *   - efficiency_improvement: 效率提升百分比
     */
    public function optimizePath(array $locations, string $strategy = 'shortest', array $constraints = []): array;

    /**
     * 批量优化多个任务的路径
     *
     * @param array<int, mixed> $tasks 任务列表
     * @param array<string, mixed> $batchOptions 批量选项
     * @return array<string, mixed> 批量优化结果
     */
    public function optimizeBatchPaths(array $tasks, array $batchOptions = []): array;
}
