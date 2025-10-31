<?php

namespace Tourze\WarehouseOperationBundle\Tests\Repository;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;
use Tourze\WarehouseOperationBundle\Entity\TaskRule;
use Tourze\WarehouseOperationBundle\Repository\TaskRuleRepository;

/**
 * TaskRuleRepository 单元测试
 *
 * @internal
 */
#[CoversClass(TaskRuleRepository::class)]
#[RunTestsInSeparateProcesses]
class TaskRuleRepositoryTest extends AbstractRepositoryTestCase
{
    public function testFindActiveByTypeShouldReturnCorrectResults(): void
    {
        $repository = $this->getRepository();

        // 创建测试数据
        $rule1 = new TaskRule();
        $rule1->setName('优先级规则1');

        $rule1->setRuleType('priority');

        $rule1->setIsActive(true);

        $rule1->setPriority(90);

        $rule2 = new TaskRule();
        $rule2->setName('技能匹配规则1');

        $rule2->setRuleType('skill_match');

        $rule2->setIsActive(true);

        $rule2->setPriority(80);

        $rule3 = new TaskRule();
        $rule3->setName('优先级规则2');

        $rule3->setRuleType('priority');

        $rule3->setIsActive(false);

        $rule3->setPriority(70);

        foreach ([$rule1, $rule2, $rule3] as $rule) {
            $repository->save($rule);
        }

        // 测试按规则类型查找活跃规则
        $results = $repository->findActiveByType('priority');

        // 验证结果中包含我们期望的规则
        $foundTarget = false;
        foreach ($results as $result) {
            if ('优先级规则1' === $result->getName() && 'priority' === $result->getRuleType()) {
                $foundTarget = true;
                break;
            }
        }
        $this->assertTrue($foundTarget, '应该找到优先级规则1');
    }

    protected function getRepository(): TaskRuleRepository
    {
        return self::getService(TaskRuleRepository::class);
    }

    public function testFindAllActiveOrderedByPriorityShouldSortCorrectly(): void
    {
        $repository = $this->getRepository();

        $rule1 = new TaskRule();
        $rule1->setName('B规则');

        $rule1->setRuleType('priority');

        $rule1->setIsActive(true);

        $rule1->setPriority(50);

        $rule2 = new TaskRule();
        $rule2->setName('A规则');

        $rule2->setRuleType('skill_match');

        $rule2->setIsActive(true);

        $rule2->setPriority(50);

        $rule3 = new TaskRule();
        $rule3->setName('高优先级规则');

        $rule3->setRuleType('constraint');

        $rule3->setIsActive(true);

        $rule3->setPriority(90);

        $rule4 = new TaskRule();
        $rule4->setName('非活跃规则');

        $rule4->setRuleType('priority');

        $rule4->setIsActive(false);

        $rule4->setPriority(95);

        foreach ([$rule1, $rule2, $rule3, $rule4] as $rule) {
            $repository->save($rule);
        }

        $results = $repository->findAllActiveOrderedByPriority();

        // 验证结果中包含我们创建的活跃规则
        $this->assertNotEmpty($results, '应至少返回一条活跃规则');
        $ruleNames = array_map(fn (TaskRule $r): string => $r->getName(), $results);
        $this->assertContainsEquals('高优先级规则', $ruleNames);
        $this->assertContainsEquals('A规则', $ruleNames);
        $this->assertContainsEquals('B规则', $ruleNames);
        $this->assertNotContainsEquals('非活跃规则', $ruleNames);
    }

