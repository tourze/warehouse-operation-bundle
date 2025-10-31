<?php

namespace Tourze\WarehouseOperationBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Tourze\WarehouseOperationBundle\Entity\CountPlan;

/**
 * 盘点计划测试数据固定装置
 */
class CountPlanFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // 全盘计划
        $fullCountPlan = new CountPlan();
        $fullCountPlan->setName('年度全盘计划');
        $fullCountPlan->setCountType('full');
        $fullCountPlan->setDescription('年度全仓库盘点计划');
        $fullCountPlan->setScope([
            'warehouses' => [1, 2, 3],
            'zones' => [],
            'categories' => [],
            'exclude_locations' => ['DAMAGE', 'QUARANTINE'],
        ]);
        $fullCountPlan->setSchedule([
            'type' => 'one_time',
            'date' => '2024-12-31',
            'time' => '18:00:00',
            'duration_hours' => 72,
            'auto_start' => false,
        ]);
        $fullCountPlan->setStartDate(new \DateTimeImmutable('2024-12-31'));
        $fullCountPlan->setStatus('draft');
        $fullCountPlan->setPriority(100);
        $fullCountPlan->setIsActive(true);

        $manager->persist($fullCountPlan);

        // 循环盘点计划
        $cyclePlan = new CountPlan();
        $cyclePlan->setName('A区月度循环盘点');
        $cyclePlan->setCountType('cycle');
        $cyclePlan->setDescription('A区域按月循环盘点');
        $cyclePlan->setScope([
            'zones' => ['A01', 'A02', 'A03'],
            'last_count_days' => 30,
            'exclude_locations' => ['DAMAGE'],
        ]);
        $cyclePlan->setSchedule([
            'type' => 'recurring',
            'frequency' => 'monthly',
            'day_of_month' => 1,
            'time' => '09:00:00',
            'duration_hours' => 8,
            'auto_start' => true,
        ]);
        $cyclePlan->setStartDate(new \DateTimeImmutable('2024-01-01'));
        $cyclePlan->setEndDate(new \DateTimeImmutable('2024-12-31'));
        $cyclePlan->setStatus('scheduled');
        $cyclePlan->setPriority(80);
        $cyclePlan->setIsActive(true);

        $manager->persist($cyclePlan);

        // ABC分类盘点
        $abcPlan = new CountPlan();
        $abcPlan->setName('A类商品重点盘点');
        $abcPlan->setCountType('abc');
        $abcPlan->setDescription('高价值A类商品重点盘点');
        $abcPlan->setScope([
            'abc_class' => ['A'],
            'value_range' => [
                'min' => 10000,
                'currency' => 'CNY',
            ],
            'categories' => ['electronics', 'jewelry'],
        ]);
        $abcPlan->setSchedule([
            'type' => 'recurring',
            'frequency' => 'weekly',
            'day_of_week' => 'saturday',
            'time' => '08:00:00',
            'duration_hours' => 4,
            'auto_start' => true,
        ]);
        $abcPlan->setStartDate(new \DateTimeImmutable('2024-01-01'));
        $abcPlan->setStatus('running');
        $abcPlan->setPriority(95);
        $abcPlan->setIsActive(true);

        $manager->persist($abcPlan);

        // 随机抽盘计划
        $randomPlan = new CountPlan();
        $randomPlan->setName('日常随机抽盘');
        $randomPlan->setCountType('random');
        $randomPlan->setDescription('日常随机抽取商品盘点，确保库存准确性');
        $randomPlan->setScope([
            'sample_rate' => 0.05, // 5% 抽样率
            'min_items' => 50,
            'max_items' => 200,
            'exclude_categories' => ['consumables'],
        ]);
        $randomPlan->setSchedule([
            'type' => 'recurring',
            'frequency' => 'daily',
            'time' => '14:00:00',
            'duration_hours' => 2,
            'auto_start' => true,
            'skip_weekends' => false,
        ]);
        $randomPlan->setStartDate(new \DateTimeImmutable('2024-01-01'));
        $randomPlan->setStatus('running');
        $randomPlan->setPriority(60);
        $randomPlan->setIsActive(true);

        $manager->persist($randomPlan);

        // 重点商品抽盘
        $spotPlan = new CountPlan();
        $spotPlan->setName('重点商品抽盘检查');
        $spotPlan->setCountType('spot');
        $spotPlan->setDescription('对重点关注商品进行专项盘点');
        $spotPlan->setScope([
            'product_codes' => ['SKU001', 'SKU002', 'SKU003'],
            'categories' => ['hazardous', 'controlled'],
            'suppliers' => ['SUPP001', 'SUPP002'],
        ]);
        $spotPlan->setSchedule([
            'type' => 'on_demand',
            'trigger_conditions' => [
                'stock_variance_threshold' => 0.02,
                'days_since_last_count' => 7,
            ],
        ]);
        $spotPlan->setStartDate(new \DateTimeImmutable('2024-01-01'));
        $spotPlan->setStatus('scheduled');
        $spotPlan->setPriority(90);
        $spotPlan->setIsActive(true);

        $manager->persist($spotPlan);

        // 已完成的历史盘点计划
        $completedPlan = new CountPlan();
        $completedPlan->setName('Q1季度盘点');
        $completedPlan->setCountType('full');
        $completedPlan->setDescription('第一季度季度盘点（已完成）');
        $completedPlan->setScope([
            'warehouses' => [1],
            'zones' => [],
        ]);
        $completedPlan->setSchedule([
            'type' => 'one_time',
            'date' => '2024-03-31',
            'time' => '18:00:00',
            'duration_hours' => 48,
        ]);
        $completedPlan->setStartDate(new \DateTimeImmutable('2024-03-31'));
        $completedPlan->setEndDate(new \DateTimeImmutable('2024-04-02'));
        $completedPlan->setStatus('completed');
        $completedPlan->setPriority(85);
        $completedPlan->setIsActive(false);

        $manager->persist($completedPlan);

        $manager->flush();
    }
}
