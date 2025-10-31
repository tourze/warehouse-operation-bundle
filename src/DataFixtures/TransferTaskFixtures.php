<?php

namespace Tourze\WarehouseOperationBundle\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Tourze\WarehouseOperationBundle\Entity\TransferTask;
use Tourze\WarehouseOperationBundle\Entity\Warehouse;
use Tourze\WarehouseOperationBundle\Enum\TaskStatus;
use Tourze\WarehouseOperationBundle\Enum\TaskType;

class TransferTaskFixtures extends AppFixtures implements DependentFixtureInterface
{
    public const TRANSFER_TASK_REFERENCE_PREFIX = 'transfer_task_';
    public const TRANSFER_TASK_COUNT = 7;

    public function load(ObjectManager $manager): void
    {
        for ($i = 0; $i < self::TRANSFER_TASK_COUNT; ++$i) {
            $transferTask = $this->createTransferTask($i);
            $manager->persist($transferTask);
            $this->addReference(self::TRANSFER_TASK_REFERENCE_PREFIX . $i, $transferTask);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            WarehouseFixtures::class,
        ];
    }

    private function createTransferTask(int $index): TransferTask
    {
        $transferTask = new TransferTask();
        $transferTask->setType(TaskType::TRANSFER);

        // 设置不同的任务状态
        $statuses = [TaskStatus::PENDING, TaskStatus::ASSIGNED, TaskStatus::IN_PROGRESS, TaskStatus::COMPLETED, TaskStatus::CANCELLED, TaskStatus::PAUSED, TaskStatus::FAILED];
        $transferTask->setStatus($statuses[$index % count($statuses)]);

        // 调拨任务优先级中等
        $transferTask->setPriority($this->faker->numberBetween(3, 7));

        // 设置任务数据，包含调拨相关信息
        $sourceWarehouseIndex = $index % WarehouseFixtures::WAREHOUSE_COUNT;
        $sourceWarehouse = $this->getReference(WarehouseFixtures::WAREHOUSE_REFERENCE_PREFIX . $sourceWarehouseIndex, Warehouse::class);

        // 选择不同的目标仓库
        $targetWarehouseIndex = ($index + 1) % WarehouseFixtures::WAREHOUSE_COUNT;
        $targetWarehouse = $this->getReference(WarehouseFixtures::WAREHOUSE_REFERENCE_PREFIX . $targetWarehouseIndex, Warehouse::class);

        $transferTask->setData([
            'source_warehouse_id' => $sourceWarehouse->getId(),
            'target_warehouse_id' => $targetWarehouse->getId(),
            'transfer_order_id' => 'TF' . str_pad((string) ($index + 1), 6, '0', STR_PAD_LEFT),
            'transfer_type' => $this->faker->randomElement(['仓间调拨', '区域调整', '库位优化', '紧急调拨', '季节性调拨']),
            'transfer_reason' => $this->faker->randomElement([
                '库存平衡',
                '客户需求',
                '库存优化',
                '安全库存补充',
                '仓储成本优化',
                '运输成本优化',
                '季节性调整',
            ]),
            'items' => $this->generateTransferItems(),
            'source_zone' => $this->generateZoneType(),
            'target_zone' => $this->generateZoneType(),
            'transportation_method' => $this->faker->randomElement(['公司车辆', '第三方物流', '专线运输', '快递配送']),
            'expected_completion_date' => $this->faker->dateTimeBetween('now', '+10 days')->format('Y-m-d'),
            'special_handling' => $this->faker->boolean(25) ? $this->faker->randomElement([
                '温控运输',
                '防震包装',
                '分批运输',
                '加急处理',
                '保险运输',
            ]) : null,
        ]);

        // 根据状态设置相关时间和作业员
        if (in_array($transferTask->getStatus(), [TaskStatus::ASSIGNED, TaskStatus::IN_PROGRESS, TaskStatus::COMPLETED, TaskStatus::CANCELLED, TaskStatus::PAUSED, TaskStatus::FAILED], true)) {
            $transferTask->setAssignedWorker($this->faker->numberBetween(5001, 5015)); // 调拨员编号
            $transferTask->setAssignedAt(\DateTimeImmutable::createFromMutable($this->faker->dateTimeBetween('-8 days', '-1 day')));
        }

        if (in_array($transferTask->getStatus(), [TaskStatus::IN_PROGRESS, TaskStatus::COMPLETED, TaskStatus::FAILED], true)) {
            $transferTask->setStartedAt(\DateTimeImmutable::createFromMutable($this->faker->dateTimeBetween('-5 days', 'now')));
        }

        if (in_array($transferTask->getStatus(), [TaskStatus::COMPLETED, TaskStatus::FAILED], true)) {
            $transferTask->setCompletedAt(\DateTimeImmutable::createFromMutable($this->faker->dateTimeBetween('-2 days', 'now')));
        }

        // 设置备注
        if ($this->faker->boolean(45)) {
            if (TaskStatus::COMPLETED === $transferTask->getStatus()) {
                $notes = [
                    '调拨任务顺利完成，货物安全到达',
                    '运输过程无异常，商品完好',
                    '提前完成调拨任务',
                    '目标仓库已确认收货',
                    '调拨流程规范，效率良好',
                ];
            } elseif (TaskStatus::FAILED === $transferTask->getStatus()) {
                $notes = [
                    '运输过程中发生意外，货物受损',
                    '目标仓库拒收，需要重新安排',
                    '运输车辆故障，影响调拨进度',
                    '商品包装不当，造成损失',
                    '天气原因导致调拨延误',
                ];
            } elseif (TaskStatus::CANCELLED === $transferTask->getStatus()) {
                $notes = [
                    '需求变更，取消调拨计划',
                    '目标仓库库位不足，暂停调拨',
                    '运输成本过高，取消本次调拨',
                    '商品质量问题，终止调拨',
                ];
            } else {
                $notes = [
                    '调拨准备工作进行中',
                    '正在安排运输车辆',
                    '商品清点完成，等待装车',
                    '运输途中，预计按时到达',
                    '已联系目标仓库准备收货',
                ];
            }
            /** @var string $note */
            $note = $this->faker->randomElement($notes);
            $transferTask->setNotes($note);
        }

        return $transferTask;
    }

