<?php

namespace Tourze\WarehouseOperationBundle\Service;

use Tourze\WarehouseOperationBundle\Entity\Location;

/**
 * 路径优化服务实现
 *
 * 提供多种路径优化算法，减少作业员移动距离，提升拣货效率。
 * 基于现有Location实体结构实现路径优化计算。
 */
class PathOptimizationService implements PathOptimizationServiceInterface
{
    private const DEFAULT_DISTANCE_UNIT = 1.0; // 默认距离单位
    private const DEFAULT_SPEED = 1.5; // 默认移动速度 (m/s)

    public function optimizePath(array $locations, string $strategy = 'shortest', array $constraints = []): array
    {
        if (0 === count($locations)) {
            return [
                'optimized_sequence' => [],
                'total_distance' => 0.0,
                'estimated_time' => 0.0,
                'efficiency_improvement' => 0.0,
            ];
        }

        // 计算原始路径距离（按输入顺序）
        $originalDistance = $this->calculatePathDistance($locations);

        // 根据策略优化路径
        $optimizedSequence = $this->applyOptimizationStrategy($locations, $strategy, $constraints);

        // 计算优化后的距离
        $optimizedDistance = $this->calculatePathDistance($optimizedSequence);

        // 计算效率提升
        $improvement = $originalDistance > 0
            ? (($originalDistance - $optimizedDistance) / $originalDistance) * 100
            : 0.0;

        return [
            'optimized_sequence' => $optimizedSequence,
            'total_distance' => $optimizedDistance,
            'estimated_time' => $this->calculateEstimatedTime($optimizedDistance),
            'efficiency_improvement' => round($improvement, 2),
        ];
    }

    public function optimizeBatchPaths(array $tasks, array $batchOptions = []): array
    {
        if (0 === count($tasks)) {
            return [
                'batch_results' => [],
                'total_distance_saved' => 0.0,
                'total_time_saved' => 0.0,
                'average_improvement' => 0.0,
            ];
        }

        $batchResults = [];
        $totalDistanceSaved = 0.0;
        $totalTimeSaved = 0.0;
        $improvements = [];

        $strategy = is_string($batchOptions['strategy'] ?? null) ? $batchOptions['strategy'] : 'shortest';
        /** @var array<string, mixed> $constraints */
        $constraints = is_array($batchOptions['constraints'] ?? null) ? $batchOptions['constraints'] : [];

        foreach ($tasks as $taskIndex => $task) {
            // 从任务中提取位置信息
            $locations = $this->extractLocationsFromTask($task);

            if (0 === count($locations)) {
                continue;
            }

            $result = $this->optimizePath($locations, $strategy, $constraints);

            // 计算节省的距离和时间
            $originalDistance = $this->calculatePathDistance($locations);
            $totalDistance = is_float($result['total_distance'] ?? null) ? $result['total_distance'] : 0.0;
            $distanceSaved = $originalDistance - $totalDistance;
            $timeSaved = $this->calculateEstimatedTime($distanceSaved);

            $totalDistanceSaved += $distanceSaved;
            $totalTimeSaved += $timeSaved;
            $improvements[] = $result['efficiency_improvement'];

            $batchResults[] = [
                'task_index' => $taskIndex,
                'result' => $result,
                'distance_saved' => round($distanceSaved, 2),
                'time_saved' => round($timeSaved, 2),
            ];
        }

        $averageImprovement = count($improvements) > 0
            ? array_sum($improvements) / count($improvements)
            : 0.0;

        return [
            'batch_results' => $batchResults,
            'total_distance_saved' => round($totalDistanceSaved, 2),
            'total_time_saved' => round($totalTimeSaved, 2),
            'average_improvement' => round($averageImprovement, 2),
        ];
    }

