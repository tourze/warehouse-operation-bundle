<?php

namespace Tourze\WarehouseOperationBundle\Tests\Repository;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;
use Tourze\WarehouseOperationBundle\Entity\Warehouse;
use Tourze\WarehouseOperationBundle\Entity\Zone;
use Tourze\WarehouseOperationBundle\Repository\ZoneRepository;

/**
 * @internal
 */
#[CoversClass(ZoneRepository::class)]
#[RunTestsInSeparateProcesses]
final class ZoneRepositoryTest extends AbstractRepositoryTestCase
{
    private ?ZoneRepository $repository = null;

    public function testConstructCorrectlyRegistersEntityClass(): void
    {
        $this->assertInstanceOf(ZoneRepository::class, $this->getRepository());
    }

    public function testFindAllReturnsArray(): void
    {
        $result = $this->getRepository()->findAll();
        $this->assertIsArray($result);
    }

    public function testFindReturnsZoneOrNull(): void
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

    public function testFindOneByReturnsZoneOrNull(): void
    {
        $result = $this->getRepository()->findOneBy(['title' => 'non-existent']);
        $this->assertNull($result);
    }

    public function testSaveEntityShouldPersistToDatabase(): void
    {
        $entity = $this->createZoneEntity();
        $entity->setTitle('New Zone');

        $this->getRepository()->save($entity);

        $this->assertNotNull($entity->getId());
        $found = $this->getRepository()->find($entity->getId());
        $this->assertInstanceOf(Zone::class, $found);
        $this->assertEquals('New Zone', $found->getTitle());
    }

    public function testRemoveEntityShouldDeleteFromDatabase(): void
    {
        $entity = $this->createZoneEntity();
        self::getEntityManager()->persist($entity);
        self::getEntityManager()->flush();
        $id = $entity->getId();

        $this->getRepository()->remove($entity);

        $found = $this->getRepository()->find($id);
        $this->assertNull($found);
    }

    private function createZoneEntity(): Zone
    {
        $warehouse = new Warehouse();
        $warehouse->setCode('WH' . uniqid());
        $warehouse->setName('Test Warehouse');
        self::getEntityManager()->persist($warehouse);

        $zone = new Zone();
        $zone->setTitle('Test Zone');
        $zone->setType('Storage');
        $zone->setWarehouse($warehouse);

        return $zone;
    }

    protected function onSetUp(): void
    {
        // Repository tests don't require additional setup
    }

    protected function createNewEntity(): object
    {
        return $this->createZoneWithDependencies();
    }

    private function createZoneWithDependencies(): Zone
    {
        $warehouse = new Warehouse();
        $warehouse->setCode('WH' . uniqid());
        $warehouse->setName('Test Warehouse ' . uniqid());

        $zone = new Zone();
        $zone->setTitle('Test Zone ' . uniqid());
        $zone->setType('Storage');
        $zone->setWarehouse($warehouse);

        return $zone;
    }

    protected function getRepository(): ZoneRepository
    {
        if (null === $this->repository) {
            $repository = self::getEntityManager()->getRepository(Zone::class);
            $this->assertInstanceOf(ZoneRepository::class, $repository);
            $this->repository = $repository;
        }

        return $this->repository;
    }
}