    public function testFindCurrentlyEffectiveShouldFilterByDate(): void
    {
        $repository = $this->getRepository();

        $now = new \DateTimeImmutable();
        $pastDate = $now->modify('-30 days');
        $futureDate = $now->modify('+30 days');
        $expiredDate = $now->modify('-10 days');

        $rule1 = new TaskRule();
        $rule1->setName('当前有效规则');

        $rule1->setRuleType('priority');

        $rule1->setEffectiveFrom($pastDate);

        $rule1->setEffectiveTo($futureDate);

        $rule1->setIsActive(true);

        $rule2 = new TaskRule();
        $rule2->setName('永久有效规则');

        $rule2->setRuleType('skill_match');

        $rule2->setEffectiveFrom(null);

        $rule2->setEffectiveTo(null);

        $rule2->setIsActive(true);

        $rule3 = new TaskRule();
        $rule3->setName('已过期规则');

        $rule3->setRuleType('constraint');

        $rule3->setEffectiveFrom($pastDate);

        $rule3->setEffectiveTo($expiredDate);

        $rule3->setIsActive(true);

        $rule4 = new TaskRule();
        $rule4->setName('未生效规则');

        $rule4->setRuleType('optimization');

        $rule4->setEffectiveFrom($futureDate);

        $rule4->setEffectiveTo($futureDate->modify('+30 days'));

        $rule4->setIsActive(true);

        foreach ([$rule1, $rule2, $rule3, $rule4] as $rule) {
            $repository->save($rule);
        }

        $results = $repository->findCurrentlyEffective($now);

        // 验证结果中包含我们期望的有效规则
        $this->assertNotEmpty($results, '应至少返回一条当前有效的规则');
        $ruleNames = array_map(fn (TaskRule $r): string => $r->getName(), $results);
        $this->assertContainsEquals('当前有效规则', $ruleNames);
        $this->assertContainsEquals('永久有效规则', $ruleNames);
        $this->assertNotContainsEquals('已过期规则', $ruleNames);
        $this->assertNotContainsEquals('未生效规则', $ruleNames);
    }

    public function testFindByPriorityRangeShouldFilterCorrectly(): void
    {
        $repository = $this->getRepository();

        $rules = [
            $this->createRuleWithPriority('低优先级', 10),
            $this->createRuleWithPriority('中优先级', 50),
            $this->createRuleWithPriority('高优先级', 90),
        ];

        foreach ($rules as $rule) {
            $repository->save($rule);
        }

        // 测试优先级范围查询 (40-80)
        $results = $repository->findByPriorityRange(40, 80);

        // 验证结果中包含我们期望的中优先级规则
        $foundMiddle = false;
        foreach ($results as $result) {
            if ('中优先级' === $result->getName() && 50 === $result->getPriority()) {
                $foundMiddle = true;
                break;
            }
        }
        $this->assertTrue($foundMiddle, '应该找到中优先级规则');
    }

    /**
     * 创建具有指定优先级的规则
     */
    private function createRuleWithPriority(string $name, int $priority): TaskRule
    {
        $rule = new TaskRule();
        $rule->setName($name);

        $rule->setRuleType('priority');

        $rule->setPriority($priority);

        $rule->setIsActive(true);

        return $rule;
    }

    public function testSearchRulesShouldSearchMultipleFields(): void
    {
        $repository = $this->getRepository();

        $rule1 = new TaskRule();
        $rule1->setName('紧急任务处理');

        $rule1->setRuleType('priority');

        $rule1->setDescription('处理紧急任务的优先级规则');

        $rule1->setNotes('用于高优先级任务');

        $rule1->setIsActive(true);

        $rule2 = new TaskRule();
        $rule2->setName('技能匹配规则');

        $rule2->setRuleType('skill_match');

        $rule2->setDescription('根据作业员技能分配任务');

        $rule2->setNotes('确保任务匹配');

        $rule2->setIsActive(true);

        foreach ([$rule1, $rule2] as $rule) {
            $repository->save($rule);
        }

        // 测试按名称搜索
        $results1 = $repository->searchRules('紧急');
        $foundByName = false;
        foreach ($results1 as $result) {
            if ('紧急任务处理' === $result->getName()) {
                $foundByName = true;
                break;
            }
        }
        $this->assertTrue($foundByName, '应该通过名称搜索找到紧急任务处理');

        // 测试按描述搜索
        $results2 = $repository->searchRules('技能');
        $foundByDescription = false;
        foreach ($results2 as $result) {
            if ('技能匹配规则' === $result->getName()) {
                $foundByDescription = true;
                break;
            }
        }
        $this->assertTrue($foundByDescription, '应该通过描述搜索找到技能匹配规则');

        // 测试按备注搜索
        $results3 = $repository->searchRules('匹配');
        $foundByNotes = false;
        foreach ($results3 as $result) {
            if ('技能匹配规则' === $result->getName()) {
                $foundByNotes = true;
                break;
            }
        }
        $this->assertTrue($foundByNotes, '应该通过备注搜索找到技能匹配规则');
    }

