<?php

declare(strict_types=1);

namespace Tourze\WarehouseOperationBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\DoctrineUserBundle\Traits\BlameableAware;

/**
 * 盘点计划实体
 *
 * 定义盘点计划的配置和执行策略，支持多种盘点模式。
 * 贫血模型，仅包含数据属性和访问器。
 */
#[ORM\Entity]
#[ORM\Table(name: 'ims_wms_count_plan', options: ['comment' => '盘点计划'])]
class CountPlan implements \Stringable
{
    use TimestampableAware;
    use BlameableAware;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, options: ['comment' => 'ID'])]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 100, options: ['comment' => '计划名称'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    private string $name = '';

    #[ORM\Column(type: Types::STRING, length: 20, options: ['comment' => '盘点类型'])]
    #[Assert\NotBlank]
    #[Assert\Choice(choices: ['full', 'cycle', 'abc', 'random', 'spot'])]
    private string $countType = 'cycle';

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '计划描述'])]
    #[Assert\Length(max: 1000)]
    private ?string $description = null;

    /**
     * @var array<string, mixed>
     */
    #[ORM\Column(type: Types::JSON, options: ['comment' => '盘点范围配置'])]
    #[Assert\Type(type: 'array')]
    private array $scope = [];

    /**
     * @var array<string, mixed>
     */
    #[ORM\Column(type: Types::JSON, options: ['comment' => '调度配置'])]
    #[Assert\Type(type: 'array')]
    private array $schedule = [];

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true, options: ['comment' => '计划开始日期'])]
    #[Assert\Type(type: '\DateTimeImmutable')]
    private ?\DateTimeImmutable $startDate = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true, options: ['comment' => '计划结束日期'])]
    #[Assert\Type(type: '\DateTimeImmutable')]
    private ?\DateTimeImmutable $endDate = null;

    #[ORM\Column(type: Types::STRING, length: 20, options: ['comment' => '执行状态'])]
    #[Assert\Choice(choices: ['draft', 'scheduled', 'running', 'paused', 'completed', 'cancelled'])]
    private string $status = 'draft';

    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '优先级(1-100)'])]
    #[Assert\Range(min: 1, max: 100)]
    private int $priority = 50;

    #[ORM\Column(type: Types::BOOLEAN, options: ['comment' => '是否启用'])]
    #[Assert\Type(type: 'bool')]
    private bool $isActive = true;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getCountType(): string
    {
        return $this->countType;
    }

    public function setCountType(string $countType): void
    {
        $this->countType = $countType;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    /**
     * @return array<string, mixed>
     */
    public function getScope(): array
    {
        return $this->scope;
    }

    /**
     * @param array<string, mixed> $scope
     */
    public function setScope(array $scope): void
    {
        $this->scope = $scope;
    }

    /**
     * 获取指定键的范围配置值，提供类型安全的访问方式
     */
    public function getScopeValue(string $key, mixed $default = null): mixed
    {
        return $this->scope[$key] ?? $default;
    }

    /**
     * 设置指定键的范围配置值
     */
    public function setScopeValue(string $key, mixed $value): void
    {
        $this->scope[$key] = $value;
    }

    /**
     * 检查是否包含指定键的范围配置
     */
    public function hasScopeKey(string $key): bool
    {
        return array_key_exists($key, $this->scope);
    }

    /**
     * @return array<string, mixed>
     */
    public function getSchedule(): array
    {
        return $this->schedule;
    }

    /**
     * @param array<string, mixed> $schedule
     */
    public function setSchedule(array $schedule): void
    {
        $this->schedule = $schedule;
    }

    /**
     * 获取指定键的调度配置值，提供类型安全的访问方式
     */
    public function getScheduleValue(string $key, mixed $default = null): mixed
    {
        return $this->schedule[$key] ?? $default;
    }

    /**
     * 设置指定键的调度配置值
     */
    public function setScheduleValue(string $key, mixed $value): void
    {
        $this->schedule[$key] = $value;
    }

    /**
     * 检查是否包含指定键的调度配置
     */
    public function hasScheduleKey(string $key): bool
    {
        return array_key_exists($key, $this->schedule);
    }

    public function getStartDate(): ?\DateTimeImmutable
    {
        return $this->startDate;
    }

    public function setStartDate(?\DateTimeImmutable $startDate): void
    {
        $this->startDate = $startDate;
    }

    public function getEndDate(): ?\DateTimeImmutable
    {
        return $this->endDate;
    }

    public function setEndDate(?\DateTimeImmutable $endDate): void
    {
        $this->endDate = $endDate;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
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

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): void
    {
        $this->isActive = $isActive;
    }

    public function __toString(): string
    {
        return "CountPlan #{$this->id} ({$this->name})";
    }
}
