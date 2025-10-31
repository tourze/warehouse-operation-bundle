<?php

namespace Tourze\WarehouseOperationBundle\Tests\Repository;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;
use Tourze\WarehouseOperationBundle\Entity\QualityStandard;
use Tourze\WarehouseOperationBundle\Repository\QualityStandardRepository;

/**
 * QualityStandardRepository 单元测试
 *
 * @internal
 */
#[CoversClass(QualityStandardRepository::class)]
#[RunTestsInSeparateProcesses]
class QualityStandardRepositoryTest extends AbstractRepositoryTestCase
{
    public function testFindByProductCategoryShouldReturnCorrectResults(): void
    {
        $repository = $this->getRepository();

        // 创建测试数据
        $standard1 = new QualityStandard();
        $standard1->setName('电子产品标准1');

        $standard1->setProductCategory('electronics_test1');

        $standard1->setIsActive(true);

        $standard1->setPriority(10);

        $standard2 = new QualityStandard();
        $standard2->setName('食品标准1');

        $standard2->setProductCategory('food_test1');

        $standard2->setIsActive(true);

        $standard2->setPriority(20);

        $standard3 = new QualityStandard();
        $standard3->setName('电子产品标准2');

        $standard3->setProductCategory('electronics_test1');

        $standard3->setIsActive(false);

        $standard3->setPriority(30);

        $repository->save($standard1);
        $repository->save($standard2);
        $repository->save($standard3);

        // 测试按类别查找
        $results = $repository->findByProductCategory('electronics_test1');

        $this->assertCount(1, $results); // 只返回活跃状态的
        $this->assertSame('电子产品标准1', $results[0]->getName());
    }

    protected function getRepository(): QualityStandardRepository
    {
        return self::getService(QualityStandardRepository::class);
    }

    public function testFindActiveStandardsShouldReturnOnlyActiveOnes(): void
    {
        $repository = $this->getRepository();

        $activeStandard = new QualityStandard();
        $activeStandard->setName('活跃标准');

        $activeStandard->setProductCategory('test');

        $activeStandard->setIsActive(true);

        $activeStandard->setPriority(10);

        $inactiveStandard = new QualityStandard();
        $inactiveStandard->setName('非活跃标准');

        $inactiveStandard->setProductCategory('test');

        $inactiveStandard->setIsActive(false);

        $inactiveStandard->setPriority(20);

        $repository->save($activeStandard);
        $repository->save($inactiveStandard);

        $results = $repository->findActiveStandards();

        // 验证结果中包含我们创建的活跃标准
        $activeFound = false;
        $inactiveFound = false;
        foreach ($results as $result) {
            if ('活跃标准' === $result->getName()) {
                $activeFound = true;
                $this->assertTrue($result->isActive());
            }
            if ('非活跃标准' === $result->getName()) {
                $inactiveFound = true;
            }
        }

        $this->assertTrue($activeFound, '应该找到活跃标准');
        $this->assertFalse($inactiveFound, '不应该找到非活跃标准');
    }

    public function testFindByPriorityRangeShouldFilterCorrectly(): void
    {
        $repository = $this->getRepository();

        $standards = [
            $this->createStandardWithPriority('低优先级', 5),
            $this->createStandardWithPriority('中优先级', 50),
            $this->createStandardWithPriority('高优先级', 95),
        ];

        foreach ($standards as $standard) {
            $repository->save($standard);
        }

        // 测试优先级范围查询
        $results = $repository->findByPriorityRange(20, 80);

        // 验证结果中包含我们期望的标准
        $foundMiddle = false;
        foreach ($results as $result) {
            if ('中优先级' === $result->getName() && 50 === $result->getPriority()) {
                $foundMiddle = true;
                break;
            }
        }

        $this->assertTrue($foundMiddle, '应该找到中优先级标准');
    }

    /**
     * 创建具有指定优先级的标准
     */
    private function createStandardWithPriority(string $name, int $priority): QualityStandard
    {
        $standard = new QualityStandard();
        $standard->setName($name);

        $standard->setProductCategory('test');

        $standard->setIsActive(true);

        $standard->setPriority($priority);

        return $standard;
    }

