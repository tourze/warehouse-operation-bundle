<?php

namespace Tourze\WarehouseOperationBundle\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Tourze\WarehouseOperationBundle\Entity\Shelf;
use Tourze\WarehouseOperationBundle\Entity\Zone;

class ShelfFixtures extends AppFixtures implements DependentFixtureInterface
{
    public const SHELF_REFERENCE_PREFIX = 'shelf_';
    public const SHELVES_PER_ZONE = 6;

    public function load(ObjectManager $manager): void
    {
        $shelfIndex = 0;
        $totalZones = $this->getTotalZones();

        for ($zoneIndex = 0; $zoneIndex < $totalZones; ++$zoneIndex) {
            try {
                $zone = $this->getReference(ZoneFixtures::ZONE_REFERENCE_PREFIX . $zoneIndex, Zone::class);
            } catch (\Exception) {
                continue;
            }

            $shelvesCount = $this->faker->numberBetween(3, self::SHELVES_PER_ZONE);

            for ($i = 0; $i < $shelvesCount; ++$i) {
                $shelf = $this->createShelf($zone);
                $manager->persist($shelf);
                $this->addReference(self::SHELF_REFERENCE_PREFIX . $shelfIndex, $shelf);
                ++$shelfIndex;
            }
        }

        $manager->flush();
    }

    private function getTotalZones(): int
    {
        $totalWarehouses = WarehouseFixtures::WAREHOUSE_COUNT + 3;

        return $totalWarehouses * ZoneFixtures::ZONES_PER_WAREHOUSE;
    }

    private function createShelf(Zone $zone): Shelf
    {
        $shelf = new Shelf();
        $shelf->setZone($zone);
        $shelf->setTitle($this->generateShelfTitle());

        $createdAt = $this->faker->dateTimeBetween('-90 days', 'now');
        $shelf->setCreateTime(\DateTimeImmutable::createFromMutable($createdAt));
        $shelf->setCreatedBy($this->faker->userName());

        return $shelf;
    }

    private function generateShelfTitle(): string
    {
        /** @var string $row */
        $row = $this->faker->randomElement(['A', 'B', 'C', 'D', 'E', 'F']);
        $number = $this->faker->numberBetween(1, 50);

        return $row . str_pad((string) $number, 2, '0', STR_PAD_LEFT) . '货架';
    }

    public function getDependencies(): array
    {
        return [
            ZoneFixtures::class,
        ];
    }
}
