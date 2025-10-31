<?php

namespace Tourze\WarehouseOperationBundle\Tests\Repository;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;
use Tourze\WarehouseOperationBundle\Entity\Location;
use Tourze\WarehouseOperationBundle\Entity\Shelf;
use Tourze\WarehouseOperationBundle\Entity\Warehouse;
use Tourze\WarehouseOperationBundle\Entity\Zone;
use Tourze\WarehouseOperationBundle\Repository\LocationRepository;

/**
 * @internal
 */
#[CoversClass(LocationRepository::class)]
#[RunTestsInSeparateProcesses]
final class LocationRepositoryTest extends AbstractRepositoryTestCase
{
    private ?LocationRepository $repository = null;

    public function testConstructCorrectlyRegistersEntityClass(): void
    {
        $this->assertInstanceOf(LocationRepository::class, $this->getRepository());
    }

    public function testFindAllReturnsArray(): void
    {
        $result = $this->getRepository()->findAll();
        $this->assertIsArray($result);
    }

    public function testFindReturnsLocationOrNull(): void
    {
        $result = $this->getRepository()->find(999999);
        $this->assertNull($result);
    }

    public function testFindByReturnsArray(): void
    {
        $result = $this->getRepository()->findBy(['title' => 'non-existent']);
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testFindOneByReturnsLocationOrNull(): void
    {
        $result = $this->getRepository()->findOneBy(['title' => 'non-existent']);
        $this->assertNull($result);
    }

    public function testSaveEntityShouldPersistToDatabase(): void
    {
        $entity = $this->createLocationEntity();
        $entity->setTitle('New Location');

        $this->getRepository()->save($entity);

        $this->assertNotNull($entity->getId());
        $found = $this->getRepository()->find($entity->getId());
        $this->assertInstanceOf(Location::class, $found);
        $this->assertEquals('New Location', $found->getTitle());
    }

    public function testRemoveEntityShouldDeleteFromDatabase(): void
    {
        $entity = $this->createLocationEntity();
        self::getEntityManager()->persist($entity);
        self::getEntityManager()->flush();
        $id = $entity->getId();

        $this->getRepository()->remove($entity);

        $found = $this->getRepository()->find($id);
        $this->assertNull($found);
    }

    private function createLocationEntity(): Location
    {
        $warehouse = new Warehouse();
        $warehouse->setCode('WH' . uniqid());
        $warehouse->setName('Test Warehouse');
        self::getEntityManager()->persist($warehouse);

        $zone = new Zone();
        $zone->setTitle('Test Zone');
        $zone->setType('Storage');
        $zone->setWarehouse($warehouse);
        self::getEntityManager()->persist($zone);

        $shelf = new Shelf();
        $shelf->setTitle('Test Shelf');
        $shelf->setZone($zone);
        self::getEntityManager()->persist($shelf);

        $location = new Location();
        $location->setShelf($shelf);

        return $location;
    }

    protected function onSetUp(): void
    {
        // Repository tests don't require additional setup
    }

    protected function createNewEntity(): object
    {
        return $this->createLocationWithDependencies();
    }

    private function createLocationWithDependencies(): Location
    {
        $warehouse = new Warehouse();
        $warehouse->setCode('WH' . uniqid());
        $warehouse->setName('Test Warehouse ' . uniqid());

        $zone = new Zone();
        $zone->setTitle('Test Zone ' . uniqid());
        $zone->setType('Storage');
        $zone->setWarehouse($warehouse);

        $shelf = new Shelf();
        $shelf->setTitle('Test Shelf ' . uniqid());
        $shelf->setZone($zone);

        $location = new Location();
        $location->setShelf($shelf);
        $location->setTitle('Test Location ' . uniqid());

        return $location;
    }

    protected function getRepository(): LocationRepository
    {
        if (null === $this->repository) {
            $repository = self::getEntityManager()->getRepository(Location::class);
            $this->assertInstanceOf(LocationRepository::class, $repository);
            $this->repository = $repository;
        }

        return $this->repository;
    }
}