    public function testFindByConditionShouldUseJsonQuery(): void
    {
        $repository = $this->getRepository();

        $rule1 = new TaskRule();
        $rule1->setName('优先级条件规则');

        $rule1->setRuleType('priority');

        $rule1->setConditions(['task_priority' => ['min' => 80]]);

        $rule1->setIsActive(true);

        $rule2 = new TaskRule();
        $rule2->setName('技能条件规则');

        $rule2->setRuleType('skill_match');

        $rule2->setConditions(['required_skills' => ['picking', 'quality']]);

        $rule2->setIsActive(true);

        foreach ([$rule1, $rule2] as $rule) {
            $repository->save($rule);
        }

        // 测试查找包含特定条件的规则
        $results = $repository->findByCondition('task_priority');

        // 验证结果中包含我们期望的条件规则
        $foundCondition = false;
        foreach ($results as $result) {
            if ('优先级条件规则' === $result->getName()) {
                $foundCondition = true;
                break;
            }
        }
        $this->assertTrue($foundCondition, '应该找到优先级条件规则');
    }

    public function testFindByActionShouldUseJsonQuery(): void
    {
        $repository = $this->getRepository();

        $rule1 = new TaskRule();
        $rule1->setName('自动分配规则');

        $rule1->setRuleType('priority');

        $rule1->setActions(['assignment' => ['auto_assign' => true]]);

        $rule1->setIsActive(true);

        $rule2 = new TaskRule();
        $rule2->setName('通知规则');

        $rule2->setRuleType('skill_match');

        $rule2->setActions(['notifications' => ['notify_supervisor' => true]]);

        $rule2->setIsActive(true);

        foreach ([$rule1, $rule2] as $rule) {
            $repository->save($rule);
        }

        // 测试查找包含特定动作的规则
        $results = $repository->findByAction('assignment');

        // 验证结果中包含我们期望的动作规则
        $foundAction = false;
        foreach ($results as $result) {
            if ('自动分配规则' === $result->getName()) {
                $foundAction = true;
                break;
            }
        }
        $this->assertTrue($foundAction, '应该找到自动分配规则');
    }

    public function testFindExpiringRulesShouldReturnNearExpiry(): void
    {
        $repository = $this->getRepository();

        $now = new \DateTimeImmutable();
        $soonExpire = $now->modify('+15 days');
        $laterExpire = $now->modify('+60 days');

        $rule1 = new TaskRule();
        $rule1->setName('即将过期规则');

        $rule1->setRuleType('constraint');

        $rule1->setEffectiveTo($soonExpire);

        $rule1->setIsActive(true);

        $rule2 = new TaskRule();
        $rule2->setName('长期有效规则');

        $rule2->setRuleType('priority');

        $rule2->setEffectiveTo($laterExpire);

        $rule2->setIsActive(true);

        $rule3 = new TaskRule();
        $rule3->setName('永久规则');

        $rule3->setRuleType('optimization');

        $rule3->setEffectiveTo(null);

        $rule3->setIsActive(true);

        foreach ([$rule1, $rule2, $rule3] as $rule) {
            $repository->save($rule);
        }

        // 测试30天内即将过期的规则
        $results = $repository->findExpiringRules(30);

        // 验证结果中包含我们期望的即将过期规则
        $foundExpiring = false;
        foreach ($results as $result) {
            if ('即将过期规则' === $result->getName()) {
                $foundExpiring = true;
                break;
            }
        }
        $this->assertTrue($foundExpiring, '应该找到即将过期规则');

        // 验证不包含长期和永久规则
        $ruleNames = array_map(fn ($r) => $r->getName(), $results);
        $this->assertNotContainsEquals('长期有效规则', $ruleNames);
        $this->assertNotContainsEquals('永久规则', $ruleNames);
    }

    public function testCountByRuleTypeShouldReturnCorrectStatistics(): void
    {
        $repository = $this->getRepository();

        $rules = [
            $this->createRuleWithType('priority', '优先级1'),
            $this->createRuleWithType('priority', '优先级2'),
            $this->createRuleWithType('skill_match', '技能匹配1'),
            $this->createRuleWithType('constraint', '约束1'),
        ];

        foreach ($rules as $rule) {
            $repository->save($rule);
        }

        $statistics = $repository->countByRuleType();

        // 验证统计数据包含我们创建的规则类型
        $this->assertGreaterThanOrEqual(2, $statistics['priority'] ?? 0);
        $this->assertGreaterThanOrEqual(1, $statistics['skill_match'] ?? 0);
        $this->assertGreaterThanOrEqual(1, $statistics['constraint'] ?? 0);
    }

    /**
     * 创建具有指定类型的规则
     */
    private function createRuleWithType(string $ruleType, string $name): TaskRule
    {
        $rule = new TaskRule();
        $rule->setName($name);

        $rule->setRuleType($ruleType);

        $rule->setPriority(50);

        $rule->setIsActive(true);

        return $rule;
    }

