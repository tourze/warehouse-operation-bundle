<?php

namespace Tourze\WarehouseOperationBundle\Tests\Entity;

use Doctrine\Common\Collections\Collection;
use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;
use Tourze\WarehouseOperationBundle\Entity\Shelf;
use Tourze\WarehouseOperationBundle\Entity\Warehouse;
use Tourze\WarehouseOperationBundle\Entity\Zone;

/**
 * @internal
 */
#[CoversClass(Zone::class)]
final class ZoneTest extends AbstractEntityTestCase
{
    protected function createEntity(): object
    {
        return new Zone();
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

    private Zone $zone;

    protected function setUp(): void
    {
        parent::setUp();
        $this->zone = new Zone();
    }

    public function testGetIdInitialValueIsNull(): void
    {
        $this->assertNull($this->zone->getId());
    }

    public function testSetAndGetWarehouseValidWarehouseReturnsWarehouse(): void
    {
        $warehouse = new Warehouse();
        $this->zone->setWarehouse($warehouse);

        $this->assertSame($warehouse, $this->zone->getWarehouse());
    }

    public function testSetAndGetWarehouseNullValueReturnsNull(): void
    {
        $this->zone->setWarehouse(null);

        $this->assertNull($this->zone->getWarehouse());
    }

    public function testSetAndGetTitleValidStringReturnsTitle(): void
    {
        $title = 'A区';
        $this->zone->setTitle($title);

        $this->assertSame($title, $this->zone->getTitle());
    }

    public function testSetAndGetAcreageValidStringReturnsAcreage(): void
    {
        $acreage = '100.50';
        $this->zone->setAcreage($acreage);

        $this->assertSame($acreage, $this->zone->getAcreage());
    }

    public function testSetAndGetAcreageNullValueReturnsNull(): void
    {
        $this->zone->setAcreage(null);

        $this->assertNull($this->zone->getAcreage());
    }

    public function testSetAndGetTypeValidStringReturnsType(): void
    {
        $type = '普通仓';
        $this->zone->setType($type);

        $this->assertSame($type, $this->zone->getType());
    }

    public function testGetShelvesInitialStateReturnsEmptyCollection(): void
    {
        $shelves = $this->zone->getShelves();

        $this->assertInstanceOf(Collection::class, $shelves);
        $this->assertTrue($shelves->isEmpty());
    }

    public function testAddShelfNewShelfAddsShelfToCollection(): void
    {
        $shelf = new Shelf();
        $result = $this->zone->addShelf($shelf);

        $this->assertSame($this->zone, $result);
        $this->assertTrue($this->zone->getShelves()->contains($shelf));
        $this->assertSame($this->zone, $shelf->getZone());
    }

    public function testAddShelfExistingShelfDoesNotAddShelfAgain(): void
    {
        $shelf = new Shelf();
        $this->zone->addShelf($shelf);
        $result = $this->zone->addShelf($shelf);

        $this->assertSame($this->zone, $result);
        $this->assertSame(1, $this->zone->getShelves()->count());
    }

    public function testRemoveShelfExistingShelfRemovesShelfFromCollection(): void
    {
        $shelf = new Shelf();
        $this->zone->addShelf($shelf);
        $result = $this->zone->removeShelf($shelf);

        $this->assertSame($this->zone, $result);
        $this->assertFalse($this->zone->getShelves()->contains($shelf));
        $this->assertNull($shelf->getZone());
    }

    public function testRemoveShelfNonExistingShelfReturnsCurrentInstance(): void
    {
        $shelf = new Shelf();
        $result = $this->zone->removeShelf($shelf);

        $this->assertSame($this->zone, $result);
        $this->assertFalse($this->zone->getShelves()->contains($shelf));
    }

    public function testRemoveShelfWithAnotherZoneDoesNotNullifyZoneReference(): void
    {
        $shelf = new Shelf();
        $this->zone->addShelf($shelf);

        $anotherZone = new Zone();
        $shelf->setZone($anotherZone);

        $result = $this->zone->removeShelf($shelf);

        $this->assertSame($this->zone, $result);
        $this->assertFalse($this->zone->getShelves()->contains($shelf));
        $this->assertSame($anotherZone, $shelf->getZone());
    }

    public function testSetAndGetCreateTimeValidDateTimeReturnsDateTime(): void
    {
        $dateTime = new \DateTimeImmutable();
        $this->zone->setCreateTime($dateTime);

        $this->assertSame($dateTime, $this->zone->getCreateTime());
    }

    public function testSetAndGetUpdateTimeValidDateTimeReturnsDateTime(): void
    {
        $dateTime = new \DateTimeImmutable();
        $this->zone->setUpdateTime($dateTime);

        $this->assertSame($dateTime, $this->zone->getUpdateTime());
    }

    public function testSetAndGetCreatedByValidStringReturnsCreatedBy(): void
    {
        $createdBy = 'admin';
        $this->zone->setCreatedBy($createdBy);

        $this->assertSame($createdBy, $this->zone->getCreatedBy());
    }

    public function testSetAndGetUpdatedByValidStringReturnsUpdatedBy(): void
    {
        $updatedBy = 'system';
        $this->zone->setUpdatedBy($updatedBy);

        $this->assertSame($updatedBy, $this->zone->getUpdatedBy());
    }
}
