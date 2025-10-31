<?php

namespace Tourze\WarehouseOperationBundle\Tests\Repository;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;
use Tourze\WarehouseOperationBundle\Entity\WorkerSkill;
use Tourze\WarehouseOperationBundle\Repository\WorkerSkillRepository;

/**
 * WorkerSkillRepository 单元测试
 *
 * @internal
 */
#[CoversClass(WorkerSkillRepository::class)]
#[RunTestsInSeparateProcesses]
class WorkerSkillRepositoryTest extends AbstractRepositoryTestCase
{
    public function testFindByWorkerIdShouldReturnWorkerSkills(): void
    {
        $repository = $this->getRepository();

        // 创建测试数据
        $skill1 = new WorkerSkill();
        $skill1->setWorkerId(101);

        $skill1->setSkillCategory('picking');

        $skill1->setSkillLevel(8);

        $skill1->setIsActive(true);

        $skill2 = new WorkerSkill();
        $skill2->setWorkerId(101);

        $skill2->setSkillCategory('packing');

        $skill2->setSkillLevel(6);

        $skill2->setIsActive(true);

        $skill3 = new WorkerSkill();
        $skill3->setWorkerId(102); // 不同的作业员
        $skill3->setSkillCategory('picking');
        $skill3->setSkillLevel(5);
        $skill3->setIsActive(true);

        $skill4 = new WorkerSkill();
        $skill4->setWorkerId(101);

        $skill4->setSkillCategory('quality');

        $skill4->setSkillLevel(7);

        $skill4->setIsActive(false);

        foreach ([$skill1, $skill2, $skill3, $skill4] as $skill) {
            $repository->save($skill);
        }

        // 测试查找作业员101的技能
        $results = $repository->findByWorkerId(101);

        $this->assertCount(2, $results); // 只返回活跃状态的技能

        // 应该按技能等级DESC排序
        $this->assertSame('picking', $results[0]->getSkillCategory());
        $this->assertSame(8, $results[0]->getSkillLevel());
        $this->assertSame('packing', $results[1]->getSkillCategory());
        $this->assertSame(6, $results[1]->getSkillLevel());
    }

    protected function getRepository(): WorkerSkillRepository
    {
        return self::getService(WorkerSkillRepository::class);
    }

    public function testFindBySkillCategoryShouldReturnMatchingSkills(): void
    {
        $repository = $this->getRepository();

        $skill1 = new WorkerSkill();
        $skill1->setWorkerId(101);

        $skill1->setSkillCategory('picking');

        $skill1->setSkillLevel(8);

        $skill1->setIsActive(true);

        $skill2 = new WorkerSkill();
        $skill2->setWorkerId(102);

        $skill2->setSkillCategory('picking');

        $skill2->setSkillLevel(6);

        $skill2->setIsActive(true);

        $skill3 = new WorkerSkill();
        $skill3->setWorkerId(103);

        $skill3->setSkillCategory('packing');

        $skill3->setSkillLevel(9);

        $skill3->setIsActive(true);

        foreach ([$skill1, $skill2, $skill3] as $skill) {
            $repository->save($skill);
        }

        // 测试查找picking技能
        $results = $repository->findBySkillCategory('picking');

        $this->assertCount(3, $results); // 包括前面测试留下的数据

        // 验证都是picking技能且按技能等级DESC排序
        foreach ($results as $result) {
            $this->assertSame('picking', $result->getSkillCategory());
        }

        // 验证排序：第一个应该是最高等级的
        $this->assertGreaterThanOrEqual($results[1]->getSkillLevel(), $results[0]->getSkillLevel());
    }

    public function testFindBySkillCategoryWithMinLevelShouldFilter(): void
    {
        $repository = $this->getRepository();

        $skills = [
            $this->createSkillWithLevel(101, 'picking', 9),
            $this->createSkillWithLevel(102, 'picking', 7),
            $this->createSkillWithLevel(103, 'picking', 5),
            $this->createSkillWithLevel(104, 'picking', 3),
        ];

        foreach ($skills as $skill) {
            $repository->save($skill);
        }

        // 测试最低等级筛选
        $results = $repository->findBySkillCategory('picking', 7);

        $this->assertGreaterThanOrEqual(2, count($results)); // 至少等级7以上的
        // 验证所有结果都符合最低等级要求
        foreach ($results as $result) {
            $this->assertGreaterThanOrEqual(7, $result->getSkillLevel());
        }
    }

    /**
     * 创建具有指定等级的技能
     */
    private function createSkillWithLevel(int $workerId, string $category, int $level): WorkerSkill
    {
        $skill = new WorkerSkill();
        $skill->setWorkerId($workerId);

        $skill->setSkillCategory($category);

        $skill->setSkillLevel($level);

        $skill->setIsActive(true);

        return $skill;
    }

