<?php

declare(strict_types=1);

namespace Tourze\WarehouseOperationBundle\Service\Quality\Validator;

/**
 * 通用检查验证器
 *
 * 处理其他未特定分类的验证逻辑，提供基本的验证功能。
 */
final class GenericCheckValidator implements QualityCheckValidatorInterface
{
    public function getSupportedCheckType(): string
    {
        return 'generic_check';
    }

    public function validate(mixed $checkValue, array $criteria, bool $strictMode): array
    {
        $defects = [];

        // 基本的非空检查（严格判定）
        $isMissing = is_array($checkValue) ? 0 === count($checkValue) : (null === $checkValue || '' === $checkValue);
        if ($isMissing && true === ($criteria['required'] ?? false)) {
            $defects[] = [
                'type' => 'missing_value',
                'message' => '必需的检查项为空',
                'critical' => $strictMode,
            ];
        }

        // 如果有预期值，进行比较
        if (isset($criteria['expected_value'])) {
            if ($checkValue !== $criteria['expected_value']) {
                $defects[] = [
                    'type' => 'value_mismatch',
                    'message' => '值不匹配预期',
                    'expected' => $criteria['expected_value'],
                    'actual' => $checkValue,
                    'critical' => $strictMode,
                ];
            }
        }

        return [
            'valid' => 0 === count($defects),
            'defects' => $defects,
        ];
    }
}
