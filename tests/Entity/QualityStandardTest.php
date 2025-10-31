<?php

namespace Tourze\WarehouseOperationBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;
use Tourze\WarehouseOperationBundle\Entity\QualityStandard;

/**
 * QualityStandard Entity 单元测试
 * @internal
 */
#[CoversClass(QualityStandard::class)]
class QualityStandardTest extends AbstractEntityTestCase
{
    protected function createEntity(): object
    {
        return new QualityStandard();
    }

    /** @return iterable<string, array{string, mixed}> */
    public static function propertiesProvider(): iterable
    {
        return [
            'name' => ['name', 'Test Standard'],
            'productCategory' => ['productCategory', 'electronics'],
            'description' => ['description', 'Test description'],
            'priority' => ['priority', 8],
        ];
    }

    public function testQualityStandardCreation(): void
    {
        $standard = new QualityStandard();

        $this->assertNull($standard->getId());
        $this->assertSame('', $standard->getName());
        $this->assertSame('', $standard->getProductCategory());
        $this->assertNull($standard->getDescription());
        $this->assertSame([], $standard->getCheckItems());
        $this->assertTrue($standard->isActive());
        $this->assertSame(1, $standard->getPriority());
    }

    public function testSettersAndGetters(): void
    {
        $standard = new QualityStandard();
        $checkItems = [
            'appearance' => ['required' => true, 'criteria' => '外观完好无损'],
            'quantity' => ['required' => true, 'tolerance' => 0.01],
            'weight' => ['required' => false, 'min' => 0, 'max' => 1000],
        ];

        $standard->setName('食品质检标准');
        $standard->setProductCategory('food');
        $standard->setDescription('食品类商品质检标准');
        $standard->setCheckItems($checkItems);
        $standard->setIsActive(false);
        $standard->setPriority(50);

        $this->assertSame('食品质检标准', $standard->getName());
        $this->assertSame('food', $standard->getProductCategory());
        $this->assertSame('食品类商品质检标准', $standard->getDescription());
        $this->assertSame($checkItems, $standard->getCheckItems());
        $this->assertFalse($standard->isActive());
        $this->assertSame(50, $standard->getPriority());
    }

    public function testFluentInterface(): void
    {
        $standard = new QualityStandard();

        $standard->setName('测试标准');
        $standard->setProductCategory('test');
        $standard->setDescription('测试描述');
        $standard->setCheckItems(['test' => 'value']);
        $standard->setIsActive(true);
        $standard->setPriority(10);

        // 验证setter方法正确设置了值
        $this->assertSame('测试标准', $standard->getName());
        $this->assertSame('test', $standard->getProductCategory());
        $this->assertSame('测试描述', $standard->getDescription());
        $this->assertSame(['test' => 'value'], $standard->getCheckItems());
        $this->assertTrue($standard->isActive());
        $this->assertSame(10, $standard->getPriority());
    }

    public function testToString(): void
    {
        $standard = new QualityStandard();
        $standard->setName('测试标准');

        // ID为null时的toString
        $expected = 'QualityStandard # (测试标准)';
        $this->assertSame($expected, $standard->__toString());
    }

    public function testCheckItemsStructure(): void
    {
        $standard = new QualityStandard();

        // 测试复杂的质检项目配置
        $checkItems = [
            'appearance' => [
                'required' => true,
                'criteria' => '外观完好无损',
                'weight' => 30,
            ],
            'quantity' => [
                'required' => true,
                'tolerance' => 0.01,
                'unit' => 'pcs',
            ],
            'dimensions' => [
                'required' => false,
                'length' => ['min' => 10, 'max' => 100],
                'width' => ['min' => 5, 'max' => 50],
                'height' => ['min' => 1, 'max' => 20],
            ],
            'expiry' => [
                'required' => true,
                'min_days' => 30,
                'check_format' => 'YYYY-MM-DD',
            ],
        ];

        $standard->setCheckItems($checkItems);

        $this->assertSame($checkItems, $standard->getCheckItems());
        $this->assertArrayHasKey('appearance', $standard->getCheckItems());
        $this->assertTrue($standard->getCheckItems()['appearance']['required']);
        $this->assertSame(0.01, $standard->getCheckItems()['quantity']['tolerance']);
    }

    public function testDefaultValues(): void
    {
        $standard = new QualityStandard();

        // 验证默认值
        $this->assertTrue($standard->isActive());
        $this->assertSame(1, $standard->getPriority());
        $this->assertSame([], $standard->getCheckItems());
        $this->assertSame('', $standard->getName());
        $this->assertSame('', $standard->getProductCategory());
    }
}
