<?php

declare(strict_types=1);

namespace Tourze\WarehouseOperationBundle\Service\Count;

/**
 * 盘点数据验证服务
 *
 * 专门负责盘点数据的质量验证和完整性检查。
 * 提供标准化的验证规则和错误处理。
 */
final class CountDataValidatorService
{
    /**
     * 验证盘点数据
     *
     * @param array<string, mixed> $countData
     * @return array<string, mixed>
     */
    public function validateCountData(array $countData): array
    {
        $errors = [];

        $errors = $this->validateRequiredFields($countData, $errors);
        $errors = $this->validateDataTypes($countData, $errors);
        $errors = $this->validateDataReasonableness($countData, $errors);

        return [
            'valid' => 0 === count($errors),
            'errors' => $errors,
        ];
    }

    /**
     * 批量验证盘点数据质量
     *
     * @param array<array<string, mixed>> $countDataBatch
     * @param array<string, mixed> $validationRules
     * @return array<string, mixed>
     */
    public function validateCountDataQuality(array $countDataBatch, array $validationRules = []): array
    {
        if (0 === count($countDataBatch)) {
            return $this->createEmptyValidationResult();
        }

        $validationResult = $this->createInitialValidationResult();

        foreach ($countDataBatch as $index => $countData) {
            $validationResult = $this->processRowValidation($index, $countData, $validationResult);
        }

        return $this->finalizeValidationResult($validationResult);
    }

    /**
     * 验证必填字段
     *
     * @param array<string, mixed> $countData
     * @param array<string> $errors
     * @return array<string>
     */
    private function validateRequiredFields(array $countData, array $errors): array
    {
        if (!isset($countData['system_quantity'])) {
            $errors[] = 'Missing system_quantity';
        }

        if (!isset($countData['actual_quantity'])) {
            $errors[] = 'Missing actual_quantity';
        }

        return $errors;
    }

    /**
     * 验证数据类型
     *
     * @param array<string, mixed> $countData
     * @param array<string> $errors
     * @return array<string>
     */
    private function validateDataTypes(array $countData, array $errors): array
    {
        if (isset($countData['system_quantity']) && !is_numeric($countData['system_quantity'])) {
            $errors[] = 'system_quantity must be numeric';
        }

        if (isset($countData['actual_quantity']) && !is_numeric($countData['actual_quantity'])) {
            $errors[] = 'actual_quantity must be numeric';
        }

        return $errors;
    }

    /**
     * 验证数据合理性
     *
     * @param array<string, mixed> $countData
     * @param array<string> $errors
     * @return array<string>
     */
    private function validateDataReasonableness(array $countData, array $errors): array
    {
        if (isset($countData['actual_quantity']) && $countData['actual_quantity'] < 0) {
            $errors[] = 'actual_quantity cannot be negative';
        }

        return $errors;
    }

    /**
     * 创建空验证结果
     *
     * @return array<string, mixed>
     */
    private function createEmptyValidationResult(): array
    {
        return [
            'validation_passed' => true,
            'data_quality_score' => 100,
            'validation_errors' => [],
            'data_corrections' => [],
        ];
    }

    /**
     * 创建初始验证结果
     *
     * @return array<string, mixed>
     */
    private function createInitialValidationResult(): array
    {
        return [
            'errors' => [],
            'corrections' => [],
            'quality_score' => 100,
        ];
    }

    /**
     * 处理单行验证
     *
     * @param array<string, mixed> $countData
     * @param array<string, mixed> $result
     * @return array<string, mixed>
     */
    private function processRowValidation(int $index, array $countData, array $result): array
    {
        $result = $this->validateRequiredFieldsForRow($index, $countData, $result);
        $result = $this->validateDataTypesForRow($index, $countData, $result);

        return $this->validateDataReasonablenessForRow($index, $countData, $result);
    }

    /**
     * 验证行必填字段
     *
     * @param array<string, mixed> $countData
     * @param array<string, mixed> $result
     * @return array<string, mixed>
     */
    private function validateRequiredFieldsForRow(int $index, array $countData, array $result): array
    {
        $requiredFields = ['system_quantity', 'actual_quantity', 'location_code', 'product_info'];

        foreach ($requiredFields as $field) {
            if (!$this->isFieldPresent($countData, $field)) {
                if (!is_array($result['errors'])) {
                    $result['errors'] = [];
                }
                $result['errors'][] = "Row {$index}: Missing required field '{$field}'";
                if (is_numeric($result['quality_score'])) {
                    $result['quality_score'] = (float) $result['quality_score'] - 10;
                }
            }
        }

        return $result;
    }

