<?php

namespace Tourze\WarehouseOperationBundle\Tests\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;
use Tourze\WarehouseOperationBundle\Enum\TaskType;

/**
 * @internal
 */
#[CoversClass(TaskType::class)]
class TaskTypeTest extends AbstractEnumTestCase
{
    public function testAllTaskTypesExist(): void
    {
        $expectedTypes = [
            TaskType::INBOUND,
            TaskType::OUTBOUND,
            TaskType::QUALITY,
            TaskType::COUNT,
            TaskType::TRANSFER,
        ];

        $this->assertCount(5, $expectedTypes);

        foreach ($expectedTypes as $type) {
            $this->assertInstanceOf(TaskType::class, $type);
        }
    }

    public function testTaskTypeValues(): void
    {
        $this->assertEquals('inbound', TaskType::INBOUND->value);
        $this->assertEquals('outbound', TaskType::OUTBOUND->value);
        $this->assertEquals('quality', TaskType::QUALITY->value);
        $this->assertEquals('count', TaskType::COUNT->value);
        $this->assertEquals('transfer', TaskType::TRANSFER->value);
    }

    public function testGetLabel(): void
    {
        $this->assertEquals('入库任务', TaskType::INBOUND->getLabel());
        $this->assertEquals('出库任务', TaskType::OUTBOUND->getLabel());
        $this->assertEquals('质检任务', TaskType::QUALITY->getLabel());
        $this->assertEquals('盘点任务', TaskType::COUNT->getLabel());
        $this->assertEquals('调拨任务', TaskType::TRANSFER->getLabel());
    }

    public function testToArray(): void
    {
        // toArray() 是实例方法，测试单个枚举值的 toArray() 输出
        $result = TaskType::INBOUND->toArray();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('value', $result);
        $this->assertArrayHasKey('label', $result);

        $this->assertEquals('inbound', $result['value']);
        $this->assertEquals('入库任务', $result['label']);
    }

    public function testGenOptions(): void
    {
        $selectOptions = TaskType::genOptions();

        $this->assertIsArray($selectOptions);
        $this->assertCount(5, $selectOptions);

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
        $this->assertEquals('inbound', $firstOption['value']);
        $this->assertEquals('入库任务', $firstOption['label']);
    }
}
