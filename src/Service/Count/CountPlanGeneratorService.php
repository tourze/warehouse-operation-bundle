<?php

declare(strict_types=1);

namespace Tourze\WarehouseOperationBundle\Service\Count;

use Tourze\WarehouseOperationBundle\Entity\CountPlan;
use Tourze\WarehouseOperationBundle\Repository\CountPlanRepository;

/**
 * 盘点计划生成服务
 *
 * 专门负责盘点计划的生成逻辑，包括计划配置、范围设定和资源预估。
 * 遵循单一职责原则，降低主服务复杂度。
 */
final class CountPlanGeneratorService
{
    private CountPlanRepository $countPlanRepository;

    public function __construct(CountPlanRepository $countPlanRepository)
    {
        $this->countPlanRepository = $countPlanRepository;
    }

    /**
     * 生成盘点计划
     *
     * @param array<string, mixed> $criteria
     * @param array<string, mixed> $planOptions
     */
    public function generatePlan(string $countType, array $criteria, array $planOptions = []): CountPlan
    {
        $countPlan = new CountPlan();

        $this->setBasicInfo($countPlan, $countType, $criteria);
        $this->setScheduleInfo($countPlan, $countType, $planOptions);
        $this->setScope($countPlan, $countType, $criteria);
        $this->setScheduleConfig($countPlan, $countType, $planOptions, $criteria);
        $this->setInitialStatus($countPlan);

        $this->countPlanRepository->save($countPlan);

        return $countPlan;
    }

    /**
     * 设置基本信息
     *
     * @param array<string, mixed> $criteria
     */
    private function setBasicInfo(CountPlan $countPlan, string $countType, array $criteria): void
    {
        $countPlan->setCountType($countType);
        $countPlan->setName($this->generatePlanName($countType, $criteria));
        $countPlan->setDescription($this->generatePlanDescription($countType, $criteria));
        $countPlan->setPriority($this->calculateDefaultPriority($countType));
    }

    /**
     * 设置调度信息
     *
     * @param array<string, mixed> $planOptions
     */
    private function setScheduleInfo(CountPlan $countPlan, string $countType, array $planOptions): void
    {
        $scheduleDateValue = $planOptions['schedule_date'] ?? null;
        $scheduleDate = is_string($scheduleDateValue)
            ? new \DateTimeImmutable($scheduleDateValue)
            : new \DateTimeImmutable('+1 day');

        $durationDays = is_numeric($planOptions['duration_days'] ?? null)
            ? (int) $planOptions['duration_days']
            : $this->getDefaultDuration($countType);
        $endDate = $scheduleDate->modify("+{$durationDays} days");

        $countPlan->setStartDate($scheduleDate);
        $countPlan->setEndDate($endDate);
    }

    /**
     * 设置盘点范围
     *
     * @param array<string, mixed> $criteria
     */
    private function setScope(CountPlan $countPlan, string $countType, array $criteria): void
    {
        $scope = [
            'count_type' => $countType,
            'warehouse_zones' => $criteria['warehouse_zones'] ?? [],
            'product_categories' => $criteria['product_categories'] ?? [],
            'value_threshold' => $criteria['value_threshold'] ?? 0,
            'last_count_days' => $criteria['last_count_days'] ?? 0,
            'inventory_turnover' => $criteria['inventory_turnover'] ?? [],
            'accuracy_requirement' => $criteria['accuracy_requirement'] ?? 95.0,
        ];

        $countPlan->setScope($scope);
    }

    /**
     * 设置调度配置
     *
     * @param array<string, mixed> $planOptions
     * @param array<string, mixed> $criteria
     */
    private function setScheduleConfig(CountPlan $countPlan, string $countType, array $planOptions, array $criteria): void
    {
        $schedule = [
            'team_assignment' => $planOptions['team_assignment'] ?? 'auto',
            'work_hours' => [
                'start_time' => '08:00',
                'end_time' => '18:00',
                'break_duration' => 60,
            ],
            'estimated_task_count' => $this->estimateTaskCount($countType, $criteria),
            'resource_requirements' => $this->calculateResourceRequirements($countType, $criteria),
        ];

        $countPlan->setSchedule($schedule);
    }

    private function setInitialStatus(CountPlan $countPlan): void
    {
        $countPlan->setStatus('draft');
        $countPlan->setIsActive(true);
    }

    /**
     * 生成计划名称
     *
     * @param array<string, mixed> $criteria
     */
    private function generatePlanName(string $countType, array $criteria): string
    {
        $typeNames = [
            'full' => '全盘',
            'cycle' => '循环盘',
            'abc' => 'ABC盘',
            'spot' => '抽盘',
            'random' => '随机盘',
        ];

        $typeName = $typeNames[$countType] ?? $countType;
        $date = (new \DateTimeImmutable())->format('Y-m-d');

        return "{$typeName}点计划_{$date}";
    }

    /**
     * 生成计划描述
     *
     * @param array<string, mixed> $criteria
     */
    private function generatePlanDescription(string $countType, array $criteria): string
    {
        $description = "盘点类型: {$countType}";

        if (count((array) ($criteria['warehouse_zones'] ?? [])) > 0) {
            $description .= "\n盘点区域: " . implode(', ', (array) $criteria['warehouse_zones']);
        }

        if (count((array) ($criteria['product_categories'] ?? [])) > 0) {
            $description .= "\n商品类别: " . implode(', ', (array) $criteria['product_categories']);
        }

        return $description;
    }

    private function calculateDefaultPriority(string $countType): int
    {
        return match ($countType) {
            'full' => 90,
            'abc' => 80,
            'spot' => 70,
            'cycle' => 60,
            'random' => 40,
            default => 50,
        };
    }

    private function getDefaultDuration(string $countType): int
    {
        return match ($countType) {
            'full' => 7,
            'abc' => 3,
            'cycle' => 2,
            'spot' => 1,
            'random' => 1,
            default => 2,
        };
    }

    /**
     * 估算任务数量
     *
     * @param array<string, mixed> $criteria
     */
    private function estimateTaskCount(string $countType, array $criteria): int
    {
        $baseCount = match ($countType) {
            'full' => 1000,
            'abc' => 300,
            'cycle' => 200,
            'spot' => 50,
            'random' => 100,
            default => 100,
        };

        $warehouseZones = $criteria['warehouse_zones'] ?? [];
        $zoneCount = is_array($warehouseZones) ? count($warehouseZones) : 0;
        if ($zoneCount > 0) {
            $count = intval($baseCount * ($zoneCount / 5));
        } else {
            $count = $baseCount;
        }

        return max(1, $count);
    }

    /**
     * 计算资源需求
     *
     * @param array<string, mixed> $criteria
     * @return array<string, mixed>
     */
    private function calculateResourceRequirements(string $countType, array $criteria): array
    {
        return [
            'personnel_count' => 'full' === $countType ? 10 : 5,
            'equipment_needed' => ['barcode_scanner', 'tablet', 'printer'],
            'estimated_hours' => $this->getDefaultDuration($countType) * 8,
        ];
    }
}
