<?php

namespace Tourze\WarehouseOperationBundle\Tests\Entity;

use Doctrine\Common\Collections\Collection;
use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;
use Tourze\WarehouseOperationBundle\Entity\Location;
use Tourze\WarehouseOperationBundle\Entity\Shelf;
use Tourze\WarehouseOperationBundle\Entity\Zone;

/**
 * @internal
 */
#[CoversClass(Shelf::class)]
final class ShelfTest extends AbstractEntityTestCase
{
    protected function createEntity(): object
    {
        return new Shelf();
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

    private Shelf $shelf;

    protected function setUp(): void
    {
        parent::setUp();
        $this->shelf = new Shelf();
    }

    public function testGetIdInitialValueIsNull(): void
    {
        $this->assertNull($this->shelf->getId());
    }

    public function testSetAndGetZoneValidZoneReturnsZone(): void
    {
        $zone = new Zone();
        $this->shelf->setZone($zone);

        $this->assertSame($zone, $this->shelf->getZone());
    }

    public function testSetAndGetZoneNullValueReturnsNull(): void
    {
        $this->shelf->setZone(null);

        $this->assertNull($this->shelf->getZone());
    }

    public function testSetAndGetTitleValidStringReturnsTitle(): void
    {
        $title = 'A货架';
        $this->shelf->setTitle($title);
        $this->assertSame($title, $this->shelf->getTitle());
    }

    public function testGetLocationsInitialStateReturnsEmptyCollection(): void
    {
        $locations = $this->shelf->getLocations();

        $this->assertInstanceOf(Collection::class, $locations);
        $this->assertTrue($locations->isEmpty());
    }

    public function testAddLocationNewLocationAddsLocationToCollection(): void
    {
        $location = new Location();
        $result = $this->shelf->addLocation($location);

        $this->assertSame($this->shelf, $result);
        $this->assertTrue($this->shelf->getLocations()->contains($location));
        $this->assertSame($this->shelf, $location->getShelf());
    }

    public function testAddLocationExistingLocationDoesNotAddLocationAgain(): void
    {
        $location = new Location();
        $this->shelf->addLocation($location);
        $result = $this->shelf->addLocation($location);

        $this->assertSame($this->shelf, $result);
        $this->assertSame(1, $this->shelf->getLocations()->count());
    }

    public function testRemoveLocationExistingLocationRemovesLocationFromCollection(): void
    {
        $location = new Location();
        $this->shelf->addLocation($location);
        $result = $this->shelf->removeLocation($location);

        $this->assertSame($this->shelf, $result);
        $this->assertFalse($this->shelf->getLocations()->contains($location));
        $this->assertNull($location->getShelf());
    }

    public function testRemoveLocationNonExistingLocationReturnsCurrentInstance(): void
    {
        $location = new Location();
        $result = $this->shelf->removeLocation($location);

        $this->assertSame($this->shelf, $result);
        $this->assertFalse($this->shelf->getLocations()->contains($location));
    }

    public function testRemoveLocationWithAnotherShelfDoesNotNullifyShelfReference(): void
    {
        $location = new Location();
        $this->shelf->addLocation($location);

        $anotherShelf = new Shelf();
        $location->setShelf($anotherShelf);

        $result = $this->shelf->removeLocation($location);

        $this->assertSame($this->shelf, $result);
        $this->assertFalse($this->shelf->getLocations()->contains($location));
        $this->assertSame($anotherShelf, $location->getShelf());
    }

    public function testSetAndGetCreateTimeValidDateTimeReturnsDateTime(): void
    {
        $dateTime = new \DateTimeImmutable();
        $this->shelf->setCreateTime($dateTime);

        $this->assertSame($dateTime, $this->shelf->getCreateTime());
    }

    public function testSetAndGetUpdateTimeValidDateTimeReturnsDateTime(): void
    {
        $dateTime = new \DateTimeImmutable();
        $this->shelf->setUpdateTime($dateTime);

        $this->assertSame($dateTime, $this->shelf->getUpdateTime());
    }

    public function testSetAndGetCreatedByValidStringReturnsCreatedBy(): void
    {
        $createdBy = 'admin';
        $this->shelf->setCreatedBy($createdBy);

        $this->assertSame($createdBy, $this->shelf->getCreatedBy());
    }

    public function testSetAndGetUpdatedByValidStringReturnsUpdatedBy(): void
    {
        $updatedBy = 'system';
        $this->shelf->setUpdatedBy($updatedBy);

        $this->assertSame($updatedBy, $this->shelf->getUpdatedBy());
    }
}
