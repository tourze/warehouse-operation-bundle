<?php

namespace Tourze\WarehouseOperationBundle\Exception;

class InsufficientInventoryException extends WarehouseOperationException
{
    public static function forItem(string $itemCode, int $requested, int $available): self
    {
        return new self(
            "Insufficient inventory for item '{$itemCode}': requested {$requested}, available {$available}",
            0,
            null,
            [
                'item_code' => $itemCode,
                'requested' => $requested,
                'available' => $available,
            ]
        );
    }
}
