<?php

namespace Tourze\WarehouseOperationBundle\Enum;

use Tourze\EnumExtra\BadgeInterface;
use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

enum TaskType: string implements Itemable, Labelable, Selectable, BadgeInterface
{
    use ItemTrait;
    use SelectTrait;

    case INBOUND = 'inbound';
    case OUTBOUND = 'outbound';
    case QUALITY = 'quality';
    case COUNT = 'count';
    case TRANSFER = 'transfer';

    public function getLabel(): string
    {
        return match ($this) {
            self::INBOUND => '入库任务',
            self::OUTBOUND => '出库任务',
            self::QUALITY => '质检任务',
            self::COUNT => '盘点任务',
            self::TRANSFER => '调拨任务',
        };
    }

    public function getBadge(): string
    {
        return match ($this) {
            self::INBOUND => 'info',
            self::OUTBOUND => 'primary',
            self::QUALITY => 'warning',
            self::COUNT => 'secondary',
            self::TRANSFER => 'success',
        };
    }
}
