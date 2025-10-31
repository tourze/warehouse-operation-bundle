<?php

declare(strict_types=1);

namespace Tourze\WarehouseOperationBundle\Service\Quality;

use Tourze\WarehouseOperationBundle\Entity\QualityTask;

/**
 * 质检样品抽检服务
 */
final class QualitySampleInspectionService
{
    /**
     * 执行质检样品抽检
     *
     * @param array<string, mixed> $batchInfo
     * @param array<string, mixed> $samplingRules
     * @return array<string, mixed>
     */
    public function executeSampleInspection(array $batchInfo, array $samplingRules): array
    {
        $batchId = $batchInfo['batch_id'];
        $totalQuantityRaw = $batchInfo['total_quantity'] ?? 0;
        if (is_int($totalQuantityRaw)) {
            $totalQuantity = $totalQuantityRaw;
        } elseif (is_numeric($totalQuantityRaw)) {
            $totalQuantity = (int) $totalQuantityRaw;
        } else {
            $totalQuantity = 0;
        }

        $samplingMethodRaw = $samplingRules['sampling_method'] ?? 'random';
        $samplingMethod = is_string($samplingMethodRaw) ? $samplingMethodRaw : 'random';

        $sampleSizeRaw = $samplingRules['sample_size'] ?? null;
        if (is_int($sampleSizeRaw)) {
            $sampleSize = $sampleSizeRaw;
        } else {
            $calculatedSize = (int) floor($totalQuantity * 0.1);
            $sampleSize = min(10, max(1, $calculatedSize));
        }

        $samplePositions = $this->calculateSamplePositions($totalQuantity, $sampleSize, $samplingMethod);

        $sampleTasks = [];
        foreach ($samplePositions as $position) {
            $task = new QualityTask();
            $task->setData([
                'batch_id' => $batchId,
                'sample_position' => $position,
                'sampling_method' => $samplingMethod,
                'product_info' => $batchInfo['product_info'],
            ]);

            $sampleTasks[] = $task;
        }

        $samplingPlan = [
            'batch_id' => $batchId,
            'total_quantity' => $totalQuantity,
            'sample_size' => $sampleSize,
            'sampling_method' => $samplingMethod,
            'sample_positions' => $samplePositions,
            'acceptance_criteria' => $samplingRules['acceptance_criteria'] ?? [],
        ];

        $expectedCompletion = new \DateTimeImmutable('+2 hours');

        return [
            'sample_tasks' => $sampleTasks,
            'sampling_plan' => $samplingPlan,
            'expected_completion' => $expectedCompletion,
        ];
    }

    /**
     * 计算样本位置
     *
     * @return array<int, int>
     */
    private function calculateSamplePositions(int $totalQuantity, int $sampleSize, string $method): array
    {
        return match ($method) {
            'random' => $this->generateRandomPositions($totalQuantity, $sampleSize),
            'systematic' => $this->generateSystematicPositions($totalQuantity, $sampleSize),
            'stratified' => $this->generateStratifiedPositions($totalQuantity, $sampleSize),
            default => range(1, min($sampleSize, $totalQuantity)),
        };
    }

    /**
     * 生成随机位置
     *
     * @return array<int, int>
     */
    private function generateRandomPositions(int $total, int $size): array
    {
        $positions = range(1, $total);
        shuffle($positions);

        return array_slice($positions, 0, $size);
    }

    /**
     * 生成系统位置
     *
     * @return array<int, int>
     */
    private function generateSystematicPositions(int $total, int $size): array
    {
        $interval = intval($total / $size);
        $start = random_int(1, $interval);
        $positions = [];

        for ($i = 0; $i < $size; ++$i) {
            $position = $start + $i * $interval;
            if ($position <= $total) {
                $positions[] = $position;
            }
        }

        return $positions;
    }

    /**
     * 生成分层位置
     *
     * @return array<int, int>
     */
    private function generateStratifiedPositions(int $total, int $size): array
    {
        $strataSize = intval($total / $size);
        $positions = [];

        for ($i = 0; $i < $size; ++$i) {
            $strataStart = $i * $strataSize + 1;
            $strataEnd = min(($i + 1) * $strataSize, $total);
            $positions[] = random_int($strataStart, $strataEnd);
        }

        return $positions;
    }
}
