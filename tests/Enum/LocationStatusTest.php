<?php

declare(strict_types=1);

namespace Tourze\WarehouseOperationBundle\Tests\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;
use Tourze\WarehouseOperationBundle\Enum\LocationStatus;

/**
 * @internal
 */
#[CoversClass(LocationStatus::class)]
final class LocationStatusTest extends AbstractEnumTestCase
{
    public function testGetLabel(): void
    {
        $this->assertSame('可用', LocationStatus::AVAILABLE->getLabel());
        $this->assertSame('占用', LocationStatus::OCCUPIED->getLabel());
        $this->assertSame('维护中', LocationStatus::MAINTENANCE->getLabel());
        $this->assertSame('锁定', LocationStatus::LOCKED->getLabel());
    }

    public function testAllCasesAreCovered(): void
    {
        $cases = LocationStatus::cases();

        $this->assertCount(4, $cases);
        $this->assertContainsEquals(LocationStatus::AVAILABLE, $cases);
        $this->assertContainsEquals(LocationStatus::OCCUPIED, $cases);
        $this->assertContainsEquals(LocationStatus::MAINTENANCE, $cases);
        $this->assertContainsEquals(LocationStatus::LOCKED, $cases);
    }

    public function testGenOptions(): void
    {
        $selectOptions = LocationStatus::genOptions();

        $this->assertIsArray($selectOptions);
        $this->assertCount(4, $selectOptions);

        foreach ($selectOptions as $option) {
            $this->assertArrayHasKey('value', $option);
            $this->assertArrayHasKey('label', $option);
            $this->assertArrayHasKey('text', $option);
            $this->assertArrayHasKey('name', $option);
        }

        // 验证第一个选项的具体值
        $this->assertArrayHasKey(0, $selectOptions, 'Select options should have first element at index 0');
        $firstOption = $selectOptions[0];
        $this->assertIsArray($firstOption, 'First option should be an array');
        $this->assertArrayHasKey('value', $firstOption, 'First option should have value key');
        $this->assertArrayHasKey('label', $firstOption, 'First option should have label key');
        $this->assertEquals('available', $firstOption['value']);
        $this->assertEquals('可用', $firstOption['label']);
    }

    public function testToArray(): void
    {
        // toArray() 是实例方法，测试单个枚举值的 toArray() 输出
        $result = LocationStatus::AVAILABLE->toArray();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('value', $result);
        $this->assertArrayHasKey('label', $result);

        $this->assertEquals('available', $result['value']);
        $this->assertEquals('可用', $result['label']);
    }
}
