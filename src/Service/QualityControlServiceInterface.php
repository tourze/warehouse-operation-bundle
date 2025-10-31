<?php

namespace Tourze\WarehouseOperationBundle\Service;

use Tourze\WarehouseOperationBundle\Entity\QualityStandard;
use Tourze\WarehouseOperationBundle\Entity\QualityTask;

/**
 * 质检控制服务接口
 *
 * 负责质检流程执行、不合格品处理和质检标准管理的核心业务逻辑。
 * 支持多维度质检、动态标准配置和自动化质量控制流程。
 */
interface QualityControlServiceInterface
{
    /**
     * 执行质检流程
     *
     * 根据质检标准对商品进行多维度检查，包括外观、数量、规格、
     * 重量、有效期等检查项目，生成详细的质检报告。
     *
     * @param QualityTask $task 质检任务
     * @param array<string, mixed> $checkData 检查数据
     *   - product_info: 商品基础信息
     *   - visual_check: 外观检查数据
     *   - quantity_check: 数量检查数据
     *   - specification_check: 规格检查数据
     *   - weight_check: 重量检查数据
     *   - expiry_check: 有效期检查数据
     *   - custom_checks: 自定义检查项
     * @param array<string, mixed> $options 检查选项
     *   - strict_mode: 是否启用严格模式
     *   - skip_optional: 是否跳过可选检查项
     *   - inspector_id: 质检员ID
     * @return array<string, mixed> 质检结果
     *   - overall_result: 整体质检结果 (pass/fail/conditional)
     *   - check_results: 各项检查详细结果
     *   - quality_score: 质量评分 (0-100)
     *   - defects: 发现的缺陷列表
     *   - recommendations: 处理建议
     *   - inspector_notes: 质检员备注
     *   - photos: 质检照片URL列表
     */
    public function performQualityCheck(QualityTask $task, array $checkData, array $options = []): array;

    /**
     * 处理质检失败商品
     *
     * 根据失败原因和严重程度，执行相应的处理流程，
     * 包括隔离、返工、退货、报废等操作。
     *
     * @param QualityTask $task 质检任务
     * @param string $failureReason 失败原因
     * @param array<string, mixed> $failureDetails 失败详情
     *   - failure_type: 失败类型 (appearance/quantity/specification/weight/expiry/damage)
     *   - severity_level: 严重程度 (low/medium/high/critical)
     *   - defect_description: 缺陷描述
     *   - affected_quantity: 受影响数量
     *   - cost_impact: 成本影响
     *   - photos: 缺陷照片
     * @param array<string, mixed> $handlingOptions 处理选项
     *   - auto_isolate: 是否自动隔离
     *   - notify_supplier: 是否通知供应商
     *   - create_claim: 是否创建索赔单
     * @return array<string, mixed> 处理结果
     *   - handling_actions: 执行的处理动作列表
     *   - isolation_location: 隔离位置
     *   - follow_up_tasks: 后续任务列表
     *   - cost_estimation: 成本预估
     *   - timeline: 处理时间线
     */
    public function handleQualityFailure(QualityTask $task, string $failureReason, array $failureDetails = [], array $handlingOptions = []): array;

    /**
     * 获取适用的质检标准
     *
     * 根据商品属性、供应商信息和业务规则，
     * 获取适用于特定商品的质检标准配置。
     *
     * @param array<string, mixed> $productAttributes 商品属性
     *   - product_id: 商品ID
     *   - category_id: 商品类别ID
     *   - supplier_id: 供应商ID
     *   - brand: 品牌
     *   - product_type: 商品类型
     *   - special_attributes: 特殊属性 (dangerous/valuable/perishable)
     *   - batch_info: 批次信息
     * @param array<string, mixed> $context 上下文信息
     *   - business_unit: 业务单元
     *   - warehouse_zone: 仓库区域
     *   - priority_level: 优先级别
     * @return QualityStandard[] 适用的质检标准列表
     */
    public function getApplicableStandards(array $productAttributes, array $context = []): array;

    /**
     * 验证质检标准配置
     *
     * 检查质检标准配置的完整性和有效性，确保标准定义正确。
     *
     * @param QualityStandard $standard 质检标准
     * @param array<string, mixed> $validationContext 验证上下文
     * @return array<string, mixed> 验证结果
     *   - is_valid: 是否有效
     *   - validation_errors: 验证错误列表
     *   - warnings: 警告信息列表
     *   - suggestions: 改进建议
     */
    public function validateQualityStandard(QualityStandard $standard, array $validationContext = []): array;

    /**
     * 生成质检报告
     *
     * 基于质检结果生成详细的质检报告，支持多种格式输出。
     *
     * @param array<int> $taskIds 质检任务ID列表
     * @param array<string, mixed> $reportOptions 报告选项
     *   - format: 报告格式 (pdf/excel/json)
     *   - include_photos: 是否包含照片
     *   - group_by: 分组方式 (product/supplier/date)
     *   - date_range: 日期范围
     * @return array<string, mixed> 报告结果
     *   - report_id: 报告ID
     *   - file_url: 报告文件URL
     *   - summary_statistics: 汇总统计
     *   - generated_at: 生成时间
     */
    public function generateQualityReport(array $taskIds, array $reportOptions = []): array;

    /**
     * 质检数据统计分析
     *
     * 分析质检数据趋势，识别质量问题模式和改进机会。
     *
     * @param array<string, mixed> $analysisParams 分析参数
     *   - time_period: 分析时间段
     *   - product_categories: 商品类别过滤
     *   - suppliers: 供应商过滤
     *   - failure_types: 失败类型过滤
     * @return array<string, mixed> 统计分析结果
     *   - overall_pass_rate: 整体合格率
     *   - trend_analysis: 趋势分析
     *   - failure_patterns: 失败模式分析
     *   - supplier_ranking: 供应商质量排名
     *   - improvement_opportunities: 改进机会
     *   - cost_analysis: 质量成本分析
     */
    public function analyzeQualityStatistics(array $analysisParams = []): array;

    /**
     * 执行质检样品抽检
     *
     * 根据抽检规则对批次商品进行抽样检查，支持多种抽检策略。
     *
     * @param array<string, mixed> $batchInfo 批次信息
     *   - batch_id: 批次ID
     *   - total_quantity: 总数量
     *   - product_info: 商品信息
     * @param array<string, mixed> $samplingRules 抽检规则
     *   - sampling_method: 抽检方法 (random/systematic/stratified)
     *   - sample_size: 样品大小
     *   - acceptance_criteria: 接收标准
     * @return array<string, mixed> 抽检结果
     *   - sample_tasks: 生成的抽检任务列表
     *   - sampling_plan: 抽检计划
     *   - expected_completion: 预计完成时间
     */
    public function executeSampleInspection(array $batchInfo, array $samplingRules): array;

    /**
     * 处理质检异常升级
     *
     * 当质检发现严重问题时，自动触发升级处理流程。
     *
     * @param QualityTask $task 质检任务
     * @param array<string, mixed> $escalationReason 升级原因
     *   - severity: 严重程度
     *   - issue_type: 问题类型
     *   - impact_scope: 影响范围
     * @return array<string, mixed> 升级处理结果
     *   - escalation_level: 升级级别
     *   - assigned_personnel: 分配的处理人员
     *   - deadline: 处理截止时间
     *   - notification_sent: 通知发送状态
     */
    public function escalateQualityIssue(QualityTask $task, array $escalationReason): array;
}
