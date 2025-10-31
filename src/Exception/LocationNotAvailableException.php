<?php

namespace Tourze\WarehouseOperationBundle\Exception;

class LocationNotAvailableException extends WarehouseOperationException
{
    public static function forLocation(int $locationId, string $reason = 'Location is not available'): self
    {
        return new self(
            $reason,
            0,
            null,
            ['location_id' => $locationId]
        );
    }
}
