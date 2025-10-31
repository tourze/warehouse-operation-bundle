<?php

declare(strict_types=1);

namespace Tourze\WarehouseOperationBundle\Service\Quality;

use Tourze\WarehouseOperationBundle\Repository\WarehouseTaskRepository;

/**
 * 质检报告服务
 */
final class QualityReportService
{
    private WarehouseTaskRepository $qualityTaskRepository;

    public function __construct(WarehouseTaskRepository $qualityTaskRepository)
    {
        $this->qualityTaskRepository = $qualityTaskRepository;
    }

    /**
     * 生成质检报告
     *
     * @param array<string|int> $taskIds
     * @param array<string, mixed> $reportOptions
     * @return array<string, mixed>
     */
    public function generateQualityReport(array $taskIds, array $reportOptions = []): array
    {
        $reportConfig = $this->extractReportConfig($reportOptions);
        $tasks = $this->qualityTaskRepository->findBy(['id' => $taskIds]);

        $reportData = $this->buildReportData($tasks, $reportConfig);
        $tasksForGroupingRaw = $reportData['tasks'] ?? [];
        // 确保 $tasksForGrouping 是 array<int, array<string, mixed>> 类型
        /** @var array<int, array<string, mixed>> $tasksForGrouping */
        $tasksForGrouping = is_array($tasksForGroupingRaw) ? array_filter($tasksForGroupingRaw, fn ($task): bool => is_array($task)) : [];
        $groupByRaw = $reportConfig['group_by'] ?? 'date';
        $groupBy = is_string($groupByRaw) ? $groupByRaw : 'date';
        $groupedData = $this->createGroupedDataIfNeeded($tasksForGrouping, $groupBy);

        return $this->formatReportOutput($reportData, $groupedData);
    }

    /**
     * 提取报告配置
     *
     * @param array<string, mixed> $reportOptions
     * @return array<string, mixed>
     */
    private function extractReportConfig(array $reportOptions): array
    {
        $format = $reportOptions['format'] ?? 'json';
        $includePhotos = (bool) ($reportOptions['include_photos'] ?? false);
        $groupByRaw = $reportOptions['group_by'] ?? 'date';
        $groupBy = is_string($groupByRaw) ? $groupByRaw : 'date';

        return [
            'format' => $format,
            'include_photos' => $includePhotos,
            'group_by' => $groupBy,
        ];
    }

    /**
     * 构建报告数据
     *
     * @param array<object> $tasks
     * @param array<string, mixed> $reportConfig
     * @return array<string, mixed>
     */
    private function buildReportData(array $tasks, array $reportConfig): array
    {
        $reportData = [
            'report_id' => uniqid('QR_'),
            'generated_at' => new \DateTimeImmutable(),
            'task_count' => count($tasks),
            'summary_statistics' => $this->calculateReportStatistics($tasks),
            'tasks' => [],
        ];

        foreach ($tasks as $task) {
            $reportData['tasks'][] = $this->buildTaskReportItem($task, (bool) $reportConfig['include_photos']);
        }

        return $reportData;
    }

    /**
     * 构建任务报告项
     *
     * @param object $task
     * @return array<string, mixed>
     */
    private function buildTaskReportItem(mixed $task, bool $includePhotos): array
    {
        $this->validateTaskObject($task);
        /** @var object $task */
        $taskData = $this->extractTaskData($task);
        $qualityResult = $this->extractQualityResult($taskData);
        $statusString = $this->extractTaskStatus($task);

        return [
            'task_id' => method_exists($task, 'getId') ? $task->getId() : null,
            'status' => $statusString,
            'overall_result' => $qualityResult['overall_result'],
            'quality_score' => $qualityResult['quality_score'],
            'defect_count' => is_array($qualityResult['defects']) ? count($qualityResult['defects']) : 0,
            'inspector_id' => $qualityResult['inspector_id'],
            'checked_at' => $qualityResult['checked_at'],
            'photos' => $includePhotos ? $qualityResult['photos'] : [],
        ];
    }

    /**
     * @param mixed $task
     */
    private function validateTaskObject(mixed $task): void
    {
        if (!is_object($task) || !method_exists($task, 'getData') || !method_exists($task, 'getId') || !method_exists($task, 'getStatus')) {
            throw new \InvalidArgumentException('Task must be an object with getData(), getId() and getStatus() methods');
        }
    }

    /**
     * @param object $task
     * @return array<string, mixed>
     */
    private function extractTaskData(object $task): array
    {
        if (!method_exists($task, 'getData')) {
            throw new \InvalidArgumentException('Task must have getData() method');
        }

        $taskDataRaw = $task->getData();
        if (!is_array($taskDataRaw)) {
            throw new \InvalidArgumentException('Task data must be an array');
        }

        /** @var array<string, mixed> $taskDataRaw */
        return $taskDataRaw;
    }

    private function extractTaskStatus(object $task): string
    {
        if (!method_exists($task, 'getStatus')) {
            throw new \InvalidArgumentException('Task must have getStatus() method');
        }

        $statusValue = $task->getStatus();
        if (is_object($statusValue) && property_exists($statusValue, 'value')) {
            return is_string($statusValue->value) || is_numeric($statusValue->value)
                ? (string) $statusValue->value
                : 'unknown';
        }

        return is_string($statusValue) || is_numeric($statusValue)
            ? (string) $statusValue
            : 'unknown';
    }

