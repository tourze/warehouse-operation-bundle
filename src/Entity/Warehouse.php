<?php

declare(strict_types=1);

namespace Tourze\WarehouseOperationBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\DoctrineUserBundle\Traits\BlameableAware;
use Tourze\WarehouseOperationBundle\Repository\WarehouseRepository;

#[ORM\Entity(repositoryClass: WarehouseRepository::class)]
#[ORM\Table(name: 'ims_wms_warehouse', options: ['comment' => '仓库'])]
class Warehouse implements \Stringable
{
    use TimestampableAware;
    use BlameableAware;

    /** @var int|null 自动增长主键由数据库写入 */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, options: ['comment' => 'ID'])]
    // @phpstan-ignore-next-line Doctrine 在持久化/加载时会注入 int
    private $id = null;

    #[ORM\Column(type: Types::STRING, length: 64, unique: true, options: ['comment' => '代号'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 64)]
    private ?string $code = null;

    #[ORM\Column(type: Types::STRING, length: 100, options: ['comment' => '名称'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    private ?string $name = null;

    #[ORM\Column(length: 60, nullable: true, options: ['comment' => '字段说明'])]
    #[Assert\Length(max: 60)]
    private ?string $contactName = null;

    #[ORM\Column(length: 120, nullable: true, options: ['comment' => '字段说明'])]
    #[Assert\Length(max: 120)]
    private ?string $contactTel = null;

    /**
     * @var Collection<int, Zone>
     */
    #[ORM\OneToMany(mappedBy: 'warehouse', targetEntity: Zone::class)]
    private Collection $zones;

    public function __construct()
    {
        $this->zones = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): void
    {
        $this->code = $code;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function __toString(): string
    {
        if (null === $this->getId() || 0 === $this->getId()) {
            return 'New Warehouse';
        }

        $name = $this->getName() ?? 'Unknown';
        $code = $this->getCode() ?? 'N/A';

        return "{$name}({$code})";
    }

    public function getContactName(): ?string
    {
        return $this->contactName;
    }

    public function setContactName(?string $contactName): void
    {
        $this->contactName = $contactName;
    }

    public function getContactTel(): ?string
    {
        return $this->contactTel;
    }

    public function setContactTel(?string $contactTel): void
    {
        $this->contactTel = $contactTel;
    }

    /**
     * @return Collection<int, Zone>
     */
    public function getZones(): Collection
    {
        return $this->zones;
    }

    public function addZone(Zone $zone): self
    {
        if (!$this->zones->contains($zone)) {
            $this->zones->add($zone);
            $zone->setWarehouse($this);
        }

        return $this;
    }

    public function removeZone(Zone $zone): self
    {
        if ($this->zones->removeElement($zone)) {
            // set the owning side to null (unless already changed)
            if ($zone->getWarehouse() === $this) {
                $zone->setWarehouse(null);
            }
        }

        return $this;
    }
}