    public function testFindExpiringCertificationsShouldReturnNearExpiry(): void
    {
        $repository = $this->getRepository();

        $now = new \DateTimeImmutable();
        $soonExpire = $now->modify('+15 days');
        $laterExpire = $now->modify('+60 days');
        $noExpiry = null;

        $skill1 = new WorkerSkill();
        $skill1->setWorkerId(101);

        $skill1->setSkillCategory('hazardous');

        $skill1->setCertifiedAt(new \DateTimeImmutable());

        $skill1->setExpiresAt($soonExpire);

        $skill1->setIsActive(true);

        $skill2 = new WorkerSkill();
        $skill2->setWorkerId(102);

        $skill2->setSkillCategory('quality');

        $skill2->setCertifiedAt(new \DateTimeImmutable());

        $skill2->setExpiresAt($laterExpire);

        $skill2->setIsActive(true);

        $skill3 = new WorkerSkill();
        $skill3->setWorkerId(103);

        $skill3->setSkillCategory('picking');

        $skill3->setCertifiedAt(new \DateTimeImmutable());

        $skill3->setExpiresAt($noExpiry);

        $skill3->setIsActive(true);

        foreach ([$skill1, $skill2, $skill3] as $skill) {
            $repository->save($skill);
        }

        // 测试30天内即将过期的认证
        $results = $repository->findExpiringCertifications(30);

        $this->assertGreaterThanOrEqual(1, count($results)); // 至少有一个即将过期的认证，可能包含前面测试的数据

        // 验证找到了我们创建的hazardous技能记录
        $foundHazardousSkill = false;
        foreach ($results as $result) {
            $expiresAt = $result->getExpiresAt();
            if ('hazardous' === $result->getSkillCategory()
                && null !== $expiresAt
                && $expiresAt->format('Y-m-d') === $soonExpire->format('Y-m-d')) {
                $foundHazardousSkill = true;
                break;
            }
        }
        $this->assertTrue($foundHazardousSkill, '应该找到即将过期的hazardous技能');
    }

    public function testFindBySkillLevelRangeShouldFilterCorrectly(): void
    {
        $repository = $this->getRepository();

        $skills = [
            $this->createSkillWithLevel(101, 'picking', 2),
            $this->createSkillWithLevel(102, 'picking', 5),
            $this->createSkillWithLevel(103, 'picking', 7),
            $this->createSkillWithLevel(104, 'picking', 9),
        ];

        foreach ($skills as $skill) {
            $repository->save($skill);
        }

        // 测试等级范围查询 (5-8)
        $results = $repository->findBySkillLevelRange(5, 8);

        $this->assertGreaterThanOrEqual(2, count($results)); // 至少2个，可能包含前面测试的数据

        // 验证所有结果都在指定等级范围内
        foreach ($results as $result) {
            $this->assertGreaterThanOrEqual(5, $result->getSkillLevel());
            $this->assertLessThanOrEqual(8, $result->getSkillLevel());
        }
    }

    public function testFindValidCertificationsShouldReturnOnlyValid(): void
    {
        $repository = $this->getRepository();

        $now = new \DateTimeImmutable();
        $validExpiry = $now->modify('+30 days');
        $expiredExpiry = $now->modify('-10 days');

        $skill1 = new WorkerSkill();
        $skill1->setWorkerId(101);

        $skill1->setSkillCategory('hazardous');

        $skill1->setCertifiedAt(new \DateTimeImmutable());

        $skill1->setExpiresAt($validExpiry);

        $skill1->setIsActive(true);

        $skill2 = new WorkerSkill();
        $skill2->setWorkerId(102);

        $skill2->setSkillCategory('quality');

        $skill2->setCertifiedAt(new \DateTimeImmutable());

        $skill2->setExpiresAt($expiredExpiry);

        $skill2->setIsActive(true);

        $skill3 = new WorkerSkill();
        $skill3->setWorkerId(103);

        $skill3->setSkillCategory('picking');

        $skill3->setCertifiedAt(null);

        $skill3->setExpiresAt($validExpiry);

        $skill3->setIsActive(true);

        $skill4 = new WorkerSkill();
        $skill4->setWorkerId(104);

        $skill4->setSkillCategory('equipment');

        $skill4->setCertifiedAt(new \DateTimeImmutable());

        $skill4->setExpiresAt(null);

        $skill4->setIsActive(true);

        foreach ([$skill1, $skill2, $skill3, $skill4] as $skill) {
            $repository->save($skill);
        }

        $results = $repository->findValidCertifications();

        $this->assertGreaterThanOrEqual(2, count($results)); // 至少skill1 和 skill4，可能包含前面测试的数据
        $skillCategories = array_map(fn ($s) => $s->getSkillCategory(), $results);
        $this->assertContainsEquals('hazardous', $skillCategories);
        $this->assertContainsEquals('equipment', $skillCategories);
    }