    public function testFindByCheckItemShouldUseJsonQuery(): void
    {
        $repository = $this->getRepository();

        $standard1 = new QualityStandard();
        $standard1->setName('有外观检查');

        $standard1->setProductCategory('test');

        $standard1->setCheckItems(['appearance' => ['required' => true]]);

        $standard1->setIsActive(true);

        $standard2 = new QualityStandard();
        $standard2->setName('无外观检查');

        $standard2->setProductCategory('test');

        $standard2->setCheckItems(['weight' => ['required' => true]]);

        $standard2->setIsActive(true);

        $repository->save($standard1);
        $repository->save($standard2);

        $results = $repository->findByCheckItem('appearance');

        // 验证结果中包含我们期望的标准
        $foundAppearance = false;
        foreach ($results as $result) {
            if ('有外观检查' === $result->getName()) {
                $foundAppearance = true;
                break;
            }
        }

        $this->assertTrue($foundAppearance, '应该找到有外观检查的标准');
    }

    public function testSearchStandardsShouldSearchMultipleFields(): void
    {
        $repository = $this->getRepository();

        $standard1 = new QualityStandard();
        $standard1->setName('电子产品质检');

        $standard1->setProductCategory('electronics_test2');

        $standard1->setDescription('电子产品专用质检标准');

        $standard1->setIsActive(true);

        $standard2 = new QualityStandard();
        $standard2->setName('食品质检');

        $standard2->setProductCategory('food');

        $standard2->setDescription('食品安全检查标准');

        $standard2->setIsActive(true);

        $repository->save($standard1);
        $repository->save($standard2);

        // 测试按名称搜索
        $results1 = $repository->searchStandards('电子产品质检');
        $foundByName = false;
        foreach ($results1 as $result) {
            if ('电子产品质检' === $result->getName()) {
                $foundByName = true;
                break;
            }
        }
        $this->assertTrue($foundByName, '应该通过名称搜索找到电子产品质检');

        // 测试按描述搜索
        $results2 = $repository->searchStandards('食品安全检查标准');
        $foundByDesc = false;
        foreach ($results2 as $result) {
            if ('食品质检' === $result->getName()) {
                $foundByDesc = true;
                break;
            }
        }
        $this->assertTrue($foundByDesc, '应该通过描述搜索找到食品质检');

        // 测试按类别搜索
        $results3 = $repository->searchStandards('food');
        $foundByCategory = false;
        foreach ($results3 as $result) {
            if ('食品质检' === $result->getName()) {
                $foundByCategory = true;
                break;
            }
        }
        $this->assertTrue($foundByCategory, '应该通过类别搜索找到食品质检');
    }

    public function testCountByCategoryShouldReturnCorrectStatistics(): void
    {
        $repository = $this->getRepository();

        $standards = [
            $this->createStandardWithCategory('electronics_test3', '电子1'),
            $this->createStandardWithCategory('electronics_test3', '电子2'),
            $this->createStandardWithCategory('food_test3', '食品1'),
            $this->createStandardWithCategory('clothing_test3', '服装1'),
        ];

        foreach ($standards as $standard) {
            $repository->save($standard);
        }

        $statistics = $repository->countByCategory();

        $this->assertSame(2, $statistics['electronics_test3']);
        $this->assertSame(1, $statistics['food_test3']);
        $this->assertSame(1, $statistics['clothing_test3']);
    }

    /**
     * 创建具有指定类别的标准
     */
    private function createStandardWithCategory(string $category, string $name): QualityStandard
    {
        $standard = new QualityStandard();
        $standard->setName($name);

        $standard->setProductCategory($category);

        $standard->setIsActive(true);

        $standard->setPriority(10);

        return $standard;
    }

    public function testSaveAndRemoveMethodsShouldWork(): void
    {
        $repository = $this->getRepository();

        $standard = new QualityStandard();
        $standard->setName('测试保存');

        $standard->setProductCategory('test');

        $standard->setIsActive(true);

        // 测试保存
        $repository->save($standard);
        $this->assertNotNull($standard->getId());

        $savedId = $standard->getId();

        // 测试删除
        $repository->remove($standard);

        $deletedStandard = $repository->find($savedId);
        $this->assertNull($deletedStandard);
    }

    protected function onSetUp(): void
    {
        // Repository tests don't require additional setup
    }

    protected function createNewEntity(): object
    {
        $standard = new QualityStandard();
        $standard->setName('测试质检标准');

        $standard->setProductCategory('test_category');

        $standard->setDescription('测试类别质检标准');

        $standard->setIsActive(true);

        $standard->setPriority(50);

        return $standard;
    }
}
