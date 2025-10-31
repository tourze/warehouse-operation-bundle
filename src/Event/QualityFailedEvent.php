<?php

namespace Tourze\WarehouseOperationBundle\Event;

use Tourze\WarehouseOperationBundle\Entity\QualityTask;

/**
 * 质检失败事件
 *
 * 当质检任务失败时触发此事件，包含失败的质检任务和相关失败信息。
 * 可用于触发后续处理流程，如隔离商品、生成返工任务等。
 */
class QualityFailedEvent extends AbstractTaskEvent
{
    /**
     * @param QualityTask $qualityTask 质检任务对象
     * @param string $failureReason 失败原因
     * @param array<string, mixed> $failureDetails 失败详情
     * @param array<string, mixed> $context 上下文信息
     */
    public function __construct(
        protected readonly QualityTask $qualityTask,
        protected readonly string $failureReason,
        protected readonly array $failureDetails = [],
        array $context = [],
    ) {
        parent::__construct($qualityTask, $context);
    }

    /**
     * 获取质检任务对象
     */
    public function getQualityTask(): QualityTask
    {
        return $this->qualityTask;
    }

    /**
     * 获取失败原因
     */
    public function getFailureReason(): string
    {
        return $this->failureReason;
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
     * 检查是否包含特定失败类型
     */
    public function hasFailureType(string $type): bool
    {
        return isset($this->failureDetails['type']) && $this->failureDetails['type'] === $type;
    }

    /**
     * 获取失败严重程度
     */
    public function getFailureSeverity(): string
    {
        $severity = $this->failureDetails['severity'] ?? 'medium';

        return is_string($severity) ? $severity : 'medium';
    }

    /**
     * 检查是否需要隔离商品
     */
    public function requiresProductIsolation(): bool
    {
        $requiresIsolation = $this->failureDetails['requires_isolation'] ?? false;

        return is_bool($requiresIsolation) ? $requiresIsolation : false;
    }
}