    /**
     * 根据策略应用路径优化
     *
     * @param Location[] $locations
     * @param string $strategy
     * @param array<string, mixed> $constraints
     * @return Location[]
     */
    private function applyOptimizationStrategy(array $locations, string $strategy, array $constraints): array
    {
        return match ($strategy) {
            'shortest' => $this->applyShortestPathStrategy($locations, $constraints),
            's_shape' => $this->applySShapeStrategy($locations, $constraints),
            'z_shape' => $this->applyZShapeStrategy($locations, $constraints),
            'dynamic' => $this->applyDynamicStrategy($locations, $constraints),
            default => $locations, // 原始顺序
        };
    }

    /**
     * 最短路径策略 - 使用简化的最近邻算法
     *
     * @param Location[] $locations
     * @param array<string, mixed> $constraints
     * @return Location[]
     */
    private function applyShortestPathStrategy(array $locations, array $constraints): array
    {
        if (count($locations) <= 1) {
            return $locations;
        }

        $optimized = [];
        $remaining = $locations;
        $current = array_shift($remaining); // 从第一个位置开始
        $optimized[] = $current;

        while (count($remaining) > 0) {
            $nearestIndex = 0;
            $minDistance = $this->calculateDistanceBetweenLocations($current, $remaining[0]);

            // 找到最近的位置
            for ($i = 1; $i < count($remaining); ++$i) {
                $distance = $this->calculateDistanceBetweenLocations($current, $remaining[$i]);
                if ($distance < $minDistance) {
                    $minDistance = $distance;
                    $nearestIndex = $i;
                }
            }

            $current = $remaining[$nearestIndex];
            $optimized[] = $current;
            array_splice($remaining, $nearestIndex, 1);
        }

        return $optimized;
    }

    /**
     * S型路径策略 - 按区域分组后优化
     *
     * @param Location[] $locations
     * @param array<string, mixed> $constraints
     * @return Location[]
     */
    private function applySShapeStrategy(array $locations, array $constraints): array
    {
        // 按Zone分组
        $grouped = $this->groupLocationsByZone($locations);
        $optimized = [];

        foreach ($grouped as $zoneLocations) {
            // 在每个Zone内应用最短路径
            $zoneOptimized = $this->applyShortestPathStrategy($zoneLocations, $constraints);
            $optimized = array_merge($optimized, $zoneOptimized);
        }

        return $optimized;
    }

    /**
     * Z型路径策略 - 按Shelf分组后优化
     *
     * @param Location[] $locations
     * @param array<string, mixed> $constraints
     * @return Location[]
     */
    private function applyZShapeStrategy(array $locations, array $constraints): array
    {
        // 按Shelf分组
        $grouped = $this->groupLocationsByShelf($locations);
        $optimized = [];

        foreach ($grouped as $shelfLocations) {
            // 在每个Shelf内按ID排序
            usort($shelfLocations, fn (Location $a, Location $b) => $a->getId() <=> $b->getId());
            $optimized = array_merge($optimized, $shelfLocations);
        }

        return $optimized;
    }

    /**
     * 动态策略 - 综合考虑距离和约束
     *
     * @param Location[] $locations
     * @param array<string, mixed> $constraints
     * @return Location[]
     */
    private function applyDynamicStrategy(array $locations, array $constraints): array
    {
        // 动态策略：根据位置密度选择最佳策略
        $zoneCount = count($this->groupLocationsByZone($locations));
        $shelfCount = count($this->groupLocationsByShelf($locations));

        if ($zoneCount > 1 && $shelfCount / $zoneCount > 2) {
            return $this->applySShapeStrategy($locations, $constraints);
        }
        if ($shelfCount > 3) {
            return $this->applyZShapeStrategy($locations, $constraints);
        }

        return $this->applyShortestPathStrategy($locations, $constraints);
    }

    /**
     * 计算路径总距离
     *
     * @param Location[] $locations
     * @return float
     */
    private function calculatePathDistance(array $locations): float
    {
        if (count($locations) <= 1) {
            return 0.0;
        }

        $totalDistance = 0.0;
        for ($i = 0; $i < count($locations) - 1; ++$i) {
            $totalDistance += $this->calculateDistanceBetweenLocations($locations[$i], $locations[$i + 1]);
        }

        return $totalDistance;
    }

