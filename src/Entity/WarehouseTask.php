<?php

declare(strict_types=1);

namespace Tourze\WarehouseOperationBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\DoctrineUserBundle\Traits\BlameableAware;
use Tourze\WarehouseOperationBundle\Enum\TaskStatus;
use Tourze\WarehouseOperationBundle\Enum\TaskType;

#[ORM\Entity]
#[ORM\Table(name: 'ims_wms_warehouse_task', options: ['comment' => '仓库任务'])]
#[ORM\InheritanceType(value: 'SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'task_type', type: 'string')]
#[ORM\DiscriminatorMap(value: [
    'inbound' => InboundTask::class,
    'outbound' => OutboundTask::class,
    'quality' => QualityTask::class,
    'count' => CountTask::class,
    'transfer' => TransferTask::class,
])]
abstract class WarehouseTask implements \Stringable
{
    use TimestampableAware;
    use BlameableAware;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, options: ['comment' => 'ID'])]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 20, enumType: TaskType::class, options: ['comment' => '任务类型'])]
    #[Assert\NotNull]
    #[Assert\Choice(choices: [TaskType::INBOUND->value, TaskType::OUTBOUND->value, TaskType::QUALITY->value, TaskType::COUNT->value, TaskType::TRANSFER->value])]
    private TaskType $type;

    #[ORM\Column(type: Types::STRING, length: 20, enumType: TaskStatus::class, options: ['comment' => '任务状态'])]
    #[Assert\NotNull]
    #[Assert\Choice(choices: [TaskStatus::PENDING->value, TaskStatus::ASSIGNED->value, TaskStatus::IN_PROGRESS->value, TaskStatus::PAUSED->value, TaskStatus::COMPLETED->value, TaskStatus::CANCELLED->value, TaskStatus::FAILED->value])]
    private TaskStatus $status = TaskStatus::PENDING;

    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '优先级'])]
    #[Assert\Range(min: 1, max: 100)]
    private int $priority = 1;

    /**
     * @var array<string, mixed>
     */
    #[ORM\Column(type: Types::JSON, options: ['comment' => '任务数据'])]
    #[Assert\Type(type: 'array')]
    private array $data = [];

    #[ORM\Column(type: Types::INTEGER, nullable: true, options: ['comment' => '分配的作业员ID'])]
    #[Assert\Positive]
    private ?int $assignedWorker = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '分配时间'])]
    #[Assert\Type(type: '\DateTimeImmutable')]
    private ?\DateTimeImmutable $assignedAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '开始时间'])]
    #[Assert\Type(type: '\DateTimeImmutable')]
    private ?\DateTimeImmutable $startedAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '完成时间'])]
    #[Assert\Type(type: '\DateTimeImmutable')]
    private ?\DateTimeImmutable $completedAt = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '备注'])]
    #[Assert\Length(max: 1000)]
    private ?string $notes = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '任务描述'])]
    #[Assert\Length(max: 1000)]
    private ?string $description = null;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true, options: ['comment' => '作业位置'])]
    #[Assert\Length(max: 100)]
    private ?string $location = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function getType(): TaskType
    {
        return $this->type;
    }

    public function setType(TaskType $type): void
    {
        $this->type = $type;
    }

    public function getStatus(): TaskStatus
    {
        return $this->status;
    }

    public function setStatus(TaskStatus $status): void
    {
        $this->status = $status;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function setPriority(int $priority): void
    {
        $this->priority = $priority;
    }

    /**
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function setData(array $data): void
    {
        $this->data = $data;
    }

    /**
     * 获取指定键的数据值，提供类型安全的访问方式
     */
    public function getDataValue(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    /**
     * 设置指定键的数据值
     */
    public function setDataValue(string $key, mixed $value): void
    {
        $this->data[$key] = $value;
    }

    /**
     * 检查是否包含指定键
     */
    public function hasDataKey(string $key): bool
    {
        return array_key_exists($key, $this->data);
    }

    public function getAssignedWorker(): ?int
    {
        return $this->assignedWorker;
    }

    public function setAssignedWorker(?int $assignedWorker): void
    {
        $this->assignedWorker = $assignedWorker;
    }

    public function getAssignedAt(): ?\DateTimeImmutable
    {
        return $this->assignedAt;
    }

    public function setAssignedAt(?\DateTimeImmutable $assignedAt): void
    {
        $this->assignedAt = $assignedAt;
    }

    public function getStartedAt(): ?\DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function setStartedAt(?\DateTimeImmutable $startedAt): void
    {
        $this->startedAt = $startedAt;
    }

    public function getCompletedAt(): ?\DateTimeImmutable
    {
        return $this->completedAt;
    }

    public function setCompletedAt(?\DateTimeImmutable $completedAt): void
    {
        $this->completedAt = $completedAt;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): void
    {
        $this->notes = $notes;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(?string $location): void
    {
        $this->location = $location;
    }

    public function __toString(): string
    {
        return "Task #{$this->id} ({$this->type->value})";
    }
}