    /**
     * 生成调拨商品信息
     *
     * @return array<int, array<string, mixed>>
     */
    private function generateTransferItems(): array
    {
        $itemCount = $this->faker->numberBetween(2, 5);
        $items = [];

        for ($i = 0; $i < $itemCount; ++$i) {
            /** @var string $baseProductName */
            $baseProductName = $this->faker->randomElement([
                '联想ThinkBook笔记本',
                'HP激光打印机',
                '罗技无线鼠标',
                '飞利浦显示器',
                'AMD处理器',
                'NVIDIA显卡',
                '金士顿内存条',
                '西部数据硬盘',
                '海康威视摄像头',
                '大华录像机',
            ]);
            /** @var string $versionSuffix */
            $versionSuffix = $this->faker->randomElement([' 标准版', ' 升级版', ' 专业版', '']);
            $items[] = [
                'sku' => 'SKU' . $this->faker->unique()->numerify('######'),
                'product_name' => $baseProductName . $versionSuffix,
                'quantity_to_transfer' => $this->faker->numberBetween(5, 100),
                'unit' => $this->faker->randomElement(['台', '个', '套', '件', '箱']),
                'source_location' => $this->generateLocationTitle(),
                'target_location' => $this->generateLocationTitle(),
                'batch_number' => date('Ymd') . $this->faker->numerify('###'),
                'unit_value' => $this->faker->randomFloat(2, 50, 5000), // 单价
                'total_value' => null, // 在实际业务中会根据数量和单价计算
                'handling_requirements' => $this->faker->boolean(30) ? $this->faker->randomElement([
                    '易碎品',
                    '防潮',
                    '避光',
                    '立放',
                    '防静电',
                ]) : null,
            ];
        }

        // 计算总价值
        foreach ($items as $key => $item) {
            $items[$key]['total_value'] = $item['quantity_to_transfer'] * $item['unit_value'];
        }

        return $items;
    }
}
