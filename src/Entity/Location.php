<?php

declare(strict_types=1);

namespace Tourze\WarehouseOperationBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\DoctrineUserBundle\Traits\BlameableAware;
use Tourze\WarehouseOperationBundle\Repository\LocationRepository;

/**
 * @see https://www.woshipm.com/pd/3355437.html
 */
#[ORM\Entity(repositoryClass: LocationRepository::class)]
#[ORM\Table(name: 'ims_wms_location', options: ['comment' => '货位'])]
class Location implements \Stringable
{
    use TimestampableAware;
    use BlameableAware;

    /** @var int|null 自动增长主键由数据库写入 */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, options: ['comment' => 'ID'])]
    // @phpstan-ignore-next-line Doctrine 在持久化/加载时会注入 int
    private $id = null;

    #[ORM\ManyToOne(inversedBy: 'locations', cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    private ?Shelf $shelf = null;

    #[ORM\Column(length: 100, nullable: true, options: ['comment' => '字段说明'])]
    #[Assert\Length(max: 100)]
    private ?string $title = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getShelf(): ?Shelf
    {
        return $this->shelf;
    }

    public function setShelf(?Shelf $shelf): void
    {
        $this->shelf = $shelf;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): void
    {
        $this->title = $title;
    }

    public function __toString(): string
    {
        return (string) ($this->id ?? 'new');
    }
}
