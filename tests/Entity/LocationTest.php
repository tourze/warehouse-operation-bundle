<?php

namespace Tourze\WarehouseOperationBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;
use Tourze\WarehouseOperationBundle\Entity\Location;
use Tourze\WarehouseOperationBundle\Entity\Shelf;

/**
 * @internal
 */
#[CoversClass(Location::class)]
final class LocationTest extends AbstractEntityTestCase
{
    protected function createEntity(): object
    {
        return new Location();
    }

    /**
     * @return iterable<string, array{string, \DateTimeImmutable}>
     */
    public static function propertiesProvider(): iterable
    {
        return [
            'createTime' => ['createTime', new \DateTimeImmutable()],
            'updateTime' => ['updateTime', new \DateTimeImmutable()],
        ];
    }

    private Location $location;

    protected function setUp(): void
    {
        parent::setUp();
        $this->location = new Location();
    }

    public function testGetIdInitialValueIsNull(): void
    {
        $this->assertNull($this->location->getId());
    }

    public function testSetAndGetShelfValidShelfReturnsShelf(): void
    {
        $shelf = new Shelf();
        $this->location->setShelf($shelf);

        $this->assertSame($shelf, $this->location->getShelf());
    }

    public function testSetAndGetShelfNullValueReturnsNull(): void
    {
        $this->location->setShelf(null);

        $this->assertNull($this->location->getShelf());
    }

    public function testSetAndGetTitleValidStringReturnsTitle(): void
    {
        $title = 'A01';
        $this->location->setTitle($title);

        $this->assertSame($title, $this->location->getTitle());
    }

    public function testSetAndGetTitleNullValueReturnsNull(): void
    {
        $this->location->setTitle(null);

        $this->assertNull($this->location->getTitle());
    }

    public function testSetAndGetCreateTimeValidDateTimeReturnsDateTime(): void
    {
        $dateTime = new \DateTimeImmutable();
        $this->location->setCreateTime($dateTime);

        $this->assertSame($dateTime, $this->location->getCreateTime());
    }

    public function testSetAndGetUpdateTimeValidDateTimeReturnsDateTime(): void
    {
        $dateTime = new \DateTimeImmutable();
        $this->location->setUpdateTime($dateTime);

        $this->assertSame($dateTime, $this->location->getUpdateTime());
    }

    public function testSetAndGetCreatedByValidStringReturnsCreatedBy(): void
    {
        $createdBy = 'admin';
        $this->location->setCreatedBy($createdBy);

        $this->assertSame($createdBy, $this->location->getCreatedBy());
    }

    public function testSetAndGetUpdatedByValidStringReturnsUpdatedBy(): void
    {
        $updatedBy = 'system';
        $this->location->setUpdatedBy($updatedBy);

        $this->assertSame($updatedBy, $this->location->getUpdatedBy());
    }
}
