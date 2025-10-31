<?php

namespace Tourze\WarehouseOperationBundle\Event;

use Tourze\WarehouseOperationBundle\Entity\CountTask;

/**
 * 盘点差异事件
 *
 * 当盘点任务发现库存差异时触发此事件，包含差异信息和处理策略。
 * 可用于触发后续处理流程，如差异分析、库存调整、审批等。
 */
class CountDiscrepancyEvent extends AbstractTaskEvent
{
    /**
     * @param CountTask $countTask 盘点任务对象
     * @param array<string, mixed> $discrepancyData 差异数据
     * @param array<string, mixed> $context 上下文信息
     */
    public function __construct(
        protected readonly CountTask $countTask,
        protected readonly array $discrepancyData,
        array $context = [],
    ) {
        parent::__construct($countTask, $context);
    }

    /**
     * 获取盘点任务对象
     */
    public function getCountTask(): CountTask
    {
        return $this->countTask;
    }

    /**
     * 获取差异数据
     *
     * @return array<string, mixed>
     */
    public function getDiscrepancyData(): array
    {
        return $this->discrepancyData;
    }

    /**
     * 检查是否为正差异（实际大于系统）
     */
    public function isPositiveDiscrepancy(): bool
    {
        return $this->getQuantityDifference() > 0;
    }

    /**
     * 获取差异数量
     */
    public function getQuantityDifference(): int
    {
        return $this->getActualQuantity() - $this->getSystemQuantity();
    }

    /**
     * 获取实际数量
     */
    public function getActualQuantity(): int
    {
        $value = $this->discrepancyData['actual_quantity'] ?? 0;

        return is_numeric($value) ? (int) $value : 0;
    }

    /**
     * 获取系统数量
     */
    public function getSystemQuantity(): int
    {
        $value = $this->discrepancyData['system_quantity'] ?? 0;

        return is_numeric($value) ? (int) $value : 0;
    }

    /**
     * 检查是否为负差异（实际小于系统）
     */
    public function isNegativeDiscrepancy(): bool
    {
        return $this->getQuantityDifference() < 0;
    }

    /**
     * 检查是否需要审批
     */
    public function requiresApproval(): bool
    {
        $severity = $this->getDiscrepancySeverity();

        return in_array($severity, ['high', 'critical'], true)
            || (bool) ($this->discrepancyData['requires_approval'] ?? false);
    }

    /**
     * 获取差异严重程度
     */
    public function getDiscrepancySeverity(): string
    {
        $severity = $this->discrepancyData['severity'] ?? '';

        if (is_string($severity) && '' !== $severity) {
            return $severity;
        }

        // 根据差异金额自动判定严重程度
        $amount = $this->getDiscrepancyAmount();

        if ($amount >= 1000) {
            return 'critical';
        }
        if ($amount >= 100) {
            return 'high';
        }
        if ($amount >= 10) {
            return 'medium';
        }

        return 'low';
    }

    /**
     * 获取差异金额
     */
    public function getDiscrepancyAmount(): float
    {
        $value = $this->discrepancyData['amount'] ?? 0.0;

        return is_numeric($value) ? (float) $value : 0.0;
    }

    /**
     * 获取建议处理动作
     *
     * @return array<string>
     */
    public function getSuggestedActions(): array
    {
        $actions = $this->discrepancyData['suggested_actions'] ?? [];

        if (is_array($actions) && count($actions) > 0) {
            return array_filter($actions, 'is_string');
        }

        // 根据差异类型和严重程度自动建议处理动作
        $severity = $this->getDiscrepancySeverity();
        $type = $this->getDiscrepancyType();

        $defaultActions = [];

        if ('critical' === $severity) {
            $defaultActions = ['immediate_recount', 'manager_review', 'audit_investigation'];
        } elseif ('high' === $severity) {
            $defaultActions = ['supervisor_approval', 'detailed_recount'];
        } elseif ('damage' === $type) {
            $defaultActions = ['damage_assessment', 'insurance_claim'];
        } else {
            $defaultActions = ['auto_adjustment'];
        }

        return $defaultActions;
    }

    /**
     * 获取差异类型
     */
    public function getDiscrepancyType(): string
    {
        $type = $this->discrepancyData['type'] ?? 'quantity';

        return is_string($type) ? $type : 'quantity';
    }
}
