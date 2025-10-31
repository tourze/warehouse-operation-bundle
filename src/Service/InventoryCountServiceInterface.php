<?php

namespace Tourze\WarehouseOperationBundle\Service;

use Tourze\WarehouseOperationBundle\Entity\CountPlan;
use Tourze\WarehouseOperationBundle\Entity\CountTask;

/**
 * 盘点管理服务接口
 *
 * 负责盘点计划生成、任务执行和差异处理的核心业务逻辑。
 * 支持全盘、循环盘、ABC盘等多种盘点模式，提供完整的盘点流程管理。
 */
interface InventoryCountServiceInterface
{
    /**
     * 生成盘点计划
     *
     * 根据盘点类型和筛选条件，自动生成盘点计划和相关任务。
     * 支持全盘、循环盘、抽盘、ABC盘等多种盘点模式。
     *
     * @param string $countType 盘点类型
     *   - full: 全盘点
     *   - cycle: 循环盘点
     *   - spot: 抽盘
     *   - abc: ABC盘点
     *   - dynamic: 动态盘点
     * @param array<string, mixed> $criteria 盘点条件
     *   - warehouse_zones: 盘点区域列表
     *   - product_categories: 商品类别过滤
     *   - value_threshold: 价值阈值 (用于ABC盘)
     *   - last_count_days: 距离上次盘点天数
     *   - inventory_turnover: 库存周转率条件
     *   - accuracy_requirement: 准确率要求
     * @param array<string, mixed> $planOptions 计划选项
     *   - schedule_date: 计划执行日期
     *   - duration_days: 计划持续天数
     *   - team_assignment: 团队分配策略
     *   - priority_level: 优先级别
     * @return CountPlan 生成的盘点计划
     */
    public function generateCountPlan(string $countType, array $criteria, array $planOptions = []): CountPlan;

    /**
     * 执行盘点任务
     *
     * 处理具体的盘点任务执行，记录盘点数据并生成结果。
     * 支持条码扫描、RFID识别、人工录入等多种数据采集方式。
     *
     * @param CountTask $task 盘点任务
     * @param array<string, mixed> $countData 盘点数据
     *   - system_quantity: 系统库存数量
     *   - actual_quantity: 实际盘点数量
     *   - location_code: 库位编码
     *   - product_info: 商品信息
     *   - batch_number: 批次号
     *   - serial_numbers: 序列号列表 (如适用)
     *   - condition_notes: 商品状态备注
     *   - photos: 盘点照片URL列表
     * @param array<string, mixed> $executionContext 执行上下文
     *   - counter_id: 盘点员ID
     *   - count_method: 盘点方法 (barcode/rfid/manual)
     *   - double_check: 是否需要复核
     *   - timestamp: 盘点时间戳
     * @return array<string, mixed> 执行结果
     *   - task_status: 任务状态 (completed/pending_review/discrepancy_found)
     *   - count_accuracy: 盘点准确度
     *   - discrepancies: 发现的差异列表
     *   - next_actions: 后续处理动作
     *   - completion_time: 完成用时
     */
    public function executeCountTask(CountTask $task, array $countData, array $executionContext = []): array;

    /**
     * 处理盘点差异
     *
     * 分析和处理盘点过程中发现的差异，根据差异类型和严重程度
     * 执行相应的处理流程，如复盘、调整、上报等。
     *
     * @param CountTask $task 盘点任务
     * @param array<string, mixed> $discrepancyData 差异数据
     *   - discrepancy_type: 差异类型 (quantity/location/condition/missing/excess)
     *   - quantity_difference: 数量差异
     *   - value_impact: 价值影响
     *   - suspected_cause: 疑似原因
     *   - evidence_photos: 证据照片
     *   - witness_info: 见证人信息
     * @param array<string, mixed> $handlingOptions 处理选项
     *   - auto_adjust_threshold: 自动调整阈值
     *   - require_supervisor_approval: 是否需要主管审批
     *   - escalate_to_manager: 是否上报经理
     *   - trigger_recount: 是否触发复盘
     * @return array<string, mixed> 处理结果
     *   - handling_action: 处理动作 (auto_adjust/supervisor_review/manager_escalation/recount)
     *   - adjustment_amount: 调整金额
     *   - approval_required: 是否需要审批
     *   - follow_up_tasks: 后续任务列表
     *   - notification_sent: 通知发送状态
     */
    public function handleDiscrepancy(CountTask $task, array $discrepancyData, array $handlingOptions = []): array;

