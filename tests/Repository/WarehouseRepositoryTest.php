<?php

namespace Tourze\WarehouseOperationBundle\Tests\Repository;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;
use Tourze\WarehouseOperationBundle\Entity\Warehouse;
use Tourze\WarehouseOperationBundle\Repository\WarehouseRepository;

/**
 * @internal
 */
#[CoversClass(WarehouseRepository::class)]
#[RunTestsInSeparateProcesses]
final class WarehouseRepositoryTest extends AbstractRepositoryTestCase
{
    private ?WarehouseRepository $repository = null;

    public function testConstructCorrectlyRegistersEntityClass(): void
    {
        $this->assertInstanceOf(WarehouseRepository::class, $this->getRepository());
    }

    public function testFindAllReturnsArray(): void
    {
        $result = $this->getRepository()->findAll();
        $this->assertIsArray($result);
    }

    public function testFindReturnsWarehouseOrNull(): void
    {
        $result = $this->getRepository()->find(999999);
        $this->assertNull($result);
    }

    public function testFindByReturnsArray(): void
    {
        $result = $this->getRepository()->findBy(['code' => 'non-existent']);
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testFindOneByReturnsWarehouseOrNull(): void
    {
        $result = $this->getRepository()->findOneBy(['code' => 'non-existent']);
        $this->assertNull($result);
    }

    public function testSaveEntityShouldPersistToDatabase(): void
    {
        $entity = $this->createWarehouseEntity();
        $entity->setName('New Warehouse');

        $this->getRepository()->save($entity);

        $this->assertNotNull($entity->getId());
        $found = $this->getRepository()->find($entity->getId());
        $this->assertInstanceOf(Warehouse::class, $found);
        $this->assertEquals('New Warehouse', $found->getName());
    }

    public function testRemoveEntityShouldDeleteFromDatabase(): void
    {
        $entity = $this->createWarehouseEntity();
        self::getEntityManager()->persist($entity);
        self::getEntityManager()->flush();
        $id = $entity->getId();

        $this->getRepository()->remove($entity);

        $found = $this->getRepository()->find($id);
        $this->assertNull($found);
    }

    private function createWarehouseEntity(): Warehouse
    {
        $warehouse = new Warehouse();
        $warehouse->setCode('WH' . uniqid());
        $warehouse->setName('Test Warehouse');

        return $warehouse;
    }

    protected function onSetUp(): void
    {
        // Repository tests don't require additional setup
    }

    protected function createNewEntity(): object
    {
        $warehouse = new Warehouse();
        $warehouse->setCode('WH' . uniqid());
        $warehouse->setName('Test Warehouse');

        return $warehouse;
    }

    protected function getRepository(): WarehouseRepository
    {
        if (null === $this->repository) {
            $repository = self::getEntityManager()->getRepository(Warehouse::class);
            $this->assertInstanceOf(WarehouseRepository::class, $repository);
            $this->repository = $repository;
        }

        return $this->repository;
    }
}