    /**
     * 验证行数据类型
     *
     * @param array<string, mixed> $countData
     * @param array<string, mixed> $result
     * @return array<string, mixed>
     */
    private function validateDataTypesForRow(int $index, array $countData, array $result): array
    {
        $numericFields = ['system_quantity', 'actual_quantity'];

        foreach ($numericFields as $field) {
            if (isset($countData[$field]) && !is_numeric($countData[$field])) {
                if (!is_array($result['errors'])) {
                    $result['errors'] = [];
                }
                $result['errors'][] = "Row {$index}: {$field} must be numeric";
                if (is_numeric($result['quality_score'])) {
                    $result['quality_score'] = (float) $result['quality_score'] - 5;
                }
            }
        }

        return $result;
    }

    /**
     * 验证行数据合理性
     *
     * @param array<string, mixed> $countData
     * @param array<string, mixed> $result
     * @return array<string, mixed>
     */
    private function validateDataReasonablenessForRow(int $index, array $countData, array $result): array
    {
        $result = $this->validateNegativeQuantity($index, $countData, $result);

        return $this->validateExcessiveDiscrepancy($index, $countData, $result);
    }

    /**
     * 验证负数量
     *
     * @param array<string, mixed> $countData
     * @param array<string, mixed> $result
     * @return array<string, mixed>
     */
    private function validateNegativeQuantity(int $index, array $countData, array $result): array
    {
        $actualQuantity = $countData['actual_quantity'] ?? null;

        if (is_numeric($actualQuantity) && $actualQuantity < 0) {
            if (!is_array($result['errors'])) {
                $result['errors'] = [];
            }
            if (!is_array($result['corrections'])) {
                $result['corrections'] = [];
            }
            $result['errors'][] = "Row {$index}: actual_quantity cannot be negative";
            $result['corrections'][] = "Row {$index}: Set actual_quantity to 0";
            if (is_numeric($result['quality_score'])) {
                $result['quality_score'] = (float) $result['quality_score'] - 3;
            }
        }

        return $result;
    }

    /**
     * 验证过度差异
     *
     * @param array<string, mixed> $countData
     * @param array<string, mixed> $result
     * @return array<string, mixed>
     */
    private function validateExcessiveDiscrepancy(int $index, array $countData, array $result): array
    {
        $systemQty = $countData['system_quantity'] ?? null;
        $actualQty = $countData['actual_quantity'] ?? null;

        if (!is_numeric($systemQty) || !is_numeric($actualQty)) {
            return $result;
        }

        $systemQty = (float) $systemQty;
        $actualQty = (float) $actualQty;

        if ($systemQty > 0) {
            $discrepancyRatio = abs($systemQty - $actualQty) / $systemQty;
            if ($discrepancyRatio > 2.0) {
                if (!is_array($result['errors'])) {
                    $result['errors'] = [];
                }
                $result['errors'][] = "Row {$index}: Excessive difference (>200%) may indicate data error";
                if (is_numeric($result['quality_score'])) {
                    $result['quality_score'] = (float) $result['quality_score'] - 15;
                }
            }
        }

        return $result;
    }

    /**
     * 检查字段是否存在且不为null
     *
     * @param array<string, mixed> $data
     */
    private function isFieldPresent(array $data, string $field): bool
    {
        return array_key_exists($field, $data) && null !== $data[$field];
    }

    /**
     * 完成验证结果
     *
     * @param array<string, mixed> $result
     * @return array<string, mixed>
     */
    private function finalizeValidationResult(array $result): array
    {
        $errors = is_array($result['errors']) ? $result['errors'] : [];
        $qualityScore = is_numeric($result['quality_score']) ? (float) $result['quality_score'] : 0;

        return [
            'validation_passed' => 0 === count($errors),
            'data_quality_score' => max(0, $qualityScore),
            'validation_errors' => $errors,
            'data_corrections' => is_array($result['corrections']) ? $result['corrections'] : [],
        ];
    }
}
