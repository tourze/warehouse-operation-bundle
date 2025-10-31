<?php

namespace Tourze\WarehouseOperationBundle\Tests\Entity;

use Doctrine\Common\Collections\Collection;
use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;
use Tourze\WarehouseOperationBundle\Entity\Warehouse;
use Tourze\WarehouseOperationBundle\Entity\Zone;

/**
 * @internal
 */
#[CoversClass(Warehouse::class)]
final class WarehouseTest extends AbstractEntityTestCase
{
    protected function createEntity(): object
    {
        return new Warehouse();
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

    private Warehouse $warehouse;

    protected function setUp(): void
    {
        parent::setUp();
        $this->warehouse = new Warehouse();
    }

    public function testGetIdInitialValueIsNull(): void
    {
        $this->assertNull($this->warehouse->getId());
    }

    public function testSetAndGetCodeValidStringReturnsCode(): void
    {
        $code = 'WH001';
        $this->warehouse->setCode($code);

        $this->assertSame($code, $this->warehouse->getCode());
    }

    public function testSetAndGetNameValidStringReturnsName(): void
    {
        $name = '主仓库';
        $this->warehouse->setName($name);

        $this->assertSame($name, $this->warehouse->getName());
    }

    public function testToStringWhenIdIsNullReturnsNewWarehouse(): void
    {
        $this->assertSame('New Warehouse', (string) $this->warehouse);
    }

    public function testToStringWhenIdIsNotZeroReturnsFormattedString(): void
    {
        $warehouse = new Warehouse();
        $warehouse->setName('测试仓库');
        $warehouse->setCode('TEST');

        // 使用反射设置ID，以模拟从数据库获取的对象
        $reflection = new \ReflectionClass($warehouse);
        $idProperty = $reflection->getProperty('id');
        $idProperty->setAccessible(true);
        $idProperty->setValue($warehouse, 1);

        $this->assertSame('测试仓库(TEST)', (string) $warehouse);
    }

    public function testSetAndGetContactNameValidStringReturnsContactName(): void
    {
        $contactName = '张三';
        $this->warehouse->setContactName($contactName);

        $this->assertSame($contactName, $this->warehouse->getContactName());
    }

    public function testSetAndGetContactNameNullValueReturnsNull(): void
    {
        $this->warehouse->setContactName(null);

        $this->assertNull($this->warehouse->getContactName());
    }

    public function testSetAndGetContactTelValidStringReturnsContactTel(): void
    {
        $contactTel = '13800138000';
        $this->warehouse->setContactTel($contactTel);

        $this->assertSame($contactTel, $this->warehouse->getContactTel());
    }

    public function testSetAndGetContactTelNullValueReturnsNull(): void
    {
        $this->warehouse->setContactTel(null);

        $this->assertNull($this->warehouse->getContactTel());
    }

    public function testGetZonesInitialStateReturnsEmptyCollection(): void
    {
        $zones = $this->warehouse->getZones();

        $this->assertInstanceOf(Collection::class, $zones);
        $this->assertTrue($zones->isEmpty());
    }

    public function testAddZoneNewZoneAddsZoneToCollection(): void
    {
        $zone = new Zone();
        $result = $this->warehouse->addZone($zone);

        $this->assertSame($this->warehouse, $result);
        $this->assertTrue($this->warehouse->getZones()->contains($zone));
        $this->assertSame($this->warehouse, $zone->getWarehouse());
    }

    public function testAddZoneExistingZoneDoesNotAddZoneAgain(): void
    {
        $zone = new Zone();
        $this->warehouse->addZone($zone);
        $result = $this->warehouse->addZone($zone);

        $this->assertSame($this->warehouse, $result);
        $this->assertSame(1, $this->warehouse->getZones()->count());
    }

    public function testRemoveZoneExistingZoneRemovesZoneFromCollection(): void
    {
        $zone = new Zone();
        $this->warehouse->addZone($zone);
        $result = $this->warehouse->removeZone($zone);

        $this->assertSame($this->warehouse, $result);
        $this->assertFalse($this->warehouse->getZones()->contains($zone));
        $this->assertNull($zone->getWarehouse());
    }

    public function testRemoveZoneNonExistingZoneReturnsCurrentInstance(): void
    {
        $zone = new Zone();
        $result = $this->warehouse->removeZone($zone);

        $this->assertSame($this->warehouse, $result);
        $this->assertFalse($this->warehouse->getZones()->contains($zone));
    }

    public function testRemoveZoneWithAnotherWarehouseDoesNotNullifyWarehouseReference(): void
    {
        $zone = new Zone();
        $this->warehouse->addZone($zone);

        $anotherWarehouse = new Warehouse();
        $zone->setWarehouse($anotherWarehouse);

        $result = $this->warehouse->removeZone($zone);

        $this->assertSame($this->warehouse, $result);
        $this->assertFalse($this->warehouse->getZones()->contains($zone));
        $this->assertSame($anotherWarehouse, $zone->getWarehouse());
    }

    public function testSetAndGetCreateTimeValidDateTimeReturnsDateTime(): void
    {
        $dateTime = new \DateTimeImmutable();
        $this->warehouse->setCreateTime($dateTime);

        $this->assertSame($dateTime, $this->warehouse->getCreateTime());
    }

    public function testSetAndGetUpdateTimeValidDateTimeReturnsDateTime(): void
    {
        $dateTime = new \DateTimeImmutable();
        $this->warehouse->setUpdateTime($dateTime);

        $this->assertSame($dateTime, $this->warehouse->getUpdateTime());
    }

    public function testSetAndGetCreatedByValidStringReturnsCreatedBy(): void
    {
        $createdBy = 'admin';
        $this->warehouse->setCreatedBy($createdBy);

        $this->assertSame($createdBy, $this->warehouse->getCreatedBy());
    }

    public function testSetAndGetUpdatedByValidStringReturnsUpdatedBy(): void
    {
        $updatedBy = 'system';
        $this->warehouse->setUpdatedBy($updatedBy);

        $this->assertSame($updatedBy, $this->warehouse->getUpdatedBy());
    }
}