    /**
     * 获取盘点进度状态
     *
     * 查询指定盘点计划的执行进度和统计信息。
     *
     * @param CountPlan $plan 盘点计划
     * @return array<string, mixed> 进度状态
     *   - total_tasks: 总任务数
     *   - completed_tasks: 已完成任务数
     *   - pending_tasks: 待处理任务数
     *   - discrepancy_tasks: 有差异任务数
     *   - completion_percentage: 完成百分比
     *   - estimated_completion: 预计完成时间
     *   - team_performance: 团队绩效统计
     */
    public function getCountProgress(CountPlan $plan): array;

    /**
     * 生成盘点差异报告
     *
     * 基于盘点结果生成详细的差异分析报告。
     *
     * @param CountPlan $plan 盘点计划
     * @param array<string, mixed> $reportOptions 报告选项
     *   - include_zero_diff: 是否包含无差异项目
     *   - group_by_category: 是否按类别分组
     *   - format: 报告格式 (pdf/excel/json)
     *   - include_photos: 是否包含照片
     * @return array<string, mixed> 报告结果
     *   - report_id: 报告ID
     *   - file_url: 报告文件URL
     *   - summary_statistics: 汇总统计
     *   - accuracy_analysis: 准确率分析
     *   - cost_impact_analysis: 成本影响分析
     */
    public function generateDiscrepancyReport(CountPlan $plan, array $reportOptions = []): array;

    /**
     * 执行盘点结果分析
     *
     * 对盘点结果进行深度分析，识别库存管理中的问题和改进机会。
     *
     * @param array<int> $planIds 盘点计划ID列表
     * @param array<string, mixed> $analysisParams 分析参数
     *   - time_range: 分析时间范围
     *   - accuracy_threshold: 准确率阈值
     *   - value_impact_threshold: 价值影响阈值
     * @return array<string, mixed> 分析结果
     *   - overall_accuracy: 整体准确率
     *   - trend_analysis: 趋势分析
     *   - problem_categories: 问题类别统计
     *   - location_accuracy_ranking: 库位准确率排名
     *   - improvement_recommendations: 改进建议
     *   - cost_benefit_analysis: 成本效益分析
     */
    public function analyzeCountResults(array $planIds, array $analysisParams = []): array;

    /**
     * 优化盘点频率建议
     *
     * 基于历史盘点数据和库存周转情况，推荐最优的盘点频率。
     *
     * @param array<string, mixed> $optimizationCriteria 优化条件
     *   - warehouse_zones: 分析的仓库区域
     *   - product_categories: 商品类别
     *   - historical_months: 历史数据月份数
     * @return array<string, mixed> 优化建议
     *   - zone_frequency_recommendations: 按区域的频率建议
     *   - category_frequency_recommendations: 按类别的频率建议
     *   - cost_optimization_potential: 成本优化潜力
     *   - implementation_plan: 实施计划建议
     */
    public function optimizeCountFrequency(array $optimizationCriteria): array;

    /**
     * 处理盘点异常情况
     *
     * 处理盘点过程中出现的各种异常情况，如设备故障、数据异常等。
     *
     * @param CountTask $task 盘点任务
     * @param string $exceptionType 异常类型
     *   - equipment_failure: 设备故障
     *   - data_corruption: 数据损坏
     *   - access_denied: 访问受限
     *   - time_expired: 时间超期
     * @param array<string, mixed> $exceptionDetails 异常详情
     * @return array<string, mixed> 处理结果
     *   - recovery_actions: 恢复动作
     *   - alternative_procedures: 备选流程
     *   - impact_assessment: 影响评估
     *   - escalation_required: 是否需要升级处理
     */
    public function handleCountException(CountTask $task, string $exceptionType, array $exceptionDetails = []): array;

    /**
     * 验证盘点数据质量
     *
     * 检查盘点数据的完整性、一致性和合理性。
     *
     * @param array<int, array<string, mixed>> $countDataBatch 批量盘点数据
     * @param array<string, mixed> $validationRules 验证规则
     * @return array<string, mixed> 验证结果
     *   - validation_passed: 验证是否通过
     *   - data_quality_score: 数据质量评分
     *   - validation_errors: 验证错误列表
     *   - data_corrections: 数据修正建议
     */
    public function validateCountDataQuality(array $countDataBatch, array $validationRules = []): array;
}
