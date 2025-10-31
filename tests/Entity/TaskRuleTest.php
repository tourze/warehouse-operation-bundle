<?php

namespace Tourze\WarehouseOperationBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;
use Tourze\WarehouseOperationBundle\Entity\TaskRule;

/**
 * TaskRule Entity 单元测试
 * @internal
 */
#[CoversClass(TaskRule::class)]
class TaskRuleTest extends AbstractEntityTestCase
{
    protected function createEntity(): object
    {
        return new TaskRule();
    }

    /** @return iterable<string, array{string, mixed}> */
    public static function propertiesProvider(): iterable
    {
        return [
            'name' => ['name', 'Test Rule'],
            'ruleType' => ['ruleType', 'priority'],
            'description' => ['description', 'Test description'],
            'conditions' => ['conditions', ['type' => 'location', 'value' => 'A1']],
            'actions' => ['actions', ['assign' => 'worker_001']],
            'priority' => ['priority', 5],
        ];
    }

    public function testTaskRuleCreation(): void
    {
        $rule = new TaskRule();

        $this->assertNull($rule->getId());
        $this->assertSame('', $rule->getName());
        $this->assertSame('priority', $rule->getRuleType());
        $this->assertNull($rule->getDescription());
        $this->assertSame([], $rule->getConditions());
        $this->assertSame([], $rule->getActions());
        $this->assertSame(50, $rule->getPriority());
        $this->assertTrue($rule->isActive());
        $this->assertNull($rule->getEffectiveFrom());
        $this->assertNull($rule->getEffectiveTo());
        $this->assertNull($rule->getNotes());
    }

    public function testSettersAndGetters(): void
    {
        $rule = new TaskRule();
        $conditions = [
            'task_type' => ['inbound', 'outbound'],
            'priority_min' => 80,
            'time_window' => [
                'start' => '08:00',
                'end' => '18:00',
            ],
        ];
        $actions = [
            'assign_to_skill' => 'advanced',
            'set_priority' => 90,
            'notify_supervisor' => true,
        ];
        $effectiveFrom = new \DateTimeImmutable('2024-01-01');
        $effectiveTo = new \DateTimeImmutable('2024-12-31');

        $rule->setName('高优先级任务规则');
        $rule->setRuleType('priority');
        $rule->setDescription('处理高优先级任务的调度规则');
        $rule->setConditions($conditions);
        $rule->setActions($actions);
        $rule->setPriority(85);
        $rule->setIsActive(true);
        $rule->setEffectiveFrom($effectiveFrom);
        $rule->setEffectiveTo($effectiveTo);
        $rule->setNotes('测试规则');

        $this->assertSame('高优先级任务规则', $rule->getName());
        $this->assertSame('priority', $rule->getRuleType());
        $this->assertSame('处理高优先级任务的调度规则', $rule->getDescription());
        $this->assertSame($conditions, $rule->getConditions());
        $this->assertSame($actions, $rule->getActions());
        $this->assertSame(85, $rule->getPriority());
        $this->assertTrue($rule->isActive());
        $this->assertSame($effectiveFrom, $rule->getEffectiveFrom());
        $this->assertSame($effectiveTo, $rule->getEffectiveTo());
        $this->assertSame('测试规则', $rule->getNotes());
    }

    public function testFluentInterface(): void
    {
        $rule = new TaskRule();

        $rule->setName('测试规则');
        $rule->setRuleType('skill_match');
        $rule->setDescription('技能匹配规则');
        $rule->setConditions(['skill_required' => 'expert']);
        $rule->setActions(['match_worker' => true]);
        $rule->setPriority(75);
        $rule->setIsActive(false);
        $rule->setNotes('流式接口测试');

        // 验证setter方法正确设置了值
        $this->assertSame('测试规则', $rule->getName());
        $this->assertSame('skill_match', $rule->getRuleType());
        $this->assertSame('技能匹配规则', $rule->getDescription());
        $this->assertSame(['skill_required' => 'expert'], $rule->getConditions());
        $this->assertSame(['match_worker' => true], $rule->getActions());
        $this->assertSame(75, $rule->getPriority());
        $this->assertFalse($rule->isActive());
        $this->assertSame('流式接口测试', $rule->getNotes());
    }

    public function testToString(): void
    {
        $rule = new TaskRule();
        $rule->setName('工作负载均衡规则');

        // ID为null时的toString
        $expected = 'TaskRule # (工作负载均衡规则)';
        $this->assertSame($expected, $rule->__toString());
    }

    public function testRuleTypes(): void
    {
        $rule = new TaskRule();

        // 测试所有支持的规则类型
        $validTypes = ['priority', 'skill_match', 'workload_balance', 'constraint', 'optimization'];

        foreach ($validTypes as $type) {
            $rule->setRuleType($type);
            $this->assertSame($type, $rule->getRuleType());
        }
    }

