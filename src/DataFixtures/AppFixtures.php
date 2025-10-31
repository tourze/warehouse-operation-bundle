<?php

namespace Tourze\WarehouseOperationBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Faker\Generator;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
#[When(env: 'dev')]
abstract class AppFixtures extends Fixture
{
    protected Generator $faker;

    public function __construct()
    {
        $this->faker = Factory::create('zh_CN');
    }

    abstract public function load(ObjectManager $manager): void;

    protected function generateWarehouseCode(): string
    {
        /** @var string $prefix */
        $prefix = $this->faker->randomElement(['WH', 'DC', 'FC', 'SC']);
        $number = $this->faker->numberBetween(100, 999);
        /** @var string $suffix */
        $suffix = $this->faker->unique()->numerify('####');

        // 确保所有操作数都是string类型
        return $prefix . (string)$number . $suffix;
    }

    protected function generateZoneType(): string
    {
        /** @var string */
        return $this->faker->randomElement([
            '收货区', '存储区', '拣货区', '发货区',
            '退货区', '质检区', '包装区', '中转区',
        ]);
    }

    protected function generateLocationTitle(): string
    {
        $row = $this->faker->randomLetter();
        $section = $this->faker->numberBetween(1, 20);
        $level = $this->faker->numberBetween(1, 5);

        return strtoupper($row) . '-' . str_pad((string) $section, 2, '0', STR_PAD_LEFT) . '-' . $level;
    }
}
