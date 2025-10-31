<?php

namespace Tourze\WarehouseOperationBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Tourze\WarehouseOperationBundle\Entity\WorkerSkill;

/**
 * 作业员技能档案测试数据固定装置
 */
class WorkerSkillFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // 高级拣货员
        $pickingExpert = new WorkerSkill();
        $pickingExpert->setWorkerId(1001);
        $pickingExpert->setWorkerName('张小明');
        $pickingExpert->setSkillCategory('picking');
        $pickingExpert->setSkillLevel(10);
        $pickingExpert->setSkillScore(95);
        $pickingExpert->setCertifications([
            'picking_certification' => [
                'level' => '高级拣货员',
                'issued_by' => '物流协会',
                'certificate_number' => 'PK2024001',
                'specializations' => ['高价值商品', '易碎品', '大件商品'],
            ],
            'safety_training' => [
                'completion_date' => '2024-01-15',
                'score' => 98,
                'valid_until' => '2025-01-15',
            ],
        ]);
        $pickingExpert->setCertifiedAt(new \DateTimeImmutable('2024-01-15'));
        $pickingExpert->setExpiresAt(new \DateTimeImmutable('2025-01-15'));
        $pickingExpert->setIsActive(true);
        $pickingExpert->setNotes('经验丰富，拣货准确率达99.5%');

        $manager->persist($pickingExpert);

        // 叉车操作员
        $equipmentOperator = new WorkerSkill();
        $equipmentOperator->setWorkerId(1002);
        $equipmentOperator->setWorkerName('李师傅');
        $equipmentOperator->setSkillCategory('equipment');
        $equipmentOperator->setSkillLevel(8);
        $equipmentOperator->setSkillScore(88);
        $equipmentOperator->setCertifications([
            'forklift_license' => [
                'number' => 'FL2024002',
                'issued_by' => '特种设备检验院',
                'level' => 'A级',
                'equipment_types' => ['平衡重叉车', '前移式叉车', '堆垛车'],
                'valid_until' => '2026-03-20',
            ],
            'equipment_maintenance' => [
                'level' => '二级维修工',
                'specialization' => '叉车设备维护',
            ],
        ]);
        $equipmentOperator->setCertifiedAt(new \DateTimeImmutable('2022-03-20'));
        $equipmentOperator->setExpiresAt(new \DateTimeImmutable('2026-03-20'));
        $equipmentOperator->setIsActive(true);
        $equipmentOperator->setNotes('持证叉车司机，无安全事故记录');

        $manager->persist($equipmentOperator);

        // 质检员
        $qualityInspector = new WorkerSkill();
        $qualityInspector->setWorkerId(1003);
        $qualityInspector->setWorkerName('王质检');
        $qualityInspector->setSkillCategory('quality');
        $qualityInspector->setSkillLevel(8);
        $qualityInspector->setSkillScore(92);
        $qualityInspector->setCertifications([
            'quality_inspector' => [
                'level' => '二级质检员',
                'certificate_number' => 'QI2024003',
                'specializations' => ['食品检验', '电子产品检验', '包装材料检验'],
                'issued_by' => '质量技术监督局',
            ],
            'food_safety' => [
                'haccp_certification' => true,
                'completion_date' => '2023-06-10',
                'training_hours' => 40,
            ],
        ]);
        $qualityInspector->setCertifiedAt(new \DateTimeImmutable('2023-06-10'));
        $qualityInspector->setExpiresAt(new \DateTimeImmutable('2025-06-10'));
        $qualityInspector->setIsActive(true);
        $qualityInspector->setNotes('熟悉多种商品质检标准');

        $manager->persist($qualityInspector);

        // 危险品作业员
        $hazmatWorker = new WorkerSkill();
        $hazmatWorker->setWorkerId(1004);
        $hazmatWorker->setWorkerName('赵安全');
        $hazmatWorker->setSkillCategory('hazardous');
        $hazmatWorker->setSkillLevel(10);
        $hazmatWorker->setSkillScore(90);
        $hazmatWorker->setCertifications([
            'hazmat_certification' => [
                'number' => 'HAZ2024004',
                'categories' => ['Class 3 易燃液体', 'Class 8 腐蚀性物质'],
                'issued_by' => '危险货物运输管理办公室',
                'training_hours' => 80,
                'valid_until' => '2025-08-30',
            ],
            'emergency_response' => [
                'certification' => '化学品应急处理资格证',
                'level' => '中级',
            ],
        ]);
        $hazmatWorker->setCertifiedAt(new \DateTimeImmutable('2023-08-30'));
        $hazmatWorker->setExpiresAt(new \DateTimeImmutable('2025-08-30'));
        $hazmatWorker->setIsActive(true);
        $hazmatWorker->setNotes('危险品处理专家，应急处理经验丰富');

        $manager->persist($hazmatWorker);

        // 冷链作业员
        $coldStorageWorker = new WorkerSkill();
        $coldStorageWorker->setWorkerId(1005);
        $coldStorageWorker->setWorkerName('孙冷链');
        $coldStorageWorker->setSkillCategory('cold_storage');
        $coldStorageWorker->setSkillLevel(5);
        $coldStorageWorker->setSkillScore(78);
        $coldStorageWorker->setCertifications([
            'cold_chain_certification' => [
                'level' => '冷链作业员',
                'temperature_ranges' => ['常温', '冷藏(0-8°C)', '冷冻(-18°C)'],
                'equipment_operation' => ['冷库门', '温度监控设备', '保温设备'],
            ],
            'food_handling' => [
                'hygiene_certification' => true,
                'valid_until' => '2024-12-31',
            ],
        ]);
        $coldStorageWorker->setCertifiedAt(new \DateTimeImmutable('2023-05-01'));
        $coldStorageWorker->setExpiresAt(new \DateTimeImmutable('2024-12-31'));
        $coldStorageWorker->setIsActive(true);
        $coldStorageWorker->setNotes('熟悉冷链作业流程和温度控制');

        $manager->persist($coldStorageWorker);

        // 初级包装工
        $packingBeginner = new WorkerSkill();
        $packingBeginner->setWorkerId(1006);
        $packingBeginner->setWorkerName('陈新手');
        $packingBeginner->setSkillCategory('packing');
        $packingBeginner->setSkillLevel(2);
        $packingBeginner->setSkillScore(45);
        $packingBeginner->setCertifications([
            'basic_training' => [
                'completion_date' => '2024-01-10',
                'training_hours' => 16,
                'topics' => ['基础包装技能', '安全操作规程'],
            ],
        ]);
        $packingBeginner->setCertifiedAt(new \DateTimeImmutable('2024-01-10'));
        $packingBeginner->setIsActive(true);
        $packingBeginner->setNotes('新入职员工，正在培训中');

        $manager->persist($packingBeginner);

        // 盘点专员
        $countingSpecialist = new WorkerSkill();
        $countingSpecialist->setWorkerId(1007);
        $countingSpecialist->setWorkerName('周盘点');
        $countingSpecialist->setSkillCategory('counting');
        $countingSpecialist->setSkillLevel(8);
        $countingSpecialist->setSkillScore(87);
        $countingSpecialist->setCertifications([
            'inventory_management' => [
                'level' => '库存管理员',
                'specializations' => ['周期盘点', 'ABC分类管理', '差异分析'],
                'certificate_number' => 'IM2024007',
            ],
            'wms_systems' => [
                'certified_systems' => ['SAP WM', 'Manhattan WMS'],
                'proficiency_level' => '高级用户',
            ],
        ]);
        $countingSpecialist->setCertifiedAt(new \DateTimeImmutable('2023-04-15'));
        $countingSpecialist->setExpiresAt(new \DateTimeImmutable('2025-04-15'));
        $countingSpecialist->setIsActive(true);
        $countingSpecialist->setNotes('盘点经验丰富，熟悉各种WMS系统');

        $manager->persist($countingSpecialist);

        $manager->flush();
    }
}
