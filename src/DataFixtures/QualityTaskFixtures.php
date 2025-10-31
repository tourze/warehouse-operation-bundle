<?php

namespace Tourze\WarehouseOperationBundle\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Tourze\WarehouseOperationBundle\Entity\QualityTask;
use Tourze\WarehouseOperationBundle\Entity\Warehouse;
use Tourze\WarehouseOperationBundle\Enum\TaskStatus;
use Tourze\WarehouseOperationBundle\Enum\TaskType;

class QualityTaskFixtures extends AppFixtures implements DependentFixtureInterface
{
    public const QUALITY_TASK_REFERENCE_PREFIX = 'quality_task_';
    public const QUALITY_TASK_COUNT = 6;

    public function load(ObjectManager $manager): void
    {
        for ($i = 0; $i < self::QUALITY_TASK_COUNT; ++$i) {
            $qualityTask = $this->createQualityTask($i);
            $manager->persist($qualityTask);
            $this->addReference(self::QUALITY_TASK_REFERENCE_PREFIX . $i, $qualityTask);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            WarehouseFixtures::class,
        ];
    }

    private function createQualityTask(int $index): QualityTask
    {
        $qualityTask = new QualityTask();
        $qualityTask->setType(TaskType::QUALITY);

        // 设置不同的任务状态
        $statuses = [TaskStatus::PENDING, TaskStatus::ASSIGNED, TaskStatus::IN_PROGRESS, TaskStatus::COMPLETED, TaskStatus::FAILED, TaskStatus::PAUSED];
        $qualityTask->setStatus($statuses[$index % count($statuses)]);

        // 质检任务优先级中等偏高
        $qualityTask->setPriority($this->faker->numberBetween(4, 8));

        // 设置任务数据，包含质检相关信息
        $warehouseIndex = $index % WarehouseFixtures::WAREHOUSE_COUNT;
        $warehouse = $this->getReference(WarehouseFixtures::WAREHOUSE_REFERENCE_PREFIX . $warehouseIndex, Warehouse::class);

        $qualityTask->setData([
            'warehouse_id' => $warehouse->getId(),
            'inspection_type' => $this->faker->randomElement(['入库质检', '库存抽检', '退货质检', '异常质检', '定期质检']),
            'trigger_reason' => $this->faker->randomElement([
                '新货到库',
                '客户投诉',
                '定期检查',
                '系统异常',
                '供应商变更',
                '批次切换',
            ]),
            'related_order_id' => $this->faker->boolean(70) ? 'PO' . str_pad((string) ($index + 1), 6, '0', STR_PAD_LEFT) : null,
            'items' => $this->generateQualityInspectionItems(),
            'inspection_location' => $this->faker->randomElement(['质检区A', '质检区B', '临时质检台', '专业检测室']),
            'inspection_standards' => $this->generateInspectionStandards(),
            'sampling_method' => $this->faker->randomElement(['全检', '抽检10%', '抽检20%', 'AQL标准抽样', '随机抽样']),
        ]);

        // 根据状态设置相关时间和作业员
        if (in_array($qualityTask->getStatus(), [TaskStatus::ASSIGNED, TaskStatus::IN_PROGRESS, TaskStatus::COMPLETED, TaskStatus::FAILED, TaskStatus::PAUSED], true)) {
            $qualityTask->setAssignedWorker($this->faker->numberBetween(4001, 4010)); // 质检员编号
            $qualityTask->setAssignedAt(\DateTimeImmutable::createFromMutable($this->faker->dateTimeBetween('-6 days', '-1 day')));
        }

        if (in_array($qualityTask->getStatus(), [TaskStatus::IN_PROGRESS, TaskStatus::COMPLETED, TaskStatus::FAILED], true)) {
            $qualityTask->setStartedAt(\DateTimeImmutable::createFromMutable($this->faker->dateTimeBetween('-3 days', 'now')));
        }

        if (in_array($qualityTask->getStatus(), [TaskStatus::COMPLETED, TaskStatus::FAILED], true)) {
            $qualityTask->setCompletedAt(\DateTimeImmutable::createFromMutable($this->faker->dateTimeBetween('-1 day', 'now')));
        }

        // 设置备注
        if ($this->faker->boolean(50)) {
            if (TaskStatus::COMPLETED === $qualityTask->getStatus()) {
                $notes = [
                    '质检合格，所有指标符合标准',
                    '发现少量外观瑕疵，在可接受范围内',
                    '产品质量优秀，超出预期',
                    '包装完整，标签清晰',
                    '功能测试通过，性能稳定',
                ];
            } elseif (TaskStatus::FAILED === $qualityTask->getStatus()) {
                $notes = [
                    '发现严重质量问题，不符合标准',
                    '产品功能缺陷，需要退回供应商',
                    '包装破损严重，影响产品质量',
                    '标识不清或错误，需要重新标记',
                    '检测设备异常，影响检测结果',
                ];
            } else {
                $notes = [
                    '质检进行中，初步检查正常',
                    '需要进一步检测确认',
                    '等待专业设备检测',
                    '样本已送实验室检验',
                    '质检流程按计划执行',
                ];
            }
            /** @var string $note */
            $note = $this->faker->randomElement($notes);
            $qualityTask->setNotes($note);
        }

        return $qualityTask;
    }

    /**
     * 生成质检商品信息
     *
     * @return array<int, array<string, mixed>>
     */
    private function generateQualityInspectionItems(): array
    {
        $itemCount = $this->faker->numberBetween(1, 4);
        $items = [];

        for ($i = 0; $i < $itemCount; ++$i) {
            $items[] = [
                'sku' => 'SKU' . $this->faker->unique()->numerify('######'),
                'product_name' => $this->faker->randomElement([
                    '三星Galaxy S23',
                    '联想拯救者笔记本',
                    '苹果AirPods Pro',
                    '戴森吹风机',
                    '飞利浦剃须刀',
                    '松下电饭煲',
                    'Canon打印机',
                    '小米扫地机器人',
                    '华为智能手表',
                    '索尼PlayStation 5',
                ]),
                'batch_number' => date('Ymd') . $this->faker->numerify('###'),
                'quantity_to_inspect' => $this->faker->numberBetween(5, 50),
                'unit' => $this->faker->randomElement(['台', '个', '套', '件']),
                'location' => $this->generateLocationTitle(),
                'supplier' => $this->faker->company(),
                'production_date' => $this->faker->dateTimeBetween('-6 months', '-1 week')->format('Y-m-d'),
            ];
        }

        return $items;
    }

    /**
     * 生成质检标准信息
     *
     * @return array<string, mixed>
     */
    private function generateInspectionStandards(): array
    {
        return [
            'appearance_check' => $this->faker->boolean(90), // 外观检查
            'functional_test' => $this->faker->boolean(80), // 功能测试
            'packaging_inspection' => $this->faker->boolean(95), // 包装检查
            'label_verification' => $this->faker->boolean(85), // 标签验证
            'dimension_check' => $this->faker->boolean(60), // 尺寸检查
            'weight_verification' => $this->faker->boolean(50), // 重量验证
            'safety_standards' => [
                'ce_marking' => $this->faker->boolean(70),
                'rohs_compliance' => $this->faker->boolean(65),
                'fcc_certification' => $this->faker->boolean(40),
            ],
            'performance_criteria' => [
                'min_performance_score' => $this->faker->numberBetween(80, 95),
                'max_defect_rate' => $this->faker->randomFloat(2, 0.1, 2.0),
                'acceptance_threshold' => $this->faker->numberBetween(85, 98),
            ],
        ];
    }
}
