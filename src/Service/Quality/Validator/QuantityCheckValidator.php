<?php

declare(strict_types=1);

namespace Tourze\WarehouseOperationBundle\Service\Quality\Validator;

/**
 * 数量检查验证器
 *
 * 专门处理数量相关的验证逻辑，包括数量对比、容差检查等。
 */
final class QuantityCheckValidator implements QualityCheckValidatorInterface
{
    public function getSupportedCheckType(): string
    {
        return 'quantity_check';
    }

    public function validate(mixed $checkValue, array $criteria, bool $strictMode): array
    {
        if (!is_array($checkValue)) {
            return [
                'valid' => false,
                'defects' => [
                    ['type' => 'invalid_data', 'message' => '数量检查数据格式错误', 'critical' => true],
                ],
            ];
        }

        $expectedValue = $checkValue['expected'] ?? 0;
        $expected = is_numeric($expectedValue) ? (int) $expectedValue : 0;

        $actualValue = $checkValue['actual'] ?? 0;
        $actual = is_numeric($actualValue) ? (int) $actualValue : 0;
        $toleranceValue = $strictMode ? ($criteria['strict_tolerance'] ?? 0) : ($criteria['tolerance'] ?? 1);
        $tolerance = is_numeric($toleranceValue) ? (int) $toleranceValue : 1;

        $difference = abs($expected - $actual);
        $defects = [];

        if ($difference > $tolerance) {
            $toleranceStr = (string) $tolerance;
            $defects[] = [
                'type' => 'quantity_mismatch',
                'message' => "数量差异 {$difference} 超过容差 {$toleranceStr}",
                'expected' => $expected,
                'actual' => $actual,
                'difference' => $difference,
                'critical' => $difference > ($tolerance * 2),
            ];
        }

        return [
            'valid' => 0 === count($defects),
            'defects' => $defects,
        ];
    }
}
