<?php

namespace Tourze\WarehouseOperationBundle\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Tourze\WarehouseOperationBundle\Entity\OutboundTask;
use Tourze\WarehouseOperationBundle\Entity\Warehouse;
use Tourze\WarehouseOperationBundle\Enum\TaskStatus;
use Tourze\WarehouseOperationBundle\Enum\TaskType;

class OutboundTaskFixtures extends AppFixtures implements DependentFixtureInterface
{
    public const OUTBOUND_TASK_REFERENCE_PREFIX = 'outbound_task_';
    public const OUTBOUND_TASK_COUNT = 10;

    public function load(ObjectManager $manager): void
    {
        for ($i = 0; $i < self::OUTBOUND_TASK_COUNT; ++$i) {
            $outboundTask = $this->createOutboundTask($i);
            $manager->persist($outboundTask);
            $this->addReference(self::OUTBOUND_TASK_REFERENCE_PREFIX . $i, $outboundTask);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            WarehouseFixtures::class,
        ];
    }

    private function createOutboundTask(int $index): OutboundTask
    {
        $outboundTask = new OutboundTask();
        $outboundTask->setType(TaskType::OUTBOUND);

        // 设置不同的任务状态
        $statuses = [TaskStatus::PENDING, TaskStatus::ASSIGNED, TaskStatus::IN_PROGRESS, TaskStatus::COMPLETED, TaskStatus::PAUSED, TaskStatus::CANCELLED];
        $outboundTask->setStatus($statuses[$index % count($statuses)]);

        // 出库任务优先级通常较高
        $outboundTask->setPriority($this->faker->numberBetween(6, 10));

        // 设置任务数据，包含出库相关信息
        $warehouseIndex = $index % WarehouseFixtures::WAREHOUSE_COUNT;
        $warehouse = $this->getReference(WarehouseFixtures::WAREHOUSE_REFERENCE_PREFIX . $warehouseIndex, Warehouse::class);

        $outboundTask->setData([
            'warehouse_id' => $warehouse->getId(),
            'sales_order_id' => 'SO' . str_pad((string) ($index + 1), 6, '0', STR_PAD_LEFT),
            'customer_info' => [
                'name' => $this->faker->name(),
                'address' => $this->faker->address(),
                'phone' => $this->faker->phoneNumber(),
            ],
            'shipping_method' => $this->faker->randomElement(['标准快递', '次日达', '当日达', '自提', '货到付款']),
            'required_delivery_date' => $this->faker->dateTimeBetween('now', '+7 days')->format('Y-m-d'),
            'items' => $this->generateOutboundItems(),
            'picking_zone' => $this->generateZoneType(),
            'shipping_carrier' => $this->faker->randomElement(['顺丰速运', '京东物流', '菜鸟物流', '申通快递', 'EMS']),
            'special_requirements' => $this->faker->boolean(20) ? $this->faker->randomElement([
                '易碎品，小心轻放',
                '冷链运输',
                '防潮包装',
                '加急处理',
                '定时配送',
            ]) : null,
        ]);

        // 根据状态设置相关时间和作业员
        if (in_array($outboundTask->getStatus(), [TaskStatus::ASSIGNED, TaskStatus::IN_PROGRESS, TaskStatus::COMPLETED, TaskStatus::PAUSED, TaskStatus::CANCELLED], true)) {
            $outboundTask->setAssignedWorker($this->faker->numberBetween(3001, 3020));
            $outboundTask->setAssignedAt(\DateTimeImmutable::createFromMutable($this->faker->dateTimeBetween('-4 days', '-1 day')));
        }

        if (in_array($outboundTask->getStatus(), [TaskStatus::IN_PROGRESS, TaskStatus::COMPLETED], true)) {
            $outboundTask->setStartedAt(\DateTimeImmutable::createFromMutable($this->faker->dateTimeBetween('-2 days', 'now')));
        }

        if (TaskStatus::COMPLETED === $outboundTask->getStatus()) {
            $outboundTask->setCompletedAt(\DateTimeImmutable::createFromMutable($this->faker->dateTimeBetween('-1 day', 'now')));
        }

        // 设置备注
        if ($this->faker->boolean(35)) {
            $notes = [
                '拣货完成，包装正常',
                '客户要求加急处理',
                '部分商品缺货，需要补充库存',
                '包装需要特殊处理',
                '客户地址偏远，需要确认配送',
                '拣货路径优化，效率提升',
                '发现商品质量问题，已更换',
                '按时完成拣货任务',
            ];
            /** @var string $note */
            $note = $this->faker->randomElement($notes);
            $outboundTask->setNotes($note);
        }

        return $outboundTask;
    }

    /**
     * 生成出库商品信息
     *
     * @return array<int, array<string, mixed>>
     */
    private function generateOutboundItems(): array
    {
        $itemCount = $this->faker->numberBetween(2, 6);
        $items = [];

        for ($i = 0; $i < $itemCount; ++$i) {
            /** @var string $baseProductName */
            $baseProductName = $this->faker->randomElement([
                '华为P60 Pro',
                'OPPO Find X6',
                'vivo X90',
                '小米Air笔记本',
                '华硕游戏本',
                'Bose耳机',
                'Nike运动鞋',
                'Adidas运动服',
                'Zara连衣裙',
                'H&M T恤',
            ]);
            /** @var string $colorSuffix */
            $colorSuffix = $this->faker->randomElement([' 蓝色', ' 红色', ' 绿色', ' 紫色', '']);
            $items[] = [
                'sku' => 'SKU' . $this->faker->unique()->numerify('######'),
                'product_name' => $baseProductName . $colorSuffix,
                'required_quantity' => $this->faker->numberBetween(1, 20),
                'unit' => $this->faker->randomElement(['台', '件', '双', '套']),
                'location' => $this->generateLocationTitle(),
                'batch_preference' => $this->faker->randomElement(['先进先出', '后进先出', '指定批次', '无要求']),
            ];
        }

        return $items;
    }
}
