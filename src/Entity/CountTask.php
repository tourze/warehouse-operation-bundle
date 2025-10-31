<?php

declare(strict_types=1);

namespace Tourze\WarehouseOperationBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\WarehouseOperationBundle\Enum\TaskType;

/**
 * 盘点任务实体
 *
 * 专门用于盘点作业的任务实体，继承自WarehouseTask。
 * 提供盘点特定的便利方法和数据访问。
 *
 * 为了避免使用 JSON_EXTRACT 查询，将常用的查询字段提取为专门的数据库字段。
 */
#[ORM\Entity]
class CountTask extends WarehouseTask
{
    #[ORM\Column(type: Types::INTEGER, nullable: true, options: ['comment' => '盘点计划ID'])]
    #[Assert\Positive]
    private ?int $countPlanId = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true, options: ['comment' => '任务序列'])]
    #[Assert\PositiveOrZero]
    private ?int $taskSequence = null;

    #[ORM\Column(type: Types::STRING, length: 50, nullable: true, options: ['comment' => '库位编码'])]
    #[Assert\Length(max: 50)]
    private ?string $locationCode = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2, nullable: true, options: ['comment' => '盘点准确率'])]
    #[Assert\Range(min: 0, max: 100)]
    private ?string $accuracy = null;

    public function __construct()
    {
        $this->setType(TaskType::COUNT);
    }

    public function getCountPlanId(): ?int
    {
        return $this->countPlanId;
    }

    public function setCountPlanId(?int $countPlanId): void
    {
        $this->countPlanId = $countPlanId;

        // 同时更新 JSON 数据以保持向后兼容
        $data = $this->getData();
        if (null !== $countPlanId) {
            $data['count_plan_id'] = $countPlanId;
        } else {
            unset($data['count_plan_id']);
        }
        $this->setData($data);
    }

    public function getTaskSequence(): ?int
    {
        return $this->taskSequence;
    }

    public function setTaskSequence(?int $taskSequence): void
    {
        $this->taskSequence = $taskSequence;

        // 同时更新 JSON 数据以保持向后兼容
        $data = $this->getData();
        if (null !== $taskSequence) {
            $data['task_sequence'] = $taskSequence;
        } else {
            unset($data['task_sequence']);
        }
        $this->setData($data);
    }

    public function getLocationCode(): ?string
    {
        return $this->locationCode;
    }

    public function setLocationCode(?string $locationCode): void
    {
        $this->locationCode = $locationCode;

        // 同时更新 JSON 数据以保持向后兼容
        $data = $this->getData();
        if (null !== $locationCode) {
            $data['location_code'] = $locationCode;
        } else {
            unset($data['location_code']);
        }
        $this->setData($data);
    }

    public function getAccuracy(): ?string
    {
        return $this->accuracy;
    }

    public function setAccuracy(?string $accuracy): void
    {
        $this->accuracy = $accuracy;

        // 同时更新 JSON 数据以保持向后兼容
        $data = $this->getData();
        if (null !== $accuracy) {
            if (!isset($data['count_result']) || !is_array($data['count_result'])) {
                $data['count_result'] = [];
            }
            $data['count_result']['accuracy'] = (float) $accuracy;
        } else {
            if (isset($data['count_result']) && is_array($data['count_result'])) {
                unset($data['count_result']['accuracy']);
                if ([] === $data['count_result']) {
                    unset($data['count_result']);
                }
            }
        }
        $this->setData($data);
    }

    /**
     * 获取任务数据 (便利方法，映射到父类的getData)
     *
     * @return array<string, mixed>
     */
    public function getTaskData(): array
    {
        return $this->getData();
    }

    /**
     * 设置任务数据 (便利方法，映射到父类的setData)
     * 同时从 JSON 数据中提取关键字段到专门的数据库字段
     *
     * @param array<string, mixed> $data
     */
    public function setTaskData(array $data): void
    {
        $this->setData($data);

        // 从 JSON 数据中提取关键字段到专门的数据库字段
        $this->extractKeyFieldsFromData();
    }

    /**
     * 从 JSON 数据中提取关键字段到专门的数据库字段
     */
    private function extractKeyFieldsFromData(): void
    {
        $data = $this->getData();

        if (isset($data['count_plan_id']) && is_int($data['count_plan_id'])) {
            $this->countPlanId = $data['count_plan_id'];
        }

        if (isset($data['task_sequence']) && is_int($data['task_sequence'])) {
            $this->taskSequence = $data['task_sequence'];
        }

        if (isset($data['location_code']) && is_string($data['location_code'])) {
            $this->locationCode = $data['location_code'];
        }

        if (isset($data['count_result']) && is_array($data['count_result'])
            && isset($data['count_result']['accuracy']) && is_numeric($data['count_result']['accuracy'])) {
            $this->accuracy = (string) $data['count_result']['accuracy'];
        }
    }

    /**
     * 设置任务类型 (便利方法)
     */
    public function setTaskType(string $type): void
    {
        // 将字符串映射到枚举
        $taskType = match ($type) {
            'count' => TaskType::COUNT,
            'recount' => TaskType::COUNT, // 复盘也是盘点类型
            default => TaskType::COUNT,
        };

        $this->setType($taskType);
    }

    /**
     * 设置任务名称 (便利方法，存储到data中)
     */
    public function setTaskName(string $name): void
    {
        $data = $this->getTaskData();
        $data['task_name'] = $name;

        $this->setTaskData($data);
    }

    /**
     * 获取任务名称 (便利方法，从data中获取)
     */
    public function getTaskName(): string
    {
        $data = $this->getTaskData();

        $taskName = $data['task_name'] ?? "CountTask #{$this->getId()}";

        return is_string($taskName) ? $taskName : "CountTask #{$this->getId()}";
    }
}
