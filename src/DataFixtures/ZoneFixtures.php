<?php

namespace Tourze\WarehouseOperationBundle\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Tourze\WarehouseOperationBundle\Entity\Warehouse;
use Tourze\WarehouseOperationBundle\Entity\Zone;

class ZoneFixtures extends AppFixtures implements DependentFixtureInterface
{
    public const ZONE_REFERENCE_PREFIX = 'zone_';
    public const ZONES_PER_WAREHOUSE = 4;

    public function load(ObjectManager $manager): void
    {
        $zoneIndex = 0;
        $totalWarehouses = WarehouseFixtures::WAREHOUSE_COUNT + 3;

        for ($warehouseIndex = 0; $warehouseIndex < $totalWarehouses; ++$warehouseIndex) {
            $warehouse = $this->getReference(WarehouseFixtures::WAREHOUSE_REFERENCE_PREFIX . $warehouseIndex, Warehouse::class);

            $zonesCount = $this->faker->numberBetween(2, self::ZONES_PER_WAREHOUSE);

            for ($i = 0; $i < $zonesCount; ++$i) {
                $zone = $this->createZone($warehouse);
                $manager->persist($zone);
                $this->addReference(self::ZONE_REFERENCE_PREFIX . $zoneIndex, $zone);
                ++$zoneIndex;
            }
        }

        $manager->flush();
    }

    private function createZone(Warehouse $warehouse): Zone
    {
        $zone = new Zone();
        $zone->setWarehouse($warehouse);
        $zone->setTitle($this->generateZoneTitle());
        $zone->setType($this->generateZoneType());

        if ($this->faker->boolean(80)) {
            $zone->setAcreage((string) $this->faker->randomFloat(2, 50.0, 5000.0));
        }

        $createdAt = $this->faker->dateTimeBetween('-120 days', 'now');
        $zone->setCreateTime(\DateTimeImmutable::createFromMutable($createdAt));
        $zone->setCreatedBy($this->faker->userName());

        return $zone;
    }

    private function generateZoneTitle(): string
    {
        /** @var string $prefix */
        $prefix = $this->faker->randomElement(['A', 'B', 'C', 'D', 'E']);
        $number = $this->faker->numberBetween(1, 99);
        /** @var string $suffix */
        $suffix = $this->faker->randomElement(['区', '库区', '存储区']);

        return $prefix . str_pad((string) $number, 2, '0', STR_PAD_LEFT) . $suffix;
    }

    public function getDependencies(): array
    {
        return [
            WarehouseFixtures::class,
        ];
    }
}
