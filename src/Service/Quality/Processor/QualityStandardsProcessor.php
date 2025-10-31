<?php

declare(strict_types=1);

namespace Tourze\WarehouseOperationBundle\Service\Quality\Processor;

use Tourze\WarehouseOperationBundle\Entity\QualityStandard;
use Tourze\WarehouseOperationBundle\Service\Quality\Validator\QualityCheckValidatorRegistryInterface;

/**
 * 质检标准处理器
 *
 * 负责处理质检标准的检查逻辑
 */
final class QualityStandardsProcessor
{
    private QualityCheckValidatorRegistryInterface $validatorRegistry;

    public function __construct(QualityCheckValidatorRegistryInterface $validatorRegistry)
    {
        $this->validatorRegistry = $validatorRegistry;
    }

    /**
     * 处理质检标准
     *
     * @param QualityStandard[] $standards
     * @param array<string, mixed> $checkData
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    public function processStandards(array $standards, array $checkData, array $options): array
    {
        $initialResults = $this->initializeQualityResults();

        return $this->executeAllStandardChecks($standards, $checkData, $options, $initialResults);
    }

    /**
     * 初始化质检结果结构
     *
     * @return array{check_results: array<string, mixed>, all_defects: array<array<string, mixed>>, total_score: int, total_weight: int, overall_result: string}
     */
    private function initializeQualityResults(): array
    {
        return [
            'check_results' => [],
            'all_defects' => [],
            'total_score' => 0,
            'total_weight' => 0,
            'overall_result' => 'pass',
        ];
    }

    /**
     * 执行所有标准检查
     *
     * @param QualityStandard[] $standards
     * @param array<string, mixed> $checkData
     * @param array<string, mixed> $options
     * @param array{check_results: array<string, mixed>, all_defects: array<array<string, mixed>>, total_score: int, total_weight: int, overall_result: string} $results
     * @return array<string, mixed>
     */
    private function executeAllStandardChecks(array $standards, array $checkData, array $options, array $results): array
    {
        foreach ($standards as $standard) {
            $results = $this->processStandardCheck($standard, $checkData, $options, $results);
        }

        return $results;
    }

    /**
     * 处理单个标准检查
     *
     * @param array<string, mixed> $checkData
     * @param array<string, mixed> $options
     * @param array<string, mixed> $results
     * @return array<string, mixed>
     */
    private function processStandardCheck(QualityStandard $standard, array $checkData, array $options, array $results): array
    {
        $standardResult = $this->executeStandardCheck($standard, $checkData, $options);

        return $this->processStandardResult($standard, $standardResult, $results);
    }

    /**
     * 处理标准检查结果
     *
     * @param array<string, mixed> $standardResult
     * @param array<string, mixed> $results
     * @return array<string, mixed>
     */
    private function processStandardResult(QualityStandard $standard, array $standardResult, array $results): array
    {
        $results = $this->recordStandardResult($standard, $standardResult, $results);
        $results = $this->mergeStandardDefects($standardResult, $results);
        $results = $this->updateStandardScores($standard, $standardResult, $results);

        return $this->updateOverallResultStatus($standardResult, $results);
    }

    /**
     * 记录标准检查结果
     *
     * @param array<string, mixed> $standardResult
     * @param array<string, mixed> $results
     * @return array<string, mixed>
     */
    private function recordStandardResult(QualityStandard $standard, array $standardResult, array $results): array
    {
        $standardId = $standard->getId();
        if (null !== $standardId) {
            if (!is_array($results['check_results'] ?? null)) {
                $results['check_results'] = [];
            }
            $results['check_results'][$standardId] = $standardResult;
        }

        return $results;
    }

    /**
     * 合并标准缺陷
     *
     * @param array<string, mixed> $standardResult
     * @param array<string, mixed> $results
     * @return array<string, mixed>
     */
    private function mergeStandardDefects(array $standardResult, array $results): array
    {
        $standardDefects = is_array($standardResult['defects'] ?? null) ? $standardResult['defects'] : [];
        $existingDefects = is_array($results['all_defects'] ?? null) ? $results['all_defects'] : [];
        $results['all_defects'] = array_merge($existingDefects, $standardDefects);

        return $results;
    }

    /**
     * 更新标准分数
     *
     * @param array<string, mixed> $standardResult
     * @param array<string, mixed> $results
     * @return array<string, mixed>
     */
    private function updateStandardScores(QualityStandard $standard, array $standardResult, array $results): array
    {
        $weight = $standard->getPriority();
        $score = is_numeric($standardResult['score'] ?? null) ? (float) $standardResult['score'] : 0.0;
        $totalScore = is_numeric($results['total_score'] ?? null) ? (float) $results['total_score'] : 0.0;
        $totalWeight = is_numeric($results['total_weight'] ?? null) ? (int) $results['total_weight'] : 0;

        $results['total_score'] = $totalScore + ($score * $weight);
        $results['total_weight'] = $totalWeight + $weight;

        return $results;
    }

