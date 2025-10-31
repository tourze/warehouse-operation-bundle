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
use Tourze\WarehouseOperationBundle\Repository\ZoneRepository;

#[ORM\Entity(repositoryClass: ZoneRepository::class)]
#[ORM\Table(name: 'ims_wms_zone', options: ['comment' => '库区'])]
class Zone implements \Stringable
{
    use TimestampableAware;
    use BlameableAware;

    /** @var int|null 自动增长主键由数据库写入 */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, options: ['comment' => 'ID'])]
    // @phpstan-ignore-next-line Doctrine 在持久化/加载时会注入 int
    private $id = null;

    #[ORM\ManyToOne(inversedBy: 'zones', cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?Warehouse $warehouse = null;

    #[ORM\Column(length: 60, options: ['comment' => '库区名称'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 60)]
    private ?string $title = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true, options: ['comment' => '面积'])]
    #[Assert\Length(max: 13)]
    #[Assert\Regex(pattern: '/^\d+(\.\d{1,2})?$/', message: '面积必须为数字格式')]
    private ?string $acreage = null;

    #[ORM\Column(length: 60, options: ['comment' => '类型'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 60)]
    private ?string $type = null;

    /**
     * @var Collection<int, Shelf>
     */
    #[ORM\OneToMany(mappedBy: 'zone', targetEntity: Shelf::class)]
    private Collection $shelves;

    public function __construct()
    {
        $this->shelves = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getWarehouse(): ?Warehouse
    {
        return $this->warehouse;
    }

    public function setWarehouse(?Warehouse $warehouse): void
    {
        $this->warehouse = $warehouse;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getAcreage(): ?string
    {
        return $this->acreage;
    }

    public function setAcreage(?string $acreage): void
    {
        $this->acreage = $acreage;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    /**
     * @return Collection<int, Shelf>
     */
    public function getShelves(): Collection
    {
        return $this->shelves;
    }

    public function addShelf(Shelf $shelf): self
    {
        if (!$this->shelves->contains($shelf)) {
            $this->shelves->add($shelf);
            $shelf->setZone($this);
        }

        return $this;
    }

    public function removeShelf(Shelf $shelf): self
    {
        if ($this->shelves->removeElement($shelf)) {
            // set the owning side to null (unless already changed)
            if ($shelf->getZone() === $this) {
                $shelf->setZone(null);
            }
        }

        return $this;
    }

    public function __toString(): string
    {
        return (string) $this->id;
    }
}
