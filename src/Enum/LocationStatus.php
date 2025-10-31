<?php

namespace Tourze\WarehouseOperationBundle\Enum;

use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

enum LocationStatus: string implements Itemable, Labelable, Selectable
{
    use ItemTrait;
    use SelectTrait;

    case AVAILABLE = 'available';
    case OCCUPIED = 'occupied';
    case MAINTENANCE = 'maintenance';
    case LOCKED = 'locked';

    public function getLabel(): string
    {
        return match ($this) {
            self::AVAILABLE => '可用',
            self::OCCUPIED => '占用',
            self::MAINTENANCE => '维护中',
            self::LOCKED => '锁定',
        };
    }
}
