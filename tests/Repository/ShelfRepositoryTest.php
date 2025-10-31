<?php

namespace Tourze\WarehouseOperationBundle\Tests\Repository;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;
use Tourze\WarehouseOperationBundle\Entity\Shelf;
use Tourze\WarehouseOperationBundle\Entity\Warehouse;
use Tourze\WarehouseOperationBundle\Entity\Zone;
use Tourze\WarehouseOperationBundle\Repository\ShelfRepository;

/**
 * @internal
 */
#[CoversClass(ShelfRepository::class)]
#[RunTestsInSeparateProcesses]
final class ShelfRepositoryTest extends AbstractRepositoryTestCase
{
    private ?ShelfRepository $repository = null;

    public function testConstructCorrectlyRegistersEntityClass(): void
    {
        $this->assertInstanceOf(ShelfRepository::class, $this->getRepository());
    }

    public function testFindAllReturnsArray(): void
    {
        $result = $this->getRepository()->findAll();
        $this->assertIsArray($result);
    }

    public function testFindReturnsShelfOrNull(): void
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

    public function testFindOneByReturnsShelfOrNull(): void
    {
        $result = $this->getRepository()->findOneBy(['title' => 'non-existent']);
        $this->assertNull($result);
    }

    public function testSaveEntityShouldPersistToDatabase(): void
    {
        $entity = $this->createShelfEntity();
        $entity->setTitle('New Shelf');

        $this->getRepository()->save($entity);

        $this->assertNotNull($entity->getId());
        $found = $this->getRepository()->find($entity->getId());
        $this->assertInstanceOf(Shelf::class, $found);
        $this->assertEquals('New Shelf', $found->getTitle());
    }

    public function testRemoveEntityShouldDeleteFromDatabase(): void
    {
        $entity = $this->createShelfEntity();
        self::getEntityManager()->persist($entity);
        self::getEntityManager()->flush();
        $id = $entity->getId();

        $this->getRepository()->remove($entity);

        $found = $this->getRepository()->find($id);
        $this->assertNull($found);
    }

    private function createShelfEntity(): Shelf
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

        return $shelf;
    }

    protected function onSetUp(): void
    {
        // Repository tests don't require additional setup
    }

    protected function createNewEntity(): object
    {
        return $this->createShelfWithDependencies();
    }

    private function createShelfWithDependencies(): Shelf
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

        return $shelf;
    }

    protected function getRepository(): ShelfRepository
    {
        if (null === $this->repository) {
            $repository = self::getEntityManager()->getRepository(Shelf::class);
            $this->assertInstanceOf(ShelfRepository::class, $repository);
            $this->repository = $repository;
        }

        return $this->repository;
    }
}
