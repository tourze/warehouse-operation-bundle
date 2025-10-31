<?php

declare(strict_types=1);

namespace Tourze\WarehouseOperationBundle\Service\Quality\Processor;

/**
 * 质检结果构建器
 *
 * 负责构建质检结果数据和生成建议
 */
final class QualityResultBuilder
{
    /**
     * 构建结果数据
     *
     * @param array<string, mixed> $qualityResult
     * @param array<string, mixed> $checkData
     * @return array<string, mixed>
     */
    public function buildResultData(array $qualityResult, array $checkData, ?int $inspectorId): array
    {
        $finalScore = $this->calculateQualityScore($qualityResult);
        /** @var array<mixed> $rawDefects */
        $rawDefects = is_array($qualityResult['all_defects'] ?? null) ? $qualityResult['all_defects'] : [];
        /** @var array<array<string, mixed>> $allDefects */
        $allDefects = array_values(array_filter($rawDefects, function ($d): bool {
            return is_array($d);
        }));
        $overallResult = is_string($qualityResult['overall_result'] ?? null) ? $qualityResult['overall_result'] : 'fail';

        return [
            'overall_result' => $overallResult,
            'quality_score' => $finalScore,
            'check_results' => $qualityResult['check_results'] ?? [],
            'defects' => $allDefects,
            'recommendations' => $this->generateRecommendations($allDefects, $overallResult),
            'inspector_notes' => $checkData['inspector_notes'] ?? '',
            'photos' => $checkData['photos'] ?? [],
            'inspector_id' => $inspectorId,
            'checked_at' => new \DateTimeImmutable(),
        ];
    }

    /**
     * 计算质量分数
     *
     * @param array<string, mixed> $qualityResult
     */
    private function calculateQualityScore(array $qualityResult): float
    {
        $totalWeight = is_numeric($qualityResult['total_weight'] ?? null) ? (float) $qualityResult['total_weight'] : 0.0;
        $totalScore = is_numeric($qualityResult['total_score'] ?? null) ? (float) $qualityResult['total_score'] : 0.0;

        return $totalWeight > 0
            ? round($totalScore / $totalWeight, 2)
            : 0;
    }

    /**
     * @param array<array<string, mixed>> $defects
     * @return array<string>
     */
    private function generateRecommendations(array $defects, string $overallResult): array
    {
        if (0 === count($defects)) {
            return ['商品质检合格，可以正常入库'];
        }

        $recommendations = $this->collectDefectRecommendations($defects);

        if (0 === count($recommendations)) {
            $recommendations[] = $this->getDefaultRecommendation($overallResult);
        }

        return $recommendations;
    }

    /**
     * 收集缺陷相关建议
     *
     * @param array<array<string, mixed>> $defects
     * @return array<string>
     */
    private function collectDefectRecommendations(array $defects): array
    {
        $recommendations = [];
        $defectTypes = array_column($defects, 'type');

        $defectRecommendationMap = [
            'expired' => '立即隔离过期商品，联系供应商处理',
            'damage' => '检查商品损坏程度，考虑降价销售或退货',
            'quantity_mismatch' => '核实数量差异，更新库存记录',
            'spec_mismatch' => '验证商品规格，确认是否为正确商品',
        ];

        foreach ($defectRecommendationMap as $type => $recommendation) {
            if (in_array($type, $defectTypes, true)) {
                $recommendations[] = $recommendation;
            }
        }

        return $recommendations;
    }

    /**
     * 获取默认建议
     */
    private function getDefaultRecommendation(string $overallResult): string
    {
        return 'fail' === $overallResult
            ? '商品存在质量问题，建议隔离处理'
            : '商品存在轻微问题，可考虑有条件接收';
    }
}
