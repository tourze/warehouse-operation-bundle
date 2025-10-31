<?php

namespace Tourze\WarehouseOperationBundle\Service;

class ConfigService
{
    public function getTaskTimeout(): int
    {
        $value = $_ENV['WAREHOUSE_TASK_TIMEOUT'] ?? '3600';

        return is_numeric($value) ? (int) $value : 3600;
    }

    public function isAutoAssignEnabled(): bool
    {
        return filter_var($_ENV['WAREHOUSE_AUTO_ASSIGN'] ?? 'true', FILTER_VALIDATE_BOOLEAN);
    }

    public function isQualityCheckRequired(): bool
    {
        return filter_var($_ENV['WAREHOUSE_QUALITY_REQUIRED'] ?? 'true', FILTER_VALIDATE_BOOLEAN);
    }

    public function getMaxConcurrentTasks(): int
    {
        $value = $_ENV['WAREHOUSE_MAX_CONCURRENT_TASKS'] ?? '100';

        return is_numeric($value) ? (int) $value : 100;
    }

    /**
     * 别名方法 - 更简洁的API
     */
    public function isAutoAssign(): bool
    {
        return $this->isAutoAssignEnabled();
    }

    /**
     * 别名方法 - 更简洁的API
     */
    public function isQualityRequired(): bool
    {
        return $this->isQualityCheckRequired();
    }
}
