<?php

namespace Tourze\WarehouseOperationBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Tourze\WarehouseOperationBundle\Entity\QualityStandard;

/**
 * 质检标准测试数据固定装置
 */
class QualityStandardFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // 食品类质检标准
        $foodStandard = new QualityStandard();
        $foodStandard->setName('食品类质检标准');
        $foodStandard->setProductCategory('food');
        $foodStandard->setDescription('食品类商品的质检标准，包含外观、保质期、包装完整性等检查');
        $foodStandard->setCheckItems([
            'appearance' => [
                'required' => true,
                'criteria' => '包装完好无破损、标签清晰',
                'weight' => 30,
            ],
            'expiry' => [
                'required' => true,
                'min_days' => 30,
                'check_format' => 'YYYY-MM-DD',
            ],
            'temperature' => [
                'required' => true,
                'range' => ['min' => -18, 'max' => 25],
                'unit' => 'celsius',
            ],
        ]);
        $foodStandard->setPriority(90);
        $foodStandard->setIsActive(true);

        $manager->persist($foodStandard);

        // 电子产品质检标准
        $electronicsStandard = new QualityStandard();
        $electronicsStandard->setName('电子产品质检标准');
        $electronicsStandard->setProductCategory('electronics');
        $electronicsStandard->setDescription('电子产品质检标准，包含外观、功能性、包装等检查');
        $electronicsStandard->setCheckItems([
            'appearance' => [
                'required' => true,
                'criteria' => '外观无划痕、屏幕无裂纹、按键正常',
                'weight' => 40,
            ],
            'functionality' => [
                'required' => true,
                'tests' => ['power_on', 'basic_functions', 'connectivity'],
            ],
            'accessories' => [
                'required' => true,
                'items' => ['charger', 'manual', 'warranty_card'],
            ],
            'packaging' => [
                'required' => true,
                'criteria' => '原装包装、防伪标识',
            ],
        ]);
        $electronicsStandard->setPriority(85);
        $electronicsStandard->setIsActive(true);

        $manager->persist($electronicsStandard);

        // 服装类质检标准
        $clothingStandard = new QualityStandard();
        $clothingStandard->setName('服装类质检标准');
        $clothingStandard->setProductCategory('clothing');
        $clothingStandard->setDescription('服装类商品质检标准');
        $clothingStandard->setCheckItems([
            'appearance' => [
                'required' => true,
                'criteria' => '无污渍、无破损、无异味',
            ],
            'size' => [
                'required' => true,
                'tolerance' => 2, // 2cm tolerance
            ],
            'material' => [
                'required' => false,
                'check_label' => true,
            ],
        ]);
        $clothingStandard->setPriority(70);
        $clothingStandard->setIsActive(true);

        $manager->persist($clothingStandard);

        // 危险品质检标准
        $hazardousStandard = new QualityStandard();
        $hazardousStandard->setName('危险品质检标准');
        $hazardousStandard->setProductCategory('hazardous');
        $hazardousStandard->setDescription('危险品特殊质检标准，严格按安全规范执行');
        $hazardousStandard->setCheckItems([
            'safety_packaging' => [
                'required' => true,
                'criteria' => '符合UN包装标准',
                'weight' => 100,
            ],
            'labeling' => [
                'required' => true,
                'items' => ['hazard_symbols', 'safety_instructions', 'emergency_contacts'],
            ],
            'documentation' => [
                'required' => true,
                'documents' => ['msds', 'safety_certificate', 'transport_permit'],
            ],
        ]);
        $hazardousStandard->setPriority(100);
        $hazardousStandard->setIsActive(true);

        $manager->persist($hazardousStandard);

        $manager->flush();
    }
}
