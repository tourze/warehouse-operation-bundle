<?php

namespace Tourze\WarehouseOperationBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Tourze\WarehouseOperationBundle\Entity\TaskRule;

/**
 * 任务调度规则测试数据固定装置
 */
class TaskRuleFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // 高优先级任务规则
        $highPriorityRule = new TaskRule();
        $highPriorityRule->setName('紧急任务优先调度');
        $highPriorityRule->setRuleType('priority');
        $highPriorityRule->setDescription('当任务优先级>=90时，立即分配给最佳作业员');
        $highPriorityRule->setConditions([
            'task_priority' => ['min' => 90],
            'task_status' => ['pending', 'assigned'],
            'working_hours' => true,
        ]);
        $highPriorityRule->setActions([
            'assignment' => [
                'strategy' => 'best_available',
                'skip_queue' => true,
                'auto_assign' => true,
            ],
            'notifications' => [
                'notify_supervisor' => true,
                'notify_worker' => true,
            ],
            'logging' => [
                'log_priority_boost' => true,
            ],
        ]);
        $highPriorityRule->setPriority(95);
        $highPriorityRule->setIsActive(true);
        $highPriorityRule->setNotes('处理紧急和高优先级任务');

        $manager->persist($highPriorityRule);

        // 技能匹配规则
        $skillMatchRule = new TaskRule();
        $skillMatchRule->setName('危险品作业技能验证');
        $skillMatchRule->setRuleType('skill_match');
        $skillMatchRule->setDescription('危险品相关任务必须分配给有资质的作业员');
        $skillMatchRule->setConditions([
            'task_categories' => ['hazardous'],
            'required_certifications' => ['hazmat_certification'],
            'minimum_skill_score' => 80,
        ]);
        $skillMatchRule->setActions([
            'assignment' => [
                'verify_certifications' => true,
                'check_skill_level' => true,
                'require_valid_certification' => true,
            ],
            'safety' => [
                'mandatory_safety_briefing' => true,
                'equipment_check' => true,
                'supervisor_approval' => true,
            ],
        ]);
        $skillMatchRule->setPriority(100);
        $skillMatchRule->setIsActive(true);
        $skillMatchRule->setEffectiveFrom(new \DateTimeImmutable('2024-01-01'));
        $skillMatchRule->setNotes('安全第一，确保危险品作业合规');

        $manager->persist($skillMatchRule);

        // 工作负载均衡规则
        $workloadBalanceRule = new TaskRule();
        $workloadBalanceRule->setName('作业员负载均衡');
        $workloadBalanceRule->setRuleType('workload_balance');
        $workloadBalanceRule->setDescription('防止作业员过载，均衡分配任务');
        $workloadBalanceRule->setConditions([
            'max_concurrent_tasks' => 3,
            'workload_threshold' => 0.8, // 80%
            'balance_window_hours' => 4,
            'exclude_urgent_tasks' => false,
        ]);
        $workloadBalanceRule->setActions([
            'load_balancing' => [
                'redistribute_tasks' => true,
                'prevent_overload' => true,
                'consider_task_complexity' => true,
            ],
            'notifications' => [
                'notify_if_overloaded' => true,
                'suggest_task_redistribution' => true,
            ],
            'metrics' => [
                'track_workload_distribution' => true,
                'calculate_efficiency_scores' => true,
            ],
        ]);
        $workloadBalanceRule->setPriority(70);
        $workloadBalanceRule->setIsActive(true);
        $workloadBalanceRule->setNotes('保持作业效率和公平分配');

        $manager->persist($workloadBalanceRule);

        // 时间约束规则
        $timeConstraintRule = new TaskRule();
        $timeConstraintRule->setName('非工作时间限制');
        $timeConstraintRule->setRuleType('constraint');
        $timeConstraintRule->setDescription('非工作时间只处理紧急任务');
        $timeConstraintRule->setConditions([
            'time_window' => [
                'outside_hours' => ['18:00', '08:00'],
                'weekends' => true,
                'holidays' => true,
            ],
            'task_urgency' => ['normal', 'low'],
        ]);
        $timeConstraintRule->setActions([
            'scheduling' => [
                'defer_to_next_workday' => true,
                'queue_for_morning' => true,
                'maintain_priority_order' => true,
            ],
            'exceptions' => [
                'allow_urgent_tasks' => true,
                'emergency_override' => true,
            ],
        ]);
        $timeConstraintRule->setPriority(80);
        $timeConstraintRule->setIsActive(true);
        $timeConstraintRule->setEffectiveFrom(new \DateTimeImmutable('2024-01-01'));
        $timeConstraintRule->setEffectiveTo(new \DateTimeImmutable('2024-12-31'));
        $timeConstraintRule->setNotes('合理安排工作时间，紧急情况除外');

        $manager->persist($timeConstraintRule);

        // 路径优化规则
        $pathOptimizationRule = new TaskRule();
        $pathOptimizationRule->setName('拣货路径优化');
        $pathOptimizationRule->setRuleType('optimization');
        $pathOptimizationRule->setDescription('同区域任务优先分配给同一作业员');
        $pathOptimizationRule->setConditions([
            'task_type' => ['picking', 'quality'],
            'same_zone' => true,
            'estimated_travel_time' => ['max' => 300], // 5 minutes
            'batch_size' => ['min' => 2, 'max' => 8],
        ]);
        $pathOptimizationRule->setActions([
            'optimization' => [
                'group_by_location' => true,
                'minimize_travel_distance' => true,
                'batch_similar_tasks' => true,
            ],
            'assignment' => [
                'prefer_zone_specialist' => true,
                'consider_current_location' => true,
            ],
            'efficiency' => [
                'calculate_travel_savings' => true,
                'track_completion_time' => true,
            ],
        ]);
        $pathOptimizationRule->setPriority(60);
        $pathOptimizationRule->setIsActive(true);
        $pathOptimizationRule->setNotes('提升拣货效率，减少移动时间');

        $manager->persist($pathOptimizationRule);

        // 质检任务特殊规则
        $qualityTaskRule = new TaskRule();
        $qualityTaskRule->setName('质检任务专员分配');
        $qualityTaskRule->setRuleType('skill_match');
        $qualityTaskRule->setDescription('质检任务只能分配给有资质的质检员');
        $qualityTaskRule->setConditions([
            'task_type' => ['quality'],
            'required_skills' => ['quality_inspection'],
            'required_certifications' => ['quality_inspector'],
        ]);
        $qualityTaskRule->setActions([
            'assignment' => [
                'match_certification' => true,
                'verify_skill_level' => true,
                'prefer_specialized_workers' => true,
            ],
            'quality_assurance' => [
                'double_check_requirements' => true,
                'log_quality_decisions' => true,
            ],
        ]);
        $qualityTaskRule->setPriority(85);
        $qualityTaskRule->setIsActive(true);
        $qualityTaskRule->setNotes('确保质检工作由专业人员执行');

        $manager->persist($qualityTaskRule);

        // 夜班作业规则
        $nightShiftRule = new TaskRule();
        $nightShiftRule->setName('夜班作业限制');
        $nightShiftRule->setRuleType('constraint');
        $nightShiftRule->setDescription('夜班时间限制某些类型的作业');
        $nightShiftRule->setConditions([
            'time_window' => [
                'start' => '22:00',
                'end' => '06:00',
            ],
            'restricted_tasks' => ['equipment_maintenance', 'hazardous_handling'],
        ]);
        $nightShiftRule->setActions([
            'restrictions' => [
                'defer_maintenance_tasks' => true,
                'limit_hazardous_operations' => true,
                'reduce_noise_level_tasks' => true,
            ],
            'safety' => [
                'require_additional_supervision' => true,
                'enhanced_safety_checks' => true,
            ],
        ]);
        $nightShiftRule->setPriority(75);
        $nightShiftRule->setIsActive(true);
        $nightShiftRule->setEffectiveFrom(new \DateTimeImmutable('2024-01-01'));
        $nightShiftRule->setNotes('夜班安全操作规则');

        $manager->persist($nightShiftRule);

        $manager->flush();
    }
}
