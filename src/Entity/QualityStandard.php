<?php

declare(strict_types=1);

namespace Tourze\WarehouseOperationBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\DoctrineUserBundle\Traits\BlameableAware;

/**
 * 质检标准实体
 *
 * 定义商品质检的标准和规则，支持多维度质检项目配置。
 * 贫血模型，仅包含数据属性和访问器。
 */
#[ORM\Entity]
#[ORM\Table(name: 'ims_wms_quality_standard', options: ['comment' => '质检标准'])]
class QualityStandard implements \Stringable
{
    use TimestampableAware;
    use BlameableAware;

    /** @var int|null 自动增长主键由数据库写入 */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, options: ['comment' => 'ID'])]
    // @phpstan-ignore-next-line Doctrine 在持久化/加载时会注入 int
    private $id = null;

    #[ORM\Column(type: Types::STRING, length: 100, options: ['comment' => '标准名称'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    private string $name = '';

    #[ORM\Column(type: Types::STRING, length: 50, options: ['comment' => '商品类别'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 50)]
    private string $productCategory = '';

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '标准描述'])]
    #[Assert\Length(max: 1000)]
    private ?string $description = null;

    /**
     * @var array<string, mixed>
     */
    #[ORM\Column(type: Types::JSON, options: ['comment' => '质检项目配置'])]
    #[Assert\Type(type: 'array')]
    private array $checkItems = [];

    #[ORM\Column(type: Types::BOOLEAN, options: ['comment' => '是否启用'])]
    #[Assert\Type(type: 'bool')]
    private bool $isActive = true;

    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '优先级(1-100)'])]
    #[Assert\Range(min: 1, max: 100)]
    private int $priority = 1;

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

    public function getProductCategory(): string
    {
        return $this->productCategory;
    }

    public function setProductCategory(string $productCategory): void
    {
        $this->productCategory = $productCategory;
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
    public function getCheckItems(): array
    {
        return $this->checkItems;
    }

    /**
     * @param array<string, mixed> $checkItems
     */
    public function setCheckItems(array $checkItems): void
    {
        $this->checkItems = $checkItems;
    }

    /**
     * 获取指定键的质检项目配置值，提供类型安全的访问方式
     */
    public function getCheckItemValue(string $key, mixed $default = null): mixed
    {
        return $this->checkItems[$key] ?? $default;
    }

    /**
     * 设置指定键的质检项目配置值
     */
    public function setCheckItemValue(string $key, mixed $value): void
    {
        $this->checkItems[$key] = $value;
    }

    /**
     * 检查是否包含指定键的质检项目配置
     */
    public function hasCheckItemKey(string $key): bool
    {
        return array_key_exists($key, $this->checkItems);
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): void
    {
        $this->isActive = $isActive;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function setPriority(int $priority): void
    {
        $this->priority = $priority;
    }

    public function __toString(): string
    {
        return "QualityStandard #{$this->id} ({$this->name})";
    }
}
