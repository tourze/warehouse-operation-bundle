<?php

namespace Tourze\WarehouseOperationBundle\Model;

/**
 * 质检结果DTO
 *
 * 包含质检规则的执行结果。
 */
class QualityResult
{
    /**
     * @param bool $passed
     * @param array<string, mixed> $details
     * @param string $message
     */
    public function __construct(
        private readonly bool $passed,
        private readonly array $details = [],
        private readonly string $message = '',
    ) {
    }

    public function isPassed(): bool
    {
        return $this->passed;
    }

    /**
     * @return array<string, mixed>
     */
    public function getDetails(): array
    {
        return $this->details;
    }

    public function getMessage(): string
    {
        return $this->message;
    }
}
