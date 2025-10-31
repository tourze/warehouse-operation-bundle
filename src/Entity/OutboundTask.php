<?php

declare(strict_types=1);

namespace Tourze\WarehouseOperationBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * 出库任务实体
 */
#[ORM\Entity]
class OutboundTask extends WarehouseTask
{
    // 具体实现将在任务6中完成
}
