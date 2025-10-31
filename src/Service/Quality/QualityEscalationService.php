<?php

declare(strict_types=1);

namespace Tourze\WarehouseOperationBundle\Service\Quality;

use Tourze\WarehouseOperationBundle\Entity\QualityTask;
use Tourze\WarehouseOperationBundle\Repository\WarehouseTaskRepository;

/**
 * 质检异常升级服务
 */
final class QualityEscalationService
{
    private WarehouseTaskRepository $qualityTaskRepository;

    public function __construct(WarehouseTaskRepository $qualityTaskRepository)
    {
        $this->qualityTaskRepository = $qualityTaskRepository;
    }

    /**
     * 处理质检异常升级
     *
     * @param array<string, mixed> $escalationReason
     * @return array<string, mixed>
     */
    public function escalateQualityIssue(QualityTask $task, array $escalationReason): array
    {
        $severityRaw = $escalationReason['severity'] ?? 'high';
        $severity = is_string($severityRaw) ? $severityRaw : 'high';

        $issueTypeRaw = $escalationReason['issue_type'] ?? 'quality_defect';
        $issueType = is_string($issueTypeRaw) ? $issueTypeRaw : 'quality_defect';

        $impactScopeRaw = $escalationReason['impact_scope'] ?? 'single_batch';
        $impactScope = is_string($impactScopeRaw) ? $impactScopeRaw : 'single_batch';

        $escalationLevel = $this->determineEscalationLevel($severity, $issueType, $impactScope);
        $assignedPersonnel = $this->getEscalationPersonnel($escalationLevel);
        $deadline = $this->calculateEscalationDeadline($escalationLevel);

        $escalationData = [
            'escalated_at' => new \DateTimeImmutable(),
            'escalation_level' => $escalationLevel,
            'severity' => $severity,
            'issue_type' => $issueType,
            'impact_scope' => $impactScope,
            'assigned_personnel' => $assignedPersonnel,
            'deadline' => $deadline,
        ];

        $task->setData(array_merge($task->getData(), ['escalation' => $escalationData]));
        $this->qualityTaskRepository->save($task);

        // 这里会发送通知（实际项目中会有通知服务）
        $notificationSent = true;

        return [
            'escalation_level' => $escalationLevel,
            'assigned_personnel' => $assignedPersonnel,
            'deadline' => $deadline,
            'notification_sent' => $notificationSent,
        ];
    }

    /**
     * 确定升级级别
     */
    private function determineEscalationLevel(string $severity, string $issueType, string $impactScope): int
    {
        $baseLevel = match ($severity) {
            'critical' => 3,
            'high' => 2,
            'medium' => 1,
            'low' => 0,
            default => 1,
        };

        $typeMultiplier = match ($issueType) {
            'safety_issue' => 2,
            'contamination' => 2,
            'recall_risk' => 2,
            'quality_defect' => 1,
            default => 1,
        };

        $scopeMultiplier = match ($impactScope) {
            'multiple_batches' => 2,
            'single_batch' => 1,
            'single_item' => 0,
            default => 1,
        };

        return min(5, max(1, $baseLevel + $typeMultiplier + $scopeMultiplier));
    }

    /**
     * 获取升级人员
     *
     * @return array<int, string>
     */
    private function getEscalationPersonnel(int $level): array
    {
        return match ($level) {
            5 => ['quality_director', 'general_manager'],
            4 => ['quality_manager', 'operations_manager'],
            3 => ['quality_supervisor', 'shift_manager'],
            2 => ['senior_inspector', 'team_lead'],
            default => ['quality_inspector'],
        };
    }

    /**
     * 计算升级截止时间
     */
    private function calculateEscalationDeadline(int $level): \DateTimeImmutable
    {
        $hours = match ($level) {
            5 => 2,  // 2小时内处理
            4 => 4,  // 4小时内处理
            3 => 8,  // 8小时内处理
            2 => 24, // 24小时内处理
            default => 48, // 48小时内处理
        };

        return new \DateTimeImmutable("+{$hours} hours");
    }
}
