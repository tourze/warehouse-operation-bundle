<?php

declare(strict_types=1);

namespace Tourze\WarehouseOperationBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\DoctrineUserBundle\Traits\BlameableAware;

/**
 * 任务调度规则实体
 *
 * 定义任务调度的业务规则和约束条件。
 * 贫血模型，仅包含数据属性和访问器。
 */
#[ORM\Entity]
#[ORM\Table(name: 'ims_wms_task_rule', options: ['comment' => '任务调度规则'])]
class TaskRule implements \Stringable
{
    use TimestampableAware;
    use BlameableAware;

    /** @var int|null 自动增长主键由数据库写入 */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, options: ['comment' => 'ID'])]
    // @phpstan-ignore-next-line Doctrine 在持久化/加载时会注入 int
    private $id = null;

    #[ORM\Column(type: Types::STRING, length: 100, options: ['comment' => '规则名称'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    private string $name = '';

    #[ORM\Column(type: Types::STRING, length: 50, options: ['comment' => '规则类型'])]
    #[Assert\NotBlank]
    #[Assert\Choice(choices: ['priority', 'skill_match', 'workload_balance', 'constraint', 'optimization'])]
    private string $ruleType = 'priority';

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '规则描述'])]
    #[Assert\Length(max: 500)]
    private ?string $description = null;

    /**
     * @var array<string, mixed>
     */
    #[ORM\Column(type: Types::JSON, options: ['comment' => '规则条件'])]
    #[Assert\Type(type: 'array')]
    private array $conditions = [];

    /**
     * @var array<string, mixed>
     */
    #[ORM\Column(type: Types::JSON, options: ['comment' => '规则动作'])]
    #[Assert\Type(type: 'array')]
    private array $actions = [];

    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '规则优先级(1-100)'])]
    #[Assert\Range(min: 1, max: 100)]
    private int $priority = 50;

    #[ORM\Column(type: Types::BOOLEAN, options: ['comment' => '是否启用'])]
    #[Assert\Type(type: 'bool')]
    private bool $isActive = true;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true, options: ['comment' => '生效开始日期'])]
    #[Assert\Type(type: '\DateTimeImmutable')]
    private ?\DateTimeImmutable $effectiveFrom = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true, options: ['comment' => '生效结束日期'])]
    #[Assert\Type(type: '\DateTimeImmutable')]
    private ?\DateTimeImmutable $effectiveTo = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '备注'])]
    #[Assert\Length(max: 1000)]
    private ?string $notes = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getRuleType(): string
    {
        return $this->ruleType;
    }

    public function setRuleType(string $ruleType): void
    {
        $this->ruleType = $ruleType;
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
    public function getConditions(): array
    {
        return $this->conditions;
    }

    /**
     * @param array<string, mixed> $conditions
     */
    public function setConditions(array $conditions): void
    {
        $this->conditions = $conditions;
    }

    /**
     * 获取指定键的规则条件值，提供类型安全的访问方式
     */
    public function getConditionValue(string $key, mixed $default = null): mixed
    {
        return $this->conditions[$key] ?? $default;
    }

    /**
     * 设置指定键的规则条件值
     */
    public function setConditionValue(string $key, mixed $value): void
    {
        $this->conditions[$key] = $value;
    }

    /**
     * 检查是否包含指定键的规则条件
     */
    public function hasConditionKey(string $key): bool
    {
        return array_key_exists($key, $this->conditions);
    }

    /**
     * @return array<string, mixed>
     */
    public function getActions(): array
    {
        return $this->actions;
    }

    /**
     * @param array<string, mixed> $actions
     */
    public function setActions(array $actions): void
    {
        $this->actions = $actions;
    }

    /**
     * 获取指定键的规则动作值，提供类型安全的访问方式
     */
    public function getActionValue(string $key, mixed $default = null): mixed
    {
        return $this->actions[$key] ?? $default;
    }

    /**
     * 设置指定键的规则动作值
     */
    public function setActionValue(string $key, mixed $value): void
    {
        $this->actions[$key] = $value;
    }

    /**
     * 检查是否包含指定键的规则动作
     */
    public function hasActionKey(string $key): bool
    {
        return array_key_exists($key, $this->actions);
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

    public function getEffectiveFrom(): ?\DateTimeImmutable
    {
        return $this->effectiveFrom;
    }

    public function setEffectiveFrom(?\DateTimeImmutable $effectiveFrom): void
    {
        $this->effectiveFrom = $effectiveFrom;
    }

    public function getEffectiveTo(): ?\DateTimeImmutable
    {
        return $this->effectiveTo;
    }

    public function setEffectiveTo(?\DateTimeImmutable $effectiveTo): void
    {
        $this->effectiveTo = $effectiveTo;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): void
    {
        $this->notes = $notes;
    }

    public function __toString(): string
    {
        return "TaskRule #{$this->id} ({$this->name})";
    }
}
