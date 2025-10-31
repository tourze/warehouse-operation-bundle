<?php

namespace Tourze\WarehouseOperationBundle\Service;

use Tourze\WarehouseOperationBundle\Entity\WarehouseTask;

/**
 * 任务调度服务接口
 *
 * 负责智能任务调度、优先级管理和作业员分配的核心业务逻辑。
 * 实现基于技能匹配、工作负载均衡和资源约束的智能调度算法。
 */
interface TaskSchedulingServiceInterface
{
    /**
     * 智能调度任务批次分配
     *
     * 根据任务优先级、作业员技能、设备资源和路径优化等因素，
     * 为一批待处理任务计算最优的分配方案。
     *
     * @param WarehouseTask[] $pendingTasks 待分配的任务列表
     * @param array<string, mixed> $constraints 调度约束条件
     *   - worker_availability: 作业员可用性信息
     *   - equipment_constraints: 设备资源约束
     *   - zone_restrictions: 区域访问限制
     *   - time_windows: 时间窗口限制
     * @return array<string, mixed> 分配结果
     *   - assignments: TaskAssignment[] 任务分配详情
     *   - unassigned: WarehouseTask[] 未能分配的任务
     *   - statistics: array 调度统计信息
     *   - recommendations: array 优化建议
     */
    public function scheduleTaskBatch(array $pendingTasks, array $constraints = []): array;

    /**
     * 重新计算所有任务优先级
     *
     * 根据业务规则、紧急程度、客户要求和库存状况等因素，
     * 动态重新计算系统中所有待处理任务的优先级。
     *
     * @param array<string, mixed> $context 重计算上下文
     *   - trigger_reason: 触发重计算的原因
     *   - affected_zones: 受影响的作业区域
     *   - priority_factors: 优先级计算因子权重
     * @return array<string, mixed> 重计算结果
     *   - updated_count: 更新任务数量
     *   - priority_changes: 优先级变更详情
     *   - affected_assignments: 受影响的分配关系
     */
    public function recalculatePriorities(array $context = []): array;

    /**
     * 基于技能匹配为任务分配最适合的作业员
     *
     * 分析任务要求与作业员技能的匹配度，结合工作负载、
     * 地理位置和历史绩效等因素，选择最适合的作业员。
     *
     * @param WarehouseTask $task 需要分配的任务
     * @param array<string, mixed> $options 分配选项
     *   - skill_weight: 技能匹配权重
     *   - workload_weight: 工作负载权重
     *   - location_weight: 位置距离权重
     *   - performance_weight: 历史绩效权重
     *   - exclude_workers: 排除的作业员ID列表
     * @return array<string, mixed>|null 分配结果
     *   - worker_id: 分配的作业员ID
     *   - match_score: 匹配评分 (0-1)
     *   - assignment_reason: 分配原因
     *   - estimated_completion: 预计完成时间
     *   - skill_analysis: 技能分析详情
     */
    public function assignWorkerBySkill(WarehouseTask $task, array $options = []): ?array;

    /**
     * 获取当前调度队列状态
     *
     * @return array<string, mixed> 队列状态信息
     *   - pending_count: 待处理任务数量
     *   - active_count: 执行中任务数量
     *   - worker_utilization: 作业员利用率
     *   - average_wait_time: 平均等待时间
     *   - bottlenecks: 瓶颈分析
     */
    public function getSchedulingQueueStatus(): array;

    /**
     * 执行调度优化分析
     *
     * 分析当前调度效果，识别性能瓶颈和优化机会。
     *
     * @param array<string, mixed> $criteria 分析条件
     *   - time_range: 分析时间范围
     *   - task_types: 任务类型过滤
     *   - zones: 区域过滤
     * @return array<string, mixed> 优化分析结果
     *   - efficiency_score: 调度效率评分
     *   - optimization_suggestions: 优化建议列表
     *   - resource_utilization: 资源利用率分析
     *   - performance_trends: 性能趋势分析
     */
    public function analyzeSchedulingOptimization(array $criteria = []): array;

    /**
     * 处理紧急任务插入
     *
     * 为紧急任务快速找到插入点，必要时重新调度已分配的任务。
     *
     * @param WarehouseTask $urgentTask 紧急任务
     * @param array<string, mixed> $urgencyLevel 紧急级别
     *   - priority: 紧急优先级
     *   - max_delay_minutes: 最大延迟时间(分钟)
     *   - preempt_allowed: 是否允许抢占其他任务
     * @return array<string, mixed> 插入结果
     *   - assigned: bool 是否成功分配
     *   - assignment_details: 分配详情
     *   - rescheduled_tasks: 被重新调度的任务
     *   - impact_analysis: 影响分析
     */
    public function handleUrgentTaskInsertion(WarehouseTask $urgentTask, array $urgencyLevel): array;

    /**
     * 批量任务重新分配
     *
     * 当作业员状态变更或设备故障时，批量重新分配受影响的任务。
     *
     * @param array<int> $affectedTaskIds 受影响的任务ID列表
     * @param string $reason 重新分配原因
     * @param array<string, mixed> $constraints 重分配约束
     * @return array<string, mixed> 重分配结果
     *   - successful_reassignments: 成功重分配的任务数量
     *   - failed_reassignments: 重分配失败的任务
     *   - new_assignments: 新的分配详情
     *   - estimated_delay: 预计延迟时间
     */
    public function batchReassignTasks(array $affectedTaskIds, string $reason, array $constraints = []): array;
}
