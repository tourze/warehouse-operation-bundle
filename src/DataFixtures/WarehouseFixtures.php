<?php

namespace Tourze\WarehouseOperationBundle\DataFixtures;

use Doctrine\Persistence\ObjectManager;
use Tourze\WarehouseOperationBundle\Entity\Warehouse;

class WarehouseFixtures extends AppFixtures
{
    public const WAREHOUSE_REFERENCE_PREFIX = 'warehouse_';
    public const WAREHOUSE_COUNT = 8;

    public function load(ObjectManager $manager): void
    {
        for ($i = 0; $i < self::WAREHOUSE_COUNT; ++$i) {
            $warehouse = $this->createWarehouse();
            $manager->persist($warehouse);
            $this->addReference(self::WAREHOUSE_REFERENCE_PREFIX . $i, $warehouse);
        }

        $specialWarehouses = [
            ['code' => 'WH001', 'name' => '主仓库', 'contactName' => '张经理', 'contactTel' => '13800138001'],
            ['code' => 'DC002', 'name' => '配送中心', 'contactName' => '李主管', 'contactTel' => '13800138002'],
            ['code' => 'FC003', 'name' => '前置仓', 'contactName' => '王管理员', 'contactTel' => '13800138003'],
        ];

        foreach ($specialWarehouses as $index => $data) {
            $warehouse = new Warehouse();
            $warehouse->setCode($data['code']);
            $warehouse->setName($data['name']);
            $warehouse->setContactName($data['contactName']);
            $warehouse->setContactTel($data['contactTel']);

            $createdAt = $this->faker->dateTimeBetween('-90 days', '-1 day');
            $warehouse->setCreateTime(\DateTimeImmutable::createFromMutable($createdAt));
            $warehouse->setCreatedBy($this->faker->userName());

            $manager->persist($warehouse);
            $this->addReference(self::WAREHOUSE_REFERENCE_PREFIX . (self::WAREHOUSE_COUNT + $index), $warehouse);
        }

        $manager->flush();
    }

    private function createWarehouse(): Warehouse
    {
        $warehouse = new Warehouse();
        $warehouse->setCode($this->generateWarehouseCode());
        $warehouse->setName($this->faker->company() . '仓库');

        if ($this->faker->boolean(70)) {
            $warehouse->setContactName($this->faker->name());
        }

        if ($this->faker->boolean(60)) {
            $warehouse->setContactTel($this->faker->phoneNumber());
        }

        $createdAt = $this->faker->dateTimeBetween('-180 days', '-1 day');
        $warehouse->setCreateTime(\DateTimeImmutable::createFromMutable($createdAt));
        $warehouse->setCreatedBy($this->faker->userName());

        return $warehouse;
    }
}