    /**
     * 计算两个位置之间的距离
     *
     * 基于现有的层级结构进行简化距离计算：
     * - 同一Location: 距离为0
     * - 同一Shelf: 距离为1单位
     * - 同一Zone不同Shelf: 距离为3单位
     * - 不同Zone: 距离为10单位
     */
    private function calculateDistanceBetweenLocations(Location $from, Location $to): float
    {
        if ($from->getId() === $to->getId()) {
            return 0.0;
        }

        $fromShelf = $from->getShelf();
        $toShelf = $to->getShelf();

        if (null === $fromShelf || null === $toShelf) {
            return 10.0; // 默认距离
        }

        if ($fromShelf->getId() === $toShelf->getId()) {
            return self::DEFAULT_DISTANCE_UNIT; // 同一货架
        }

        $fromZone = $fromShelf->getZone();
        $toZone = $toShelf->getZone();

        if (null === $fromZone || null === $toZone) {
            return 10.0; // 默认距离
        }

        if ($fromZone->getId() === $toZone->getId()) {
            return 3.0 * self::DEFAULT_DISTANCE_UNIT; // 同一区域不同货架
        }

        return 10.0 * self::DEFAULT_DISTANCE_UNIT; // 不同区域
    }

    /**
     * 计算预估时间
     */
    private function calculateEstimatedTime(float $distance): float
    {
        return round($distance / self::DEFAULT_SPEED, 2);
    }

    /**
     * 按Zone分组位置
     *
     * @param Location[] $locations
     * @return array<int, Location[]>
     */
    private function groupLocationsByZone(array $locations): array
    {
        $grouped = [];

        foreach ($locations as $location) {
            $shelf = $location->getShelf();
            $zone = $shelf?->getZone();
            $zoneId = $zone?->getId() ?? 0;

            if (!isset($grouped[$zoneId])) {
                $grouped[$zoneId] = [];
            }
            $grouped[$zoneId][] = $location;
        }

        return $grouped;
    }

    /**
     * 按Shelf分组位置
     *
     * @param Location[] $locations
     * @return array<int, Location[]>
     */
    private function groupLocationsByShelf(array $locations): array
    {
        $grouped = [];

        foreach ($locations as $location) {
            $shelf = $location->getShelf();
            $shelfId = $shelf?->getId() ?? 0;

            if (!isset($grouped[$shelfId])) {
                $grouped[$shelfId] = [];
            }
            $grouped[$shelfId][] = $location;
        }

        return $grouped;
    }

    /**
     * 从任务中提取位置信息
     *
     * @param mixed $task
     * @return Location[]
     */
    /**
     * @return Location[]
     */
    private function extractLocationsFromTask(mixed $task): array
    {
        if (is_array($task)) {
            /** @var array<string, mixed> $typedTask */
            $typedTask = $task;

            return $this->extractLocationsFromArray($typedTask);
        }

        if (is_object($task)) {
            return $this->extractLocationsFromObject($task);
        }

        return [];
    }

    /**
     * 从数组任务中提取位置
     *
     * @param array<string, mixed> $task
     * @return Location[]
     */
    private function extractLocationsFromArray(array $task): array
    {
        $locations = $task['locations'] ?? [];
        if (!is_array($locations)) {
            return [];
        }

        return $this->filterLocationInstances($locations);
    }

    /**
     * 从对象任务中提取位置
     *
     * @return Location[]
     */
    private function extractLocationsFromObject(object $task): array
    {
        if (!method_exists($task, 'getLocations')) {
            return [];
        }

        $locationCollection = $task->getLocations();
        if (!is_object($locationCollection) || !method_exists($locationCollection, 'toArray')) {
            return [];
        }

        $result = $locationCollection->toArray();
        if (!is_array($result)) {
            return [];
        }

        return $this->filterLocationInstances($result);
    }

    /**
     * 过滤出Location实例
     *
     * @param array<mixed> $items
     * @return Location[]
     */
    private function filterLocationInstances(array $items): array
    {
        return array_values(array_filter($items, fn (mixed $item): bool => $item instanceof Location));
    }
}
