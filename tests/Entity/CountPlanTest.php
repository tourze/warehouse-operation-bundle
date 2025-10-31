<?php

namespace Tourze\WarehouseOperationBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;
use Tourze\WarehouseOperationBundle\Entity\CountPlan;

/**
 * CountPlan Entity 单元测试
 * @internal
 */
#[CoversClass(CountPlan::class)]
class CountPlanTest extends AbstractEntityTestCase
{
    protected function createEntity(): object
    {
        return new CountPlan();
    }

    /** @return iterable<string, array{string, mixed}> */
    public static function propertiesProvider(): iterable
    {
        return [
            'name' => ['name', 'Test Plan'],
            'countType' => ['countType', 'cycle'],
            'description' => ['description', 'Test description'],
            'status' => ['status', 'draft'],
            'priority' => ['priority', 5],
        ];
    }

    public function testCountPlanCreation(): void
    {
        $plan = new CountPlan();

        $this->assertNull($plan->getId());
        $this->assertSame('', $plan->getName());
        $this->assertSame('cycle', $plan->getCountType());
        $this->assertNull($plan->getDescription());
        $this->assertSame([], $plan->getScope());
        $this->assertSame([], $plan->getSchedule());
        $this->assertNull($plan->getStartDate());
        $this->assertNull($plan->getEndDate());
        $this->assertSame('draft', $plan->getStatus());
        $this->assertSame(50, $plan->getPriority());
        $this->assertTrue($plan->isActive());
    }

    public function testSettersAndGetters(): void
    {
        $plan = new CountPlan();
        $scope = [
            'zones' => ['A', 'B', 'C'],
            'categories' => ['food', 'electronics'],
            'value_range' => ['min' => 100, 'max' => 10000],
        ];
        $schedule = [
            'frequency' => 'monthly',
            'day_of_month' => 1,
            'time' => '09:00',
            'duration_hours' => 8,
        ];
        $startDate = new \DateTimeImmutable('2024-01-01');
        $endDate = new \DateTimeImmutable('2024-12-31');

        $plan->setName('月度循环盘点计划');
        $plan->setCountType('cycle');
        $plan->setDescription('每月定期循环盘点');
        $plan->setScope($scope);
        $plan->setSchedule($schedule);
        $plan->setStartDate($startDate);
        $plan->setEndDate($endDate);
        $plan->setStatus('scheduled');
        $plan->setPriority(80);
        $plan->setIsActive(true);

        $this->assertSame('月度循环盘点计划', $plan->getName());
        $this->assertSame('cycle', $plan->getCountType());
        $this->assertSame('每月定期循环盘点', $plan->getDescription());
        $this->assertSame($scope, $plan->getScope());
        $this->assertSame($schedule, $plan->getSchedule());
        $this->assertSame($startDate, $plan->getStartDate());
        $this->assertSame($endDate, $plan->getEndDate());
        $this->assertSame('scheduled', $plan->getStatus());
        $this->assertSame(80, $plan->getPriority());
        $this->assertTrue($plan->isActive());
    }

    public function testFluentInterface(): void
    {
        $plan = new CountPlan();

        $plan->setName('测试计划');
        $plan->setCountType('abc');
        $plan->setDescription('ABC分类盘点');
        $plan->setScope(['zones' => ['A']]);
        $plan->setSchedule(['frequency' => 'weekly']);
        $plan->setStatus('running');
        $plan->setPriority(90);
        $plan->setIsActive(false);

        // 验证setter方法正确设置了值
        $this->assertSame('测试计划', $plan->getName());
        $this->assertSame('abc', $plan->getCountType());
        $this->assertSame('ABC分类盘点', $plan->getDescription());
        $this->assertSame(['zones' => ['A']], $plan->getScope());
        $this->assertSame(['frequency' => 'weekly'], $plan->getSchedule());
        $this->assertSame('running', $plan->getStatus());
        $this->assertSame(90, $plan->getPriority());
        $this->assertFalse($plan->isActive());
    }

    public function testToString(): void
    {
        $plan = new CountPlan();
        $plan->setName('全盘计划');

        // ID为null时的toString
        $expected = 'CountPlan # (全盘计划)';
        $this->assertSame($expected, $plan->__toString());
    }

    public function testCountTypeValues(): void
    {
        $plan = new CountPlan();

        // 测试所有支持的盘点类型
        $validTypes = ['full', 'cycle', 'abc', 'random', 'spot'];

        foreach ($validTypes as $type) {
            $plan->setCountType($type);
            $this->assertSame($type, $plan->getCountType());
        }
    }

    public function testStatusValues(): void
    {
        $plan = new CountPlan();

        // 测试所有支持的状态值
        $validStatuses = ['draft', 'scheduled', 'running', 'paused', 'completed', 'cancelled'];

        foreach ($validStatuses as $status) {
            $plan->setStatus($status);
            $this->assertSame($status, $plan->getStatus());
        }
    }

    public function testScopeConfiguration(): void
    {
        $plan = new CountPlan();

        // 测试复杂的盘点范围配置
        $scope = [
            'warehouses' => [1, 2, 3],
            'zones' => ['A01', 'B02', 'C03'],
            'categories' => ['food', 'electronics', 'clothing'],
            'abc_class' => ['A', 'B'],
            'value_range' => [
                'min' => 1000,
                'max' => 50000,
                'currency' => 'CNY',
            ],
            'last_count_days' => 90,
            'exclude_locations' => ['DAMAGE', 'QUARANTINE'],
        ];

        $plan->setScope($scope);

        $this->assertSame($scope, $plan->getScope());
        $this->assertSame([1, 2, 3], $plan->getScope()['warehouses']);
        $this->assertSame(90, $plan->getScope()['last_count_days']);
    }

    public function testScheduleConfiguration(): void
    {
        $plan = new CountPlan();

        // 测试调度配置
        $schedule = [
            'type' => 'recurring',
            'frequency' => 'monthly',
            'interval' => 1,
            'day_of_month' => 1,
            'time' => '09:00:00',
            'timezone' => 'Asia/Shanghai',
            'duration_hours' => 8,
            'auto_start' => true,
            'notification_settings' => [
                'before_start' => ['1day', '1hour'],
                'on_completion' => true,
                'recipients' => ['manager@example.com'],
            ],
        ];

        $plan->setSchedule($schedule);

        $this->assertSame($schedule, $plan->getSchedule());
        $this->assertSame('recurring', $plan->getSchedule()['type']);
        $this->assertSame(8, $plan->getSchedule()['duration_hours']);
        $this->assertTrue($plan->getSchedule()['auto_start']);
    }

    public function testDateHandling(): void
    {
        $plan = new CountPlan();

        $startDate = new \DateTimeImmutable('2024-01-01 00:00:00');
        $endDate = new \DateTimeImmutable('2024-12-31 23:59:59');

        $plan->setStartDate($startDate);
        $plan->setEndDate($endDate);

        $this->assertSame($startDate, $plan->getStartDate());
        $this->assertSame($endDate, $plan->getEndDate());

        // 测试设置为null
        $plan->setStartDate(null);
        $plan->setEndDate(null);

        $this->assertNull($plan->getStartDate());
        $this->assertNull($plan->getEndDate());
    }
}
