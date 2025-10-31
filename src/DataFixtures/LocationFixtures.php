<?php

namespace Tourze\WarehouseOperationBundle\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Tourze\WarehouseOperationBundle\Entity\Location;
use Tourze\WarehouseOperationBundle\Entity\Shelf;

class LocationFixtures extends AppFixtures implements DependentFixtureInterface
{
    public const LOCATION_REFERENCE_PREFIX = 'location_';
    public const LOCATIONS_PER_SHELF = 8;

    public function load(ObjectManager $manager): void
    {
        $locationIndex = 0;
        $totalShelves = $this->getTotalShelves();

        for ($shelfIndex = 0; $shelfIndex < $totalShelves; ++$shelfIndex) {
            try {
                $shelf = $this->getReference(ShelfFixtures::SHELF_REFERENCE_PREFIX . $shelfIndex, Shelf::class);
            } catch (\Exception) {
                continue;
            }

            $locationsCount = $this->faker->numberBetween(4, self::LOCATIONS_PER_SHELF);

            for ($i = 0; $i < $locationsCount; ++$i) {
                $location = $this->createLocation($shelf);
                $manager->persist($location);
                $this->addReference(self::LOCATION_REFERENCE_PREFIX . $locationIndex, $location);
                ++$locationIndex;
            }
        }

        $manager->flush();
    }

    private function getTotalShelves(): int
    {
        $totalWarehouses = WarehouseFixtures::WAREHOUSE_COUNT + 3;
        $totalZones = $totalWarehouses * ZoneFixtures::ZONES_PER_WAREHOUSE;

        return $totalZones * ShelfFixtures::SHELVES_PER_ZONE;
    }

    private function createLocation(Shelf $shelf): Location
    {
        $location = new Location();
        $location->setShelf($shelf);

        if ($this->faker->boolean(90)) {
            $location->setTitle($this->generateLocationTitle());
        }

        $createdAt = $this->faker->dateTimeBetween('-60 days', 'now');
        $location->setCreateTime(\DateTimeImmutable::createFromMutable($createdAt));
        $location->setCreatedBy($this->faker->userName());

        return $location;
    }

    public function getDependencies(): array
    {
        return [
            ShelfFixtures::class,
        ];
    }
}