    /**
     * 更新总体结果状态
     *
     * @param array<string, mixed> $standardResult
     * @param array<string, mixed> $results
     * @return array<string, mixed>
     */
    private function updateOverallResultStatus(array $standardResult, array $results): array
    {
        $standardResultStr = is_string($standardResult['result'] ?? null) ? $standardResult['result'] : 'fail';
        $overallResultStr = is_string($results['overall_result'] ?? null) ? $results['overall_result'] : 'pass';
        $results['overall_result'] = $this->updateOverallResult($standardResultStr, $overallResultStr);

        return $results;
    }

    /**
     * 执行标准检查
     *
     * @param array<string, mixed> $checkData
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    private function executeStandardCheck(QualityStandard $standard, array $checkData, array $options): array
    {
        $results = $this->initializeStandardCheckResults($standard);
        $checkItems = $standard->getCheckItems();

        $processedResults = $this->processAllCheckItems($checkItems, $checkData, $options, $results);
        $processedResults['score'] = $this->calculateFinalScore($processedResults);

        return $processedResults;
    }

    /**
     * 处理所有检查项
     *
     * @param array<string, mixed> $checkItems
     * @param array<string, mixed> $checkData
     * @param array<string, mixed> $options
     * @param array<string, mixed> $results
     * @return array<string, mixed>
     */
    private function processAllCheckItems(array $checkItems, array $checkData, array $options, array $results): array
    {
        foreach ($checkItems as $checkKey => $checkConfig) {
            if (!$this->isValidCheckItem($checkKey, $checkConfig)) {
                continue;
            }

            // 确保 $checkConfig 是正确的数组类型
            /** @var array<string, mixed> $checkConfigArray */
            $checkConfigArray = $checkConfig;

            if (!$this->shouldProcessCheckItem($checkConfigArray, $options)) {
                continue;
            }

            $strictMode = (bool) ($options['strict_mode'] ?? true);
            $results = $this->processCheckItem($checkKey, $checkConfigArray, $checkData, $strictMode, $results);
        }

        return $results;
    }

    /**
     * 验证检查项是否有效
     *
     * @param mixed $checkKey
     * @param mixed $checkConfig
     */
    private function isValidCheckItem(mixed $checkKey, mixed $checkConfig): bool
    {
        return is_string($checkKey) && is_array($checkConfig);
    }

    /**
     * 初始化标准检查结果
     *
     * @return array<string, mixed>
     */
    private function initializeStandardCheckResults(QualityStandard $standard): array
    {
        return [
            'standard_id' => $standard->getId(),
            'standard_name' => $standard->getName(),
            'result' => 'pass',
            'score' => 0,
            'check_results' => [],
            'defects' => [],
            'total_score' => 0,
            'total_weight' => 0,
        ];
    }

    /**
     * 检查是否应该处理检查项
     *
     * @param array<string, mixed> $checkConfig
     * @param array<string, mixed> $options
     */
    private function shouldProcessCheckItem(array $checkConfig, array $options): bool
    {
        if (!(bool) ($checkConfig['enabled'] ?? true)) {
            return false;
        }

        return !((bool) ($options['skip_optional'] ?? false) && !(bool) ($checkConfig['required'] ?? false));
    }

    /**
     * 处理单个检查项
     *
     * @param array<string, mixed> $checkConfig
     * @param array<string, mixed> $checkData
     * @param array<string, mixed> $results
     * @return array<string, mixed>
     */
    private function processCheckItem(string $checkKey, array $checkConfig, array $checkData, bool $strictMode, array $results): array
    {
        $checkResult = $this->executeIndividualCheck($checkKey, $checkConfig, $checkData, $strictMode);
        if (!is_array($results['check_results'] ?? null)) {
            $results['check_results'] = [];
        }
        $results['check_results'][$checkKey] = $checkResult;

        $results = $this->mergeCheckItemDefects($checkResult, $results);
        $results = $this->updateCheckItemScores($checkConfig, $checkResult, $results);

        return $this->updateCheckItemResult($checkResult, $results);
    }

    /**
     * 合并检查项缺陷
     *
     * @param array<string, mixed> $checkResult
     * @param array<string, mixed> $results
     * @return array<string, mixed>
     */
    private function mergeCheckItemDefects(array $checkResult, array $results): array
    {
        $checkDefects = is_array($checkResult['defects'] ?? null) ? $checkResult['defects'] : [];
        if (count($checkDefects) > 0) {
            $existingDefects = is_array($results['defects'] ?? null) ? $results['defects'] : [];
            $results['defects'] = array_merge($existingDefects, $checkDefects);
        }

        return $results;
    }

    /**
     * 更新检查项分数
     *
     * @param array<string, mixed> $checkConfig
     * @param array<string, mixed> $checkResult
     * @param array<string, mixed> $results
     * @return array<string, mixed>
     */
    private function updateCheckItemScores(array $checkConfig, array $checkResult, array $results): array
    {
        $weight = is_numeric($checkConfig['weight'] ?? null) ? (float) ($checkConfig['weight']) : 1.0;
        $score = is_numeric($checkResult['score'] ?? null) ? (float) $checkResult['score'] : 0.0;
        $totalScore = is_numeric($results['total_score'] ?? null) ? (float) $results['total_score'] : 0.0;
        $totalWeight = is_numeric($results['total_weight'] ?? null) ? (float) $results['total_weight'] : 0.0;

        $results['total_score'] = $totalScore + ($score * $weight);
        $results['total_weight'] = $totalWeight + $weight;

        return $results;
    }