    /**
     * 提取质检结果
     *
     * @param array<string, mixed> $taskData
     * @return array<string, mixed>
     */
    private function extractQualityResult(array $taskData): array
    {
        $qualityResultRaw = $taskData['quality_result'] ?? [];
        $qualityResult = is_array($qualityResultRaw) ? $qualityResultRaw : [];

        $defectsRaw = $qualityResult['defects'] ?? [];
        $defects = is_array($defectsRaw) ? $defectsRaw : [];

        $overallResult = $qualityResult['overall_result'] ?? 'unknown';
        $qualityScore = $qualityResult['quality_score'] ?? 0;
        $inspectorId = $qualityResult['inspector_id'] ?? null;
        $checkedAt = $qualityResult['checked_at'] ?? null;
        $photosRaw = $qualityResult['photos'] ?? [];
        $photos = is_array($photosRaw) ? $photosRaw : [];

        return [
            'overall_result' => is_string($overallResult) ? $overallResult : 'unknown',
            'quality_score' => is_numeric($qualityScore) ? (float) $qualityScore : 0.0,
            'defects' => $defects,
            'inspector_id' => $inspectorId,
            'checked_at' => $checkedAt,
            'photos' => $photos,
        ];
    }

    /**
     * 根据需要创建分组数据
     *
     * @param array<int, array<string, mixed>> $tasks
     * @return array<string, mixed>|null
     */
    private function createGroupedDataIfNeeded(array $tasks, string $groupBy): ?array
    {
        if ('none' === $groupBy) {
            return null;
        }

        return $this->groupReportData($tasks, $groupBy);
    }

    /**
     * 格式化报告输出
     *
     * @param array<string, mixed> $reportData
     * @param array<string, mixed>|null $groupedData
     * @return array<string, mixed>
     */
    private function formatReportOutput(array $reportData, ?array $groupedData): array
    {
        $output = [
            'report_id' => $reportData['report_id'],
            'file_url' => null, // 实际项目中会生成文件并返回URL
            'summary_statistics' => $reportData['summary_statistics'],
            'generated_at' => $reportData['generated_at'],
            'report_data' => $reportData,
        ];

        if (null !== $groupedData) {
            $output['report_data']['grouped_data'] = $groupedData;
        }

        return $output;
    }

    /**
     * 计算报告统计数据
     *
     * @param array<object> $tasks
     * @return array<string, mixed>
     */
    private function calculateReportStatistics(array $tasks): array
    {
        $totalTasks = count($tasks);
        $passCount = 0;
        $failCount = 0;
        $conditionalCount = 0;
        $totalScore = 0.0;

        foreach ($tasks as $task) {
            if (!method_exists($task, 'getData')) {
                continue;
            }

            $taskData = $task->getData();
            if (!is_array($taskData)) {
                continue;
            }

            $qualityResultRaw = $taskData['quality_result'] ?? [];
            $qualityResult = is_array($qualityResultRaw) ? $qualityResultRaw : [];
            $result = $qualityResult['overall_result'] ?? 'unknown';

            switch ($result) {
                case 'pass':
                    $passCount++;
                    break;
                case 'fail':
                    $failCount++;
                    break;
                case 'conditional':
                    $conditionalCount++;
                    break;
            }

            $scoreRaw = $qualityResult['quality_score'] ?? 0;
            $totalScore += is_numeric($scoreRaw) ? (float) $scoreRaw : 0.0;
        }

        return [
            'total_tasks' => $totalTasks,
            'pass_count' => $passCount,
            'fail_count' => $failCount,
            'conditional_count' => $conditionalCount,
            'pass_rate' => $totalTasks > 0 ? round($passCount / $totalTasks * 100, 2) : 0,
            'average_score' => $totalTasks > 0 ? round($totalScore / $totalTasks, 2) : 0,
        ];
    }

    /**
     * 按指定方式分组报告数据
     *
     * @param array<int, array<string, mixed>> $tasks
     * @return array<string, mixed>
     */
    private function groupReportData(array $tasks, string $groupBy): array
    {
        $groups = [];

        foreach ($tasks as $task) {
            $checkedAtRaw = $task['checked_at'] ?? null;
            $checkedAtStr = null;
            if ($checkedAtRaw instanceof \DateTimeInterface) {
                $checkedAtStr = $checkedAtRaw->format('Y-m-d');
            } elseif (is_string($checkedAtRaw)) {
                $checkedAtStr = $checkedAtRaw;
            }

            $groupKey = match ($groupBy) {
                'date' => null !== $checkedAtStr
                    ? (new \DateTimeImmutable($checkedAtStr))->format('Y-m-d')
                    : (new \DateTimeImmutable())->format('Y-m-d'),
                'inspector' => is_scalar($task['inspector_id'] ?? null)
                    ? (string) ($task['inspector_id'])
                    : 'unknown',
                'result' => is_string($task['overall_result'] ?? null)
                    ? $task['overall_result']
                    : 'unknown',
                default => 'all',
            };

            if (!isset($groups[$groupKey])) {
                $groups[$groupKey] = [];
            }

            $groups[$groupKey][] = $task;
        }

        return $groups;
    }
}
