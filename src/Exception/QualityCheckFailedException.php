<?php

namespace Tourze\WarehouseOperationBundle\Exception;

class QualityCheckFailedException extends WarehouseOperationException
{
    /**
     * @param string[] $failureReasons
     */
    public static function forItem(string $itemCode, array $failureReasons): self
    {
        return new self(
            "Quality check failed for item '{$itemCode}': " . implode(', ', $failureReasons),
            0,
            null,
            [
                'item_code' => $itemCode,
                'failure_reasons' => $failureReasons,
            ]
        );
    }
}
