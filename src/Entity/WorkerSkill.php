<?php

declare(strict_types=1);

namespace Tourze\WarehouseOperationBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\DoctrineUserBundle\Traits\BlameableAware;

/**
 * 作业员技能档案实体
 *
 * 记录作业员的技能水平和能力认证，用于智能任务分配。
 * 贫血模型，仅包含数据属性和访问器。
 */
#[ORM\Entity]
#[ORM\Table(name: 'ims_wms_worker_skill', options: ['comment' => '作业员技能档案'])]
class WorkerSkill implements \Stringable
{
    use TimestampableAware;
    use BlameableAware;

    /** @var int|null 自动增长主键由数据库写入 */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, options: ['comment' => 'ID'])]
    // @phpstan-ignore-next-line Doctrine 在持久化/加载时会注入 int
    private $id = null;

    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '作业员ID'])]
    #[Assert\NotBlank]
    #[Assert\Positive]
    private int $workerId = 0;

    #[ORM\Column(type: Types::STRING, length: 100, options: ['comment' => '作业员姓名'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    private string $workerName = '';

    #[ORM\Column(type: Types::STRING, length: 50, options: ['comment' => '技能类别'])]
    #[Assert\NotBlank]
    #[Assert\Choice(choices: ['picking', 'packing', 'quality', 'counting', 'equipment', 'hazardous', 'cold_storage'])]
    private string $skillCategory = '';

    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '技能等级(1-10)'])]
    #[Assert\Range(min: 1, max: 10)]
    private int $skillLevel = 1;

    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '技能分数(1-100)'])]
    #[Assert\Range(min: 1, max: 100)]
    private int $skillScore = 1;

    /**
     * @var array<string, mixed>
     */
    #[ORM\Column(type: Types::JSON, options: ['comment' => '认证信息'])]
    #[Assert\Type(type: 'array')]
    private array $certifications = [];

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true, options: ['comment' => '认证日期'])]
    #[Assert\Type(type: '\DateTimeImmutable')]
    private ?\DateTimeImmutable $certifiedAt = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true, options: ['comment' => '认证到期日期'])]
    #[Assert\Type(type: '\DateTimeImmutable')]
    private ?\DateTimeImmutable $expiresAt = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['comment' => '是否启用'])]
    #[Assert\Type(type: 'bool')]
    private bool $isActive = true;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '备注'])]
    #[Assert\Length(max: 500)]
    private ?string $notes = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getWorkerId(): int
    {
        return $this->workerId;
    }

    public function setWorkerId(int $workerId): void
    {
        $this->workerId = $workerId;
    }

    public function getWorkerName(): string
    {
        return $this->workerName;
    }

    public function setWorkerName(string $workerName): void
    {
        $this->workerName = $workerName;
    }

    public function getSkillCategory(): string
    {
        return $this->skillCategory;
    }

    public function setSkillCategory(string $skillCategory): void
    {
        $this->skillCategory = $skillCategory;
    }

    public function getSkillLevel(): int
    {
        return $this->skillLevel;
    }

    public function setSkillLevel(int $skillLevel): void
    {
        $this->skillLevel = $skillLevel;
    }

    public function getSkillScore(): int
    {
        return $this->skillScore;
    }

    public function setSkillScore(int $skillScore): void
    {
        $this->skillScore = $skillScore;
    }

    /**
     * @return array<string, mixed>
     */
    public function getCertifications(): array
    {
        return $this->certifications;
    }

    /**
     * @param array<string, mixed> $certifications
     */
    public function setCertifications(array $certifications): void
    {
        $this->certifications = $certifications;
    }

    /**
     * 获取指定键的认证信息值，提供类型安全的访问方式
     */
    public function getCertificationValue(string $key, mixed $default = null): mixed
    {
        return $this->certifications[$key] ?? $default;
    }

    /**
     * 设置指定键的认证信息值
     */
    public function setCertificationValue(string $key, mixed $value): void
    {
        $this->certifications[$key] = $value;
    }

    /**
     * 检查是否包含指定键的认证信息
     */
    public function hasCertificationKey(string $key): bool
    {
        return array_key_exists($key, $this->certifications);
    }

    public function getCertifiedAt(): ?\DateTimeImmutable
    {
        return $this->certifiedAt;
    }

    public function setCertifiedAt(?\DateTimeImmutable $certifiedAt): void
    {
        $this->certifiedAt = $certifiedAt;
    }

    public function isCertified(): bool
    {
        return null !== $this->certifiedAt
               && (null === $this->expiresAt || $this->expiresAt > new \DateTimeImmutable());
    }

    public function getExpiresAt(): ?\DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(?\DateTimeImmutable $expiresAt): void
    {
        $this->expiresAt = $expiresAt;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): void
    {
        $this->isActive = $isActive;
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
        return "WorkerSkill #{$this->id} ({$this->workerName} - {$this->skillCategory})";
    }
}