    public function testCountBySkillCategoryShouldReturnUniqueWorkers(): void
    {
        $repository = $this->getRepository();

        $skills = [
            $this->createSkillWithCategory(101, 'picking'),
            $this->createSkillWithCategory(102, 'picking'),
            $this->createSkillWithCategory(101, 'packing'), // 同一作业员的不同技能
            $this->createSkillWithCategory(103, 'quality'),
        ];

        foreach ($skills as $skill) {
            $repository->save($skill);
        }

        $statistics = $repository->countBySkillCategory();

        // 验证统计结果包含我们创建的数据
        $this->assertGreaterThanOrEqual(2, $statistics['picking'] ?? 0); // 2个不同作业员
        $this->assertGreaterThanOrEqual(1, $statistics['packing'] ?? 0); // 1个作业员
        $this->assertGreaterThanOrEqual(1, $statistics['quality'] ?? 0); // 1个作业员
    }

    /**
     * 创建具有指定类别的技能
     */
    private function createSkillWithCategory(int $workerId, string $category): WorkerSkill
    {
        $skill = new WorkerSkill();
        $skill->setWorkerId($workerId);

        $skill->setSkillCategory($category);

        $skill->setSkillLevel(5);

        $skill->setIsActive(true);

        return $skill;
    }

    public function testFindMultiSkillWorkersShouldReturnMatchingWorkers(): void
    {
        $repository = $this->getRepository();

        // 作业员101：picking + packing
        $repository->save($this->createSkillWithCategory(101, 'picking'));
        $repository->save($this->createSkillWithCategory(101, 'packing'));

        // 作业员102：picking + quality
        $repository->save($this->createSkillWithCategory(102, 'picking'));
        $repository->save($this->createSkillWithCategory(102, 'quality'));

        // 作业员103：只有picking
        $repository->save($this->createSkillWithCategory(103, 'picking'));

        // 作业员104：picking + packing + quality
        $repository->save($this->createSkillWithCategory(104, 'picking'));
        $repository->save($this->createSkillWithCategory(104, 'packing'));
        $repository->save($this->createSkillWithCategory(104, 'quality'));

        // 测试查找具备picking和packing技能的作业员
        $results = $repository->findMultiSkillWorkers(['picking', 'packing']);

        $this->assertCount(2, $results); // 101 和 104
        $this->assertContainsEquals(101, $results);
        $this->assertContainsEquals(104, $results);
    }

    public function testGetWorkerMaxSkillLevelShouldReturnHighest(): void
    {
        $repository = $this->getRepository();

        $skills = [
            $this->createSkillWithLevel(101, 'picking', 8),
            $this->createSkillWithLevel(101, 'packing', 6),
            $this->createSkillWithLevel(101, 'quality', 9),
        ];

        foreach ($skills as $skill) {
            $repository->save($skill);
        }

        $maxLevel = $repository->getWorkerMaxSkillLevel(101);

        $this->assertSame(9, $maxLevel);
    }

    public function testFindSkillUpgradeCandidatesShouldReturnEligible(): void
    {
        $repository = $this->getRepository();

        $oldDate = new \DateTimeImmutable('-6 months');
        $recentDate = new \DateTimeImmutable('-1 month');

        $skill1 = new WorkerSkill();
        $skill1->setWorkerId(101);

        $skill1->setSkillCategory('picking');

        $skill1->setSkillLevel(5);

        $skill1->setIsActive(true);

        $skill2 = new WorkerSkill();
        $skill2->setWorkerId(102);

        $skill2->setSkillCategory('picking');

        $skill2->setSkillLevel(8);

        $skill2->setIsActive(true);

        $skill3 = new WorkerSkill();
        $skill3->setWorkerId(103);

        $skill3->setSkillCategory('picking');

        $skill3->setSkillLevel(6);

        $skill3->setIsActive(true);

        foreach ([$skill1, $skill2, $skill3] as $skill) {
            $repository->save($skill);
        }

        $results = $repository->findSkillUpgradeCandidates('picking', 7);

        // 验证结果中包含我们期望的作业员
        $foundCandidate = false;
        foreach ($results as $result) {
            if (101 === $result->getWorkerId()) {
                $foundCandidate = true;
                break;
            }
        }
        $this->assertTrue($foundCandidate, '应该找到作业员101作为升级候选人');
    }

