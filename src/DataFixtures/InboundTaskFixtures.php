<?php

namespace Tourze\WarehouseOperationBundle\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Tourze\WarehouseOperationBundle\Entity\InboundTask;
use Tourze\WarehouseOperationBundle\Entity\Warehouse;
use Tourze\WarehouseOperationBundle\Enum\TaskStatus;
use Tourze\WarehouseOperationBundle\Enum\TaskType;

class InboundTaskFixtures extends AppFixtures implements DependentFixtureInterface
{
    public const INBOUND_TASK_REFERENCE_PREFIX = 'inbound_task_';
    public const INBOUND_TASK_COUNT = 8;

    public function load(ObjectManager $manager): void
    {
        for ($i = 0; $i < self::INBOUND_TASK_COUNT; ++$i) {
            $inboundTask = $this->createInboundTask($i);
            $manager->persist($inboundTask);
            $this->addReference(self::INBOUND_TASK_REFERENCE_PREFIX . $i, $inboundTask);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            WarehouseFixtures::class,
        ];
    }

    private function createInboundTask(int $index): InboundTask
    {
        $inboundTask = new InboundTask();
        $inboundTask->setType(TaskType::INBOUND);

        // 设置不同的任务状态，让数据更真实
        $statuses = [TaskStatus::PENDING, TaskStatus::ASSIGNED, TaskStatus::IN_PROGRESS, TaskStatus::COMPLETED, TaskStatus::CANCELLED, TaskStatus::FAILED];
        $inboundTask->setStatus($statuses[$index % count($statuses)]);

        // 设置优先级，入库任务通常优先级较高
        $inboundTask->setPriority($this->faker->numberBetween(5, 10));

        // 设置任务数据，包含入库相关信息
        $warehouseIndex = $index % WarehouseFixtures::WAREHOUSE_COUNT;
        $warehouse = $this->getReference(WarehouseFixtures::WAREHOUSE_REFERENCE_PREFIX . $warehouseIndex, Warehouse::class);

        $inboundTask->setData([
            'warehouse_id' => $warehouse->getId(),
            'purchase_order_id' => 'PO' . str_pad((string) ($index + 1), 6, '0', STR_PAD_LEFT),
            'supplier_name' => $this->faker->company(),
            'expected_arrival_date' => $this->faker->dateTimeBetween('-2 days', '+3 days')->format('Y-m-d'),
            'items' => $this->generateInboundItems(),
            'receiving_dock' => $this->faker->randomElement(['Dock A', 'Dock B', 'Dock C', 'Temp Dock']),
            'transport_info' => [
                'carrier' => $this->faker->randomElement(['SF Express', 'Deppon', 'ZTO Express', 'YTO Express']),
                'tracking_number' => $this->faker->regexify('[A-Z]{2}[0-9]{10}'),
                'vehicle_plate' => $this->faker->regexify('[A-Z]{1}[A-Z]{1}[A-Z0-9]{5}'),
            ],
        ]);

        // 根据状态设置相关时间和作业员
        if (in_array($inboundTask->getStatus(), [TaskStatus::ASSIGNED, TaskStatus::IN_PROGRESS, TaskStatus::COMPLETED, TaskStatus::CANCELLED, TaskStatus::FAILED], true)) {
            $inboundTask->setAssignedWorker($this->faker->numberBetween(2001, 2020));
            $inboundTask->setAssignedAt(\DateTimeImmutable::createFromMutable($this->faker->dateTimeBetween('-5 days', '-1 day')));
        }

        if (in_array($inboundTask->getStatus(), [TaskStatus::IN_PROGRESS, TaskStatus::COMPLETED, TaskStatus::FAILED], true)) {
            $inboundTask->setStartedAt(\DateTimeImmutable::createFromMutable($this->faker->dateTimeBetween('-2 days', 'now')));
        }

        if (in_array($inboundTask->getStatus(), [TaskStatus::COMPLETED, TaskStatus::FAILED], true)) {
            $inboundTask->setCompletedAt(\DateTimeImmutable::createFromMutable($this->faker->dateTimeBetween('-1 day', 'now')));
        }

        // 设置备注
        if ($this->faker->boolean(40)) {
            $notes = [
                'Goods arrived on time, packaging intact',
                'Found damaged packaging on some items, recorded exception',
                'Supplier delivered early, receiving went smoothly',
                'Quality check needed to confirm product specifications',
                'Receiving area congested, suggest adjusting receiving time',
                'Goods quantity matches order',
                'Found new products, need to create product records',
            ];
            /** @var string $note */
            $note = $this->faker->randomElement($notes);
            $inboundTask->setNotes($note);
        }

        return $inboundTask;
    }

    /**
     * 生成入库商品信息
     *
     * @return array<int, array<string, mixed>>
     */
    private function generateInboundItems(): array
    {
        $itemCount = $this->faker->numberBetween(3, 8);
        $items = [];

        for ($i = 0; $i < $itemCount; ++$i) {
            $skuSuffix = $this->faker->unique()->numerify('######');
            /** @var string $baseProductName */
            $baseProductName = $this->faker->randomElement([
                'Apple iPhone 15',
                'Xiaomi 13 Pro',
                'Huawei Mate 50',
                'Dell Laptop',
                'Lenovo ThinkPad',
                'Sony Wireless Headphones',
                'Canon Digital Camera',
                'Haier Refrigerator',
                'Gree Air Conditioner',
                'Midea Washing Machine',
            ]);
            /** @var string $colorSuffix */
            $colorSuffix = $this->faker->randomElement([' Black', ' White', ' Gold', ' Silver', '']);
            $items[] = [
                'sku' => 'SKU' . $skuSuffix,
                'product_name' => $baseProductName . $colorSuffix,
                'expected_quantity' => $this->faker->numberBetween(10, 100),
                'unit' => $this->faker->randomElement(['unit', 'piece', 'box', 'item']),
                'batch_number' => date('Ymd') . $this->faker->numerify('###'),
                'expiry_date' => $this->faker->boolean(30) ? $this->faker->dateTimeBetween('+30 days', '+2 years')->format('Y-m-d') : null,
            ];
        }

        return $items;
    }
}
