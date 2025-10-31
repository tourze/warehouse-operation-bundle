<?php

declare(strict_types=1);

namespace Tourze\WarehouseOperationBundle\Service\Quality;

use Tourze\WarehouseOperationBundle\Entity\QualityStandard;

/**
 * 质检标准验证服务
 */
final class QualityStandardValidationService
{
    /**
     * 验证质检标准配置
     *
     * @param array<string, mixed> $validationContext
     * @return array<string, mixed>
     */
    public function validateQualityStandard(QualityStandard $standard, array $validationContext = []): array
    {
        /** @var array<string> $errors */
        $errors = [];
        /** @var array<string> $warnings */
        $warnings = [];
        /** @var array<string> $suggestions */
        $suggestions = [];

        $checkItemsResult = $this->validateCheckItems($standard->getCheckItems());
        $errors = array_merge($errors, (array) $checkItemsResult['errors']);
        $warnings = array_merge($warnings, (array) $checkItemsResult['warnings']);
        $suggestions = array_merge($suggestions, (array) $checkItemsResult['suggestions']);

        $basicErrors = $this->validateBasicFields($standard);
        $errors = array_merge($errors, $basicErrors);

        return [
            'is_valid' => 0 === count($errors),
            'validation_errors' => $errors,
            'warnings' => $warnings,
            'suggestions' => $suggestions,
        ];
    }

    /**
     * 验证检查项
     *
     * @param array<string, mixed> $checkItems
     * @return array<string, mixed>
     */
    private function validateCheckItems(array $checkItems): array
    {
        /** @var array<string> $errors */
        $errors = [];
        /** @var array<string> $warnings */
        $warnings = [];
        /** @var array<string> $suggestions */
        $suggestions = [];

        if (0 === count($checkItems)) {
            $errors[] = '质检标准必须包含至少一个检查项';

            return ['errors' => $errors, 'warnings' => $warnings, 'suggestions' => $suggestions];
        }

        $warnings = array_merge($warnings, $this->validateRequiredChecks($checkItems));
        $configResult = $this->validateCheckItemConfigs($checkItems);
        $errors = array_merge($errors, (array) $configResult['errors']);
        $warnings = array_merge($warnings, (array) $configResult['warnings']);
        $suggestions = array_merge($suggestions, (array) $configResult['suggestions']);

        return ['errors' => $errors, 'warnings' => $warnings, 'suggestions' => $suggestions];
    }

    /**
     * 验证必需的检查项
     *
     * @param array<string, mixed> $checkItems
     * @return array<int, string>
     */
    private function validateRequiredChecks(array $checkItems): array
    {
        $warnings = [];
        $requiredChecks = ['visual_check', 'quantity_check'];
        foreach ($requiredChecks as $check) {
            if (!isset($checkItems[$check])) {
                $warnings[] = "建议添加 {$check} 检查项";
            }
        }

        return $warnings;
    }

    /**
     * 验证检查项配置完整性
     *
     * @param array<string, mixed> $checkItems
     * @return array<string, mixed>
     */
    private function validateCheckItemConfigs(array $checkItems): array
    {
        $errors = [];
        $warnings = [];
        $suggestions = [];
        foreach ($checkItems as $checkKey => $checkConfig) {
            if (!is_array($checkConfig)) {
                $errors[] = "检查项 {$checkKey} 配置格式错误";
                continue;
            }

            if (!isset($checkConfig['enabled'])) {
                $warnings[] = "检查项 {$checkKey} 缺少 enabled 配置";
            }

            if (!isset($checkConfig['weight'])) {
                $suggestions[] = "建议为检查项 {$checkKey} 设置权重";
            }

            if (isset($checkConfig['criteria']) && is_array($checkConfig['criteria']) && 0 === count($checkConfig['criteria'])) {
                $warnings[] = "检查项 {$checkKey} 的判定标准为空";
            }
        }

        return ['errors' => $errors, 'warnings' => $warnings, 'suggestions' => $suggestions];
    }

    /**
     * 验证基本字段
     *
     * @return array<int, string>
     */
    private function validateBasicFields(QualityStandard $standard): array
    {
        $errors = [];
        if ('' === $standard->getName()) {
            $errors[] = '质检标准名称不能为空';
        }

        if ('' === $standard->getProductCategory()) {
            $errors[] = '商品类别不能为空';
        }

        if ($standard->getPriority() < 1 || $standard->getPriority() > 100) {
            $errors[] = '优先级必须在1-100之间';
        }

        return $errors;
    }
}