    public function testFindWorkersBySkillsShouldReturnMatchingWorkers(): void
    {
        $repository = $this->getRepository();

        // 创建测试数据
        $skill1 = new WorkerSkill();
        $skill1->setWorkerId(101);

        $skill1->setSkillCategory('picking');

        $skill1->setSkillLevel(8);

        $skill1->setSkillScore(85);

        $skill1->setIsActive(true);

        $skill2 = new WorkerSkill();
        $skill2->setWorkerId(102);

        $skill2->setSkillCategory('packing');

        $skill2->setSkillLevel(6);

        $skill2->setSkillScore(75);

        $skill2->setIsActive(true);

        $skill3 = new WorkerSkill();
        $skill3->setWorkerId(103);

        $skill3->setSkillCategory('picking');

        $skill3->setSkillLevel(7);

        $skill3->setSkillScore(90);

        $skill3->setIsActive(true);

        $skill4 = new WorkerSkill();
        $skill4->setWorkerId(104);

        $skill4->setSkillCategory('quality');

        $skill4->setSkillLevel(9);

        $skill4->setSkillScore(95);

        $skill4->setIsActive(false);

        $skill5 = new WorkerSkill();
        $skill5->setWorkerId(105);

        $skill5->setSkillCategory('packing');

        $skill5->setSkillLevel(5);

        $skill5->setSkillScore(70);

        $skill5->setIsActive(true);

        foreach ([$skill1, $skill2, $skill3, $skill4, $skill5] as $skill) {
            $repository->save($skill);
        }

        // 测试查找具备picking技能的作业员
        $pickingWorkers = $repository->findWorkersBySkills(['picking']);
        $this->assertGreaterThanOrEqual(2, count($pickingWorkers)); // 应该至少返回2个picking技能记录（可能包含前面测试的数据）

        // 验证结果按技能分数DESC排序
        $prevScore = PHP_INT_MAX;
        foreach ($pickingWorkers as $worker) {
            $this->assertSame('picking', $worker->getSkillCategory());
            $this->assertLessThanOrEqual($prevScore, $worker->getSkillScore());
            $prevScore = $worker->getSkillScore();
        }

        // 测试查找多个技能
        $multiSkillWorkers = $repository->findWorkersBySkills(['picking', 'packing']);
        $this->assertGreaterThanOrEqual(4, count($multiSkillWorkers)); // 至少picking(2) + packing(2) = 4，可能包含前面测试的数据

        // 测试排除指定作业员
        $excludeWorkers = $repository->findWorkersBySkills(['picking'], [101]);
        $this->assertGreaterThanOrEqual(1, count($excludeWorkers));
        // 验证结果中不包含被排除的作业员
        foreach ($excludeWorkers as $worker) {
            $this->assertNotSame(101, $worker->getWorkerId());
        }

        // 测试排除多个作业员
        $excludeMultiple = $repository->findWorkersBySkills(['packing'], [102, 105]);
        // 验证结果中不包含被排除的作业员
        foreach ($excludeMultiple as $worker) {
            $this->assertNotContainsEquals($worker->getWorkerId(), [102, 105]);
        }

        // 测试空技能列表
        $emptySkills = $repository->findWorkersBySkills([]);
        $this->assertCount(0, $emptySkills);

        // 测试不存在的技能
        $nonExistentSkill = $repository->findWorkersBySkills(['nonexistent']);
        $this->assertCount(0, $nonExistentSkill);

        // 验证只返回活跃状态的技能
        $allResults = $repository->findWorkersBySkills(['quality']);
        // 验证所有质量技能记录都是活跃状态（如果有的话）
        foreach ($allResults as $result) {
            $this->assertTrue($result->isActive());
        }
    }

    public function testSaveAndRemoveMethodsShouldWork(): void
    {
        $repository = $this->getRepository();

        $skill = new WorkerSkill();
        $skill->setWorkerId(999);

        $skill->setSkillCategory('test');

        $skill->setSkillLevel(5);

        $skill->setIsActive(true);

        // 测试保存
        $repository->save($skill);
        $this->assertNotNull($skill->getId());

        $savedId = $skill->getId();

        // 测试删除
        $repository->remove($skill);

        $deletedSkill = $repository->find($savedId);
        $this->assertNull($deletedSkill);
    }

    protected function onSetUp(): void
    {
        // Repository tests don't require additional setup
    }

    protected function createNewEntity(): object
    {
        $skill = new WorkerSkill();
        $skill->setWorkerId(101);

        $skill->setSkillCategory('picking');

        $skill->setSkillLevel(5);

        $skill->setCertifiedAt(new \DateTimeImmutable());

        $skill->setIsActive(true);

        $skill->setExpiresAt(new \DateTimeImmutable('+1 year'));

        return $skill;
    }
}
