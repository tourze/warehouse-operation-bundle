<?php

namespace Tourze\WarehouseOperationBundle\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Tourze\WarehouseOperationBundle\Entity\CountTask;
use Tourze\WarehouseOperationBundle\Entity\Warehouse;
use Tourze\WarehouseOperationBundle\Enum\TaskStatus;
use Tourze\WarehouseOperationBundle\Enum\TaskType;

class CountTaskFixtures extends AppFixtures implements DependentFixtureInterface
{
    public const COUNT_TASK_REFERENCE_PREFIX = 'count_task_';
    public const COUNT_TASK_COUNT = 5;

    public function load(ObjectManager $manager): void
    {
        for ($i = 0; $i < self::COUNT_TASK_COUNT; ++$i) {
            $countTask = $this->createCountTask($i);
            $manager->persist($countTask);
            $this->addReference(self::COUNT_TASK_REFERENCE_PREFIX . $i, $countTask);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            WarehouseFixtures::class,
        ];
    }

    private function createCountTask(int $index): CountTask
    {
        $countTask = new CountTask();
        $countTask->setType(TaskType::COUNT);

        // 设置不同的任务状态，让数据更真实
        $statuses = [TaskStatus::PENDING, TaskStatus::ASSIGNED, TaskStatus::IN_PROGRESS, TaskStatus::COMPLETED, TaskStatus::PAUSED];
        $countTask->setStatus($statuses[$index % count($statuses)]);

        // 设置优先级
        $countTask->setPriority($this->faker->numberBetween(1, 10));

        // 设置任务数据，包含盘点相关信息
        $warehouseIndex = $index % WarehouseFixtures::WAREHOUSE_COUNT;
        $warehouse = $this->getReference(WarehouseFixtures::WAREHOUSE_REFERENCE_PREFIX . $warehouseIndex, Warehouse::class);

        $countTask->setData([
            'warehouse_id' => $warehouse->getId(),
            'zone_type' => $this->generateZoneType(),
            'count_type' => $this->faker->randomElement(['全盘', '循环盘点', '抽盘']),
            'location_range' => [
                'from' => $this->generateLocationTitle(),
                'to' => $this->generateLocationTitle(),
            ],
            'expected_items' => $this->faker->numberBetween(50, 500),
            'inventory_snapshot_date' => $this->faker->dateTimeThisMonth()->format('Y-m-d H:i:s'),
        ]);

        // 根据状态设置相关时间和作业员
        if (in_array($countTask->getStatus(), [TaskStatus::ASSIGNED, TaskStatus::IN_PROGRESS, TaskStatus::COMPLETED, TaskStatus::PAUSED], true)) {
            $countTask->setAssignedWorker($this->faker->numberBetween(1001, 1020));
            $countTask->setAssignedAt(\DateTimeImmutable::createFromMutable($this->faker->dateTimeBetween('-7 days', '-1 day')));
        }

        if (in_array($countTask->getStatus(), [TaskStatus::IN_PROGRESS, TaskStatus::COMPLETED], true)) {
            $countTask->setStartedAt(\DateTimeImmutable::createFromMutable($this->faker->dateTimeBetween('-3 days', 'now')));
        }

        if (TaskStatus::COMPLETED === $countTask->getStatus()) {
            $countTask->setCompletedAt(\DateTimeImmutable::createFromMutable($this->faker->dateTimeBetween('-1 day', 'now')));
        }

        // 设置备注
        if ($this->faker->boolean(30)) {
            $notes = [
                '盘点过程中发现部分商品位置不准确',
                '库存数据与系统记录基本一致',
                '发现少量破损商品，已单独记录',
                '盘点区域清洁度良好',
                '建议调整货架布局提高效率',
            ];
            /** @var string $note */
            $note = $this->faker->randomElement($notes);
            $countTask->setNotes($note);
        }

        return $countTask;
    }
}
