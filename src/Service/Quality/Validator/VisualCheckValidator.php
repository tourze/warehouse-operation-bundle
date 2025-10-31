<?php

declare(strict_types=1);

namespace Tourze\WarehouseOperationBundle\Service\Quality\Validator;

/**
 * 视觉检查验证器
 *
 * 专门处理视觉检查相关的验证逻辑，包括外观、包装、损坏等。
 */
final class VisualCheckValidator implements QualityCheckValidatorInterface
{
    public function getSupportedCheckType(): string
    {
        return 'visual_check';
    }

    public function validate(mixed $checkValue, array $criteria, bool $strictMode): array
    {
        if (!is_array($checkValue)) {
            return [
                'valid' => false,
                'defects' => [
                    ['type' => 'invalid_data', 'message' => '视觉检查数据格式错误', 'critical' => true],
                ],
            ];
        }

        // 确保 $checkValue 是 array<string, mixed> 类型
        /** @var array<string, mixed> $typedCheckValue */
        $typedCheckValue = array_filter($checkValue, fn ($key): bool => is_string($key), ARRAY_FILTER_USE_KEY);

        $defects = [];
        $allowedConditionsRaw = $criteria['allowed_conditions'] ?? ['perfect', 'good', 'damaged'];
        /** @var array<string> $allowedConditions */
        $allowedConditions = is_array($allowedConditionsRaw) ? $allowedConditionsRaw : ['perfect', 'good', 'damaged'];

        $conditionDefects = $this->validateCondition($typedCheckValue, $allowedConditions);
        $defects = array_merge($defects, $conditionDefects);

        $damageDefects = $this->validateDamage($typedCheckValue, $criteria, $strictMode);
        $defects = array_merge($defects, $damageDefects);

        return [
            'valid' => 0 === count($defects),
            'defects' => $defects,
        ];
    }

    /**
     * 验证商品状况
     *
     * @param array<string, mixed> $checkValue
     * @param array<string> $allowedConditions
     * @return array<array<string, mixed>>
     */
    private function validateCondition(array $checkValue, array $allowedConditions): array
    {
        $condition = $checkValue['condition'] ?? '';
        $conditionStr = is_string($condition) ? $condition : '';

        if (!in_array($conditionStr, $allowedConditions, true)) {
            return [
                [
                    'type' => 'invalid_condition',
                    'message' => "状况 '{$conditionStr}' 不在允许范围内",
                    'critical' => true,
                ],
            ];
        }

        return [];
    }

    /**
     * 验证损坏情况
     *
     * @param array<string, mixed> $checkValue
     * @param array<string, mixed> $criteria
     * @return array<array<string, mixed>>
     */
    private function validateDamage(array $checkValue, array $criteria, bool $strictMode): array
    {
        if (!isset($checkValue['damage']) || false === $checkValue['damage']) {
            return [];
        }

        $damageLevelRaw = $checkValue['damage_level'] ?? 'unknown';
        $damageLevel = is_string($damageLevelRaw) ? $damageLevelRaw : 'unknown';
        $maxDamageRaw = $strictMode ? ($criteria['max_damage_strict'] ?? 'none') : ($criteria['max_damage'] ?? 'minor');
        $maxDamage = is_string($maxDamageRaw) ? $maxDamageRaw : 'minor';

        if ($this->compareDamageLevel($damageLevel, $maxDamage) > 0) {
            return [
                [
                    'type' => 'excessive_damage',
                    'message' => "损坏程度 '{$damageLevel}' 超过允许的 '{$maxDamage}'",
                    'critical' => true,
                ],
            ];
        }

        return [];
    }

    /**
     * 比较损坏程度
     */
    private function compareDamageLevel(string $level1, string $level2): int
    {
        $damageLevels = ['none' => 0, 'minor' => 1, 'moderate' => 2, 'major' => 3, 'severe' => 4];

        $value1 = $damageLevels[$level1] ?? 0;
        $value2 = $damageLevels[$level2] ?? 0;

        return $value1 <=> $value2;
    }
}