    /**
     * 更新检查项结果
     *
     * @param array<string, mixed> $checkResult
     * @param array<string, mixed> $results
     * @return array<string, mixed>
     */
    private function updateCheckItemResult(array $checkResult, array $results): array
    {
        $checkResultStr = is_string($checkResult['result'] ?? null) ? $checkResult['result'] : 'fail';
        $standardResultStr = is_string($results['result'] ?? null) ? $results['result'] : 'pass';
        $results['result'] = $this->updateStandardResult($checkResultStr, $standardResultStr);

        return $results;
    }

    /**
     * 执行单项检查
     *
     * @param array<string, mixed> $checkConfig
     * @param array<string, mixed> $checkData
     * @return array<string, mixed>
     */
    private function executeIndividualCheck(string $checkKey, array $checkConfig, array $checkData, bool $strictMode): array
    {
        $checkValue = $checkData[$checkKey] ?? null;

        if (null === $checkValue) {
            return $this->createMissingDataResult($checkKey);
        }

        /** @var array<string, mixed> $criteria */
        $criteria = is_array($checkConfig['criteria'] ?? null) ? $checkConfig['criteria'] : [];
        $validationResult = $this->performValidation($checkKey, $checkValue, $criteria, $strictMode);

        return $this->buildCheckResult($validationResult);
    }

    /**
     * 执行验证
     *
     * @param array<string, mixed> $criteria
     * @return array<string, mixed>
     */
    private function performValidation(string $checkKey, mixed $checkValue, array $criteria, bool $strictMode): array
    {
        $validator = $this->validatorRegistry->getValidator($checkKey);

        return $validator->validate($checkValue, $criteria, $strictMode);
    }

    /**
     * @param array<string, mixed> $results
     */
    private function calculateFinalScore(array $results): float
    {
        $totalWeight = is_numeric($results['total_weight'] ?? null) ? (float) $results['total_weight'] : 0.0;
        $totalScore = is_numeric($results['total_score'] ?? null) ? (float) $results['total_score'] : 0.0;

        return $totalWeight > 0
            ? round($totalScore / $totalWeight, 2)
            : 0;
    }

    /**
     * @return array<string, mixed>
     */
    private function createMissingDataResult(string $checkKey): array
    {
        return [
            'result' => 'fail',
            'score' => 0,
            'defects' => [['type' => $checkKey, 'description' => '缺少检查数据']],
            'details' => ['missing_data' => true],
        ];
    }

    /**
     * @param array<string, mixed> $validationResult
     * @return array<string, mixed>
     */
    private function buildCheckResult(array $validationResult): array
    {
        $defects = $validationResult['defects'] ?? null;
        $defectsCount = is_array($defects) || $defects instanceof \Countable ? count($defects) : 0;

        if (0 === $defectsCount) {
            return $this->buildPassCheckResult($validationResult);
        }

        return $this->buildFailedCheckResult($validationResult);
    }

    /**
     * 构建通过的检查结果
     *
     * @param array<string, mixed> $validationResult
     * @return array<string, mixed>
     */
    private function buildPassCheckResult(array $validationResult): array
    {
        return [
            'result' => 'pass',
            'score' => 100,
            'defects' => [],
            'details' => $validationResult['details'] ?? [],
        ];
    }

    /**
     * 构建失败的检查结果
     *
     * @param array<string, mixed> $validationResult
     * @return array<string, mixed>
     */
    private function buildFailedCheckResult(array $validationResult): array
    {
        $defects = $validationResult['defects'] ?? null;
        $defectsCount = is_array($defects) || $defects instanceof \Countable ? count($defects) : 0;
        $score = max(0, 100 - $defectsCount * 20);
        $result = $this->hasCriticalDefects($validationResult) ? 'fail' : 'conditional';

        return [
            'result' => $result,
            'score' => $score,
            'defects' => $validationResult['defects'],
            'details' => $validationResult['details'] ?? [],
        ];
    }

    /**
     * @param array<string, mixed> $validationResult
     */
    private function hasCriticalDefects(array $validationResult): bool
    {
        $defects = is_array($validationResult['defects'] ?? null) ? $validationResult['defects'] : [];
        if (0 === count($defects)) {
            return false;
        }

        foreach ($defects as $defect) {
            if (is_array($defect) && true === ($defect['critical'] ?? false)) {
                return true;
            }
        }

        return false;
    }

    private function updateOverallResult(string $standardResult, string $overallResult): string
    {
        if ('fail' === $standardResult) {
            return 'fail';
        }
        if ('conditional' === $standardResult && 'pass' === $overallResult) {
            return 'conditional';
        }

        return $overallResult;
    }

    private function updateStandardResult(string $checkResult, string $standardResult): string
    {
        if ('fail' === $checkResult) {
            return 'fail';
        }
        if ('conditional' === $checkResult && 'pass' === $standardResult) {
            return 'conditional';
        }

        return $standardResult;
    }
}
