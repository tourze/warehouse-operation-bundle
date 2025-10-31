<?php

namespace Tourze\WarehouseOperationBundle\Event;

use Tourze\WarehouseOperationBundle\Entity\WarehouseTask;

/**
 * 任务失败事件
 *
 * 当任务执行失败时触发此事件，包含失败原因和影响分析。
 * 可用于触发异常处理、通知、重试调度等处理流程。
 */
class TaskFailedEvent extends AbstractTaskEvent
{
    /**
     * @param WarehouseTask $task 任务对象
     * @param string $failureReason 失败原因
     * @param \DateTimeImmutable $failedAt 失败时间
     * @param array<string, mixed> $failureDetails 失败详情
     * @param array<string, mixed> $impactAnalysis 影响分析
     * @param array<string, mixed> $context 上下文信息
     */
    public function __construct(
        WarehouseTask $task,
        protected readonly string $failureReason,
        protected readonly \DateTimeImmutable $failedAt,
        protected readonly array $failureDetails = [],
        protected readonly array $impactAnalysis = [],
        array $context = [],
    ) {
        parent::__construct($task, $context);
    }

    /**
     * 获取失败原因
     */
    public function getFailureReason(): string
    {
        return $this->failureReason;
    }

    /**
     * 获取失败时间
     */
    public function getFailedAt(): \DateTimeImmutable
    {
        return $this->failedAt;
    }

    /**
     * 获取失败详情
     *
     * @return array<string, mixed>
     */
    public function getFailureDetails(): array
    {
        return $this->failureDetails;
    }

    /**
     * 获取影响分析
     *
     * @return array<string, mixed>
     */
    public function getImpactAnalysis(): array
    {
        return $this->impactAnalysis;
    }

    /**
     * 获取错误代码
     */
    public function getErrorCode(): ?string
    {
        $errorCode = $this->failureDetails['error_code'] ?? null;

        return is_string($errorCode) ? $errorCode : null;
    }

    /**
     * 获取失败类型
     */
    public function getFailureType(): string
    {
        $type = $this->failureDetails['type'] ?? 'unknown';

        return is_string($type) ? $type : 'unknown';
    }

    /**
     * 检查是否为系统错误
     */
    public function isSystemError(): bool
    {
        return 'system' === $this->getFailureType();
    }

    /**
     * 检查是否为人为错误
     */
    public function isHumanError(): bool
    {
        return 'human' === $this->getFailureType();
    }

    /**
     * 检查是否为设备故障
     */
    public function isEquipmentFailure(): bool
    {
        return 'equipment' === $this->getFailureType();
    }

    /**
     * 检查是否为环境因素
     */
    public function isEnvironmentalIssue(): bool
    {
        return 'environmental' === $this->getFailureType();
    }

    /**
     * 获取失败严重程度
     */
    public function getSeverityLevel(): string
    {
        $severity = $this->failureDetails['severity'] ?? 'medium';

        return is_string($severity) ? $severity : 'medium';
    }

    /**
     * 检查是否为严重失败
     */
    public function isCriticalFailure(): bool
    {
        return 'critical' === $this->getSeverityLevel();
    }

    /**
     * 获取重试次数
     */
    public function getRetryCount(): int
    {
        $retryCount = $this->failureDetails['retry_count'] ?? 0;

        return is_int($retryCount) ? $retryCount : 0;
    }

    /**
     * 检查是否可以重试
     */
    public function canRetry(): bool
    {
        $maxRetries = $this->failureDetails['max_retries'] ?? 3;

        return $this->getRetryCount() < $maxRetries && !$this->isCriticalFailure();
    }

    /**
     * 获取下次重试时间
     */
    public function getNextRetryTime(): ?\DateTimeImmutable
    {
        if (!$this->canRetry()) {
            return null;
        }

        $retryDelay = $this->failureDetails['retry_delay_seconds'] ?? 300;

        if (!is_int($retryDelay) && !is_float($retryDelay) && !is_string($retryDelay)) {
            $retryDelay = 300;
        }

        $retryDelayInt = (int) $retryDelay;

        return $this->failedAt->add(new \DateInterval('PT' . $retryDelayInt . 'S'));
    }

    /**
     * 获取已完成的工作百分比
     */
    public function getCompletedPercentage(): float
    {
        $percentage = $this->failureDetails['completed_percentage'] ?? 0.0;

        return is_float($percentage) || is_int($percentage) ? (float) $percentage : 0.0;
    }

    /**
     * 获取失败时的工作员ID
     */
    public function getFailedByWorkerId(): ?int
    {
        $workerId = $this->failureDetails['worker_id'] ?? null;

        return is_int($workerId) ? $workerId : null;
    }

    /**
     * 获取失败位置
     */
    public function getFailureLocation(): ?string
    {
        $location = $this->failureDetails['location'] ?? null;

        return is_string($location) ? $location : null;
    }

    /**
     * 获取异常堆栈跟踪
     */
    public function getStackTrace(): ?string
    {
        $stackTrace = $this->failureDetails['stack_trace'] ?? null;

        return is_string($stackTrace) ? $stackTrace : null;
    }

    /**
     * 获取受影响的相关任务
     *
     * @return array<int, mixed>
     */
    public function getAffectedTasks(): array
    {
        $tasks = $this->impactAnalysis['affected_tasks'] ?? [];

        /** @var array<int, mixed> */
        return is_array($tasks) ? $tasks : [];
    }

    /**
     * 获取预计影响持续时间
     */
    public function getEstimatedImpactDuration(): ?int
    {
        $duration = $this->impactAnalysis['estimated_impact_seconds'] ?? null;

        return is_int($duration) ? $duration : null;
    }

    /**
     * 获取恢复建议
     *
     * @return array<int, string>
     */
    public function getRecoveryActions(): array
    {
        $actions = $this->impactAnalysis['recovery_actions'] ?? [];

        /** @var array<int, string> */
        return is_array($actions) ? $actions : [];
    }

    /**
     * 获取通知级别
     */
    public function getNotificationLevel(): string
    {
        $severity = $this->getSeverityLevel();

        return match ($severity) {
            'critical' => 'immediate',
            'high' => 'urgent',
            'medium' => 'normal',
            default => 'low',
        };
    }

    /**
     * 检查是否需要上级审批
     */
    public function requiresManagerApproval(): bool
    {
        return $this->isCriticalFailure()
               || $this->getRetryCount() >= 2
               || count($this->getAffectedTasks()) > 0;
    }
}