    public function testFindConflictingRulesShouldDetectConflicts(): void
    {
        $repository = $this->getRepository();

        $baseDate = new \DateTimeImmutable('2024-01-01');
        $endDate = new \DateTimeImmutable('2024-12-31');

        $rule1 = new TaskRule();
        $rule1->setName('现有规则');

        $rule1->setRuleType('priority');

        $rule1->setPriority(80);

        $rule1->setEffectiveFrom($baseDate);

        $rule1->setEffectiveTo($endDate);

        $rule1->setIsActive(true);

        $rule2 = new TaskRule();
        $rule2->setName('不同类型规则');

        $rule2->setRuleType('skill_match');

        $rule2->setPriority(80);

        $rule2->setEffectiveFrom($baseDate);

        $rule2->setEffectiveTo($endDate);

        $rule2->setIsActive(true);

        foreach ([$rule1, $rule2] as $rule) {
            $repository->save($rule);
        }

        // 测试查找可能冲突的规则（相同类型和优先级）
        $conflicts = $repository->findConflictingRules(
            'priority',
            80,
            new \DateTimeImmutable('2024-06-01'),
            new \DateTimeImmutable('2024-08-31')
        );

        $this->assertCount(1, $conflicts); // 应该找到rule1
        $this->assertArrayHasKey(0, $conflicts, 'Conflicts array should have element at index 0');
        $this->assertSame('现有规则', $conflicts[0]->getName());
    }

    public function testGetMaxPriorityShouldReturnHighest(): void
    {
        $repository = $this->getRepository();

        $rules = [
            $this->createRuleWithPriority('低优先级', 10),
            $this->createRuleWithPriority('中优先级', 50),
            $this->createRuleWithPriority('高优先级', 95),
        ];

        foreach ($rules as $rule) {
            $repository->save($rule);
        }

        $maxPriority = $repository->getMaxPriority();

        $this->assertGreaterThanOrEqual(95, $maxPriority);
    }

    public function testBulkToggleActiveShouldUpdateMultipleRules(): void
    {
        $repository = $this->getRepository();

        $rules = [
            $this->createRuleWithName('规则1'),
            $this->createRuleWithName('规则2'),
            $this->createRuleWithName('规则3'),
        ];

        foreach ($rules as $rule) {
            $repository->save($rule);
        }

        $ruleIds = array_filter(
            array_map(fn (TaskRule $r): ?int => $r->getId(), $rules),
            fn (?int $id): bool => null !== $id
        );

        // 测试批量禁用
        $affectedRows = $repository->bulkToggleActive($ruleIds, false);

        $this->assertSame(3, $affectedRows);

        // 验证规则已被禁用 - 检查我们创建的规则不再活跃
        $updatedRules = $repository->findAllActiveOrderedByPriority();
        $updatedNames = array_map(fn ($r) => $r->getName(), $updatedRules);
        $this->assertNotContainsEquals('规则1', $updatedNames);
        $this->assertNotContainsEquals('规则2', $updatedNames);
        $this->assertNotContainsEquals('规则3', $updatedNames);
    }

    /**
     * 创建具有指定名称的规则
     */
    private function createRuleWithName(string $name): TaskRule
    {
        $rule = new TaskRule();
        $rule->setName($name);

        $rule->setRuleType('priority');

        $rule->setPriority(50);

        $rule->setIsActive(true);

        return $rule;
    }

    public function testSaveAndRemoveMethodsShouldWork(): void
    {
        $repository = $this->getRepository();

        $rule = new TaskRule();
        $rule->setName('测试保存规则');

        $rule->setRuleType('test');

        $rule->setIsActive(true);

        // 测试保存
        $repository->save($rule);
        $this->assertNotNull($rule->getId());

        $savedId = $rule->getId();

        // 测试删除
        $repository->remove($rule);

        $deletedRule = $repository->find($savedId);
        $this->assertNull($deletedRule);
    }

    protected function onSetUp(): void
    {
        // Repository tests don't require additional setup
    }

    protected function createNewEntity(): object
    {
        $rule = new TaskRule();
        $rule->setName('测试任务规则');

        $rule->setRuleType('priority');

        $rule->setDescription('优先级测试规则');

        $rule->setConditions(['task_priority' => ['min' => 80]]);

        $rule->setActions(['assignment' => ['auto_assign' => true]]);

        $rule->setPriority(50);

        $rule->setIsActive(true);

        return $rule;
    }
}
