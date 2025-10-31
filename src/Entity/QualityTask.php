<?php

declare(strict_types=1);

namespace Tourze\WarehouseOperationBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Tourze\WarehouseOperationBundle\Enum\TaskType;

/**
 * 质检任务实体
 */
#[ORM\Entity]
class QualityTask extends WarehouseTask
{
    public function __construct()
    {
        $this->setType(TaskType::QUALITY);
    }

    /**
     * 设置任务名称 (便利方法，存储到data中)
     */
    public function setTaskName(string $name): void
    {
        $data = $this->getData();
        $data['task_name'] = $name;

        $this->setData($data);
    }

    /**
     * 获取任务名称 (便利方法，从data中获取)
     */
    public function getTaskName(): string
    {
        $data = $this->getData();

        $taskName = $data['task_name'] ?? "QualityTask #{$this->getId()}";

        return is_string($taskName) ? $taskName : "QualityTask #{$this->getId()}";
    }

    /**
     * 设置任务类型 (便利方法)
     */
    public function setTaskType(string $type): void
    {
        // 将字符串映射到枚举
        $taskType = match ($type) {
            'quality_check' => TaskType::QUALITY,
            'quality' => TaskType::QUALITY,
            default => TaskType::QUALITY,
        };

        $this->setType($taskType);
    }
}
