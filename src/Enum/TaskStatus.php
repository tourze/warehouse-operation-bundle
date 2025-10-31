<?php

namespace Tourze\WarehouseOperationBundle\Enum;

use Tourze\EnumExtra\BadgeInterface;
use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

enum TaskStatus: string implements Itemable, Labelable, Selectable, BadgeInterface
{
    use ItemTrait;
    use SelectTrait;

    case PENDING = 'pending';
    case ASSIGNED = 'assigned';
    case IN_PROGRESS = 'in_progress';
    case PAUSED = 'paused';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
    case FAILED = 'failed';
    case DISCREPANCY_FOUND = 'discrepancy_found';

    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING => '待分配',
            self::ASSIGNED => '已分配',
            self::IN_PROGRESS => '进行中',
            self::PAUSED => '暂停',
            self::COMPLETED => '已完成',
            self::CANCELLED => '已取消',
            self::FAILED => '失败',
            self::DISCREPANCY_FOUND => '发现差异',
        };
    }

    public function canAssign(): bool
    {
        return self::PENDING === $this;
    }

    public function canStart(): bool
    {
        return self::ASSIGNED === $this;
    }

    public function canComplete(): bool
    {
        return self::IN_PROGRESS === $this;
    }

    public function canCancel(): bool
    {
        return in_array($this, [self::PENDING, self::ASSIGNED, self::PAUSED], true);
    }

    public function getBadge(): string
    {
        return match ($this) {
            self::PENDING => 'secondary',
            self::ASSIGNED => 'info',
            self::IN_PROGRESS => 'primary',
            self::PAUSED => 'warning',
            self::COMPLETED => 'success',
            self::CANCELLED => 'danger',
            self::FAILED => 'danger',
            self::DISCREPANCY_FOUND => 'warning',
        };
    }
}