    public function testConditionsStructure(): void
    {
        $rule = new TaskRule();

        // 测试复杂的规则条件
        $conditions = [
            'task_properties' => [
                'type' => ['quality', 'count'],
                'priority' => ['min' => 70, 'max' => 100],
                'estimated_duration' => ['max' => 120], // minutes
            ],
            'worker_requirements' => [
                'skill_level' => 'advanced',
                'certifications' => ['quality_inspector'],
                'max_concurrent_tasks' => 3,
            ],
            'time_constraints' => [
                'working_hours' => [
                    'start' => '09:00',
                    'end' => '17:00',
                ],
                'exclude_days' => ['sunday'],
                'urgent_override' => true,
            ],
            'location_constraints' => [
                'allowed_zones' => ['A', 'B', 'C'],
                'exclude_locations' => ['MAINTENANCE', 'RESTRICTED'],
            ],
        ];

        $rule->setConditions($conditions);

        $this->assertSame($conditions, $rule->getConditions());
        $this->assertSame(['quality', 'count'], $rule->getConditions()['task_properties']['type']);
        $this->assertSame(3, $rule->getConditions()['worker_requirements']['max_concurrent_tasks']);
        $this->assertTrue($rule->getConditions()['time_constraints']['urgent_override']);
    }

    public function testActionsStructure(): void
    {
        $rule = new TaskRule();

        // 测试复杂的规则动作
        $actions = [
            'assignment' => [
                'strategy' => 'skill_based',
                'fallback_strategy' => 'round_robin',
                'auto_assign' => true,
            ],
            'priority_adjustment' => [
                'increase_by' => 10,
                'cap_at' => 95,
                'reason' => 'rule_based_boost',
            ],
            'notifications' => [
                'notify_supervisor' => true,
                'notify_worker' => false,
                'email_template' => 'high_priority_task',
            ],
            'constraints' => [
                'max_retry_attempts' => 3,
                'timeout_minutes' => 60,
                'escalation_threshold' => 30,
            ],
            'logging' => [
                'log_assignment' => true,
                'log_level' => 'info',
                'include_context' => true,
            ],
        ];

        $rule->setActions($actions);

        $this->assertSame($actions, $rule->getActions());
        $this->assertSame('skill_based', $rule->getActions()['assignment']['strategy']);
        $this->assertSame(10, $rule->getActions()['priority_adjustment']['increase_by']);
        $this->assertTrue($rule->getActions()['notifications']['notify_supervisor']);
        $this->assertSame(3, $rule->getActions()['constraints']['max_retry_attempts']);
    }

    public function testDateHandling(): void
    {
        $rule = new TaskRule();

        $startDate = new \DateTimeImmutable('2024-06-01');
        $endDate = new \DateTimeImmutable('2024-08-31');

        $rule->setEffectiveFrom($startDate);
        $rule->setEffectiveTo($endDate);

        $this->assertSame($startDate, $rule->getEffectiveFrom());
        $this->assertSame($endDate, $rule->getEffectiveTo());

        // 测试设置为null
        $rule->setEffectiveFrom(null);
        $rule->setEffectiveTo(null);

        $this->assertNull($rule->getEffectiveFrom());
        $this->assertNull($rule->getEffectiveTo());
    }

    public function testPriorityRange(): void
    {
        $rule = new TaskRule();

        // 测试边界值
        $rule->setPriority(1);
        $this->assertSame(1, $rule->getPriority());

        $rule->setPriority(100);
        $this->assertSame(100, $rule->getPriority());

        // 测试默认值
        $rule->setPriority(50);
        $this->assertSame(50, $rule->getPriority());
    }

    public function testSkillMatchingRule(): void
    {
        $rule = new TaskRule();

        // 技能匹配规则示例
        $rule->setName('危险品任务技能匹配');
        $rule->setRuleType('skill_match');
        $rule->setConditions([
            'task_category' => 'hazardous',
            'required_certifications' => ['hazmat_certification'],
            'minimum_skill_score' => 80,
        ]);
        $rule->setActions([
            'match_worker_skills' => true,
            'verify_certifications' => true,
            'require_supervisor_approval' => true,
        ]);

        $this->assertSame('skill_match', $rule->getRuleType());
        $this->assertSame(['hazmat_certification'], $rule->getConditions()['required_certifications']);
        $this->assertTrue($rule->getActions()['require_supervisor_approval']);
    }

    public function testWorkloadBalanceRule(): void
    {
        $rule = new TaskRule();

        // 工作负载均衡规则示例
        $rule->setName('工作负载均衡规则');
        $rule->setRuleType('workload_balance');
        $rule->setConditions([
            'max_tasks_per_worker' => 5,
            'workload_threshold' => 0.8, // 80%
            'balance_window_hours' => 4,
        ]);
        $rule->setActions([
            'redistribute_tasks' => true,
            'prevent_overload' => true,
            'notify_if_imbalanced' => true,
        ]);

        $this->assertSame('workload_balance', $rule->getRuleType());
        $this->assertSame(5, $rule->getConditions()['max_tasks_per_worker']);
        $this->assertSame(0.8, $rule->getConditions()['workload_threshold']);
    }
}
