<?php

declare(strict_types=1);

namespace Tourze\WarehouseOperationBundle\Service\Quality;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Tourze\WarehouseOperationBundle\Entity\QualityStandard;
use Tourze\WarehouseOperationBundle\Entity\QualityTask;
use Tourze\WarehouseOperationBundle\Enum\TaskStatus;
use Tourze\WarehouseOperationBundle\Event\QualityFailedEvent;
use Tourze\WarehouseOperationBundle\Repository\QualityStandardRepository;
use Tourze\WarehouseOperationBundle\Repository\WarehouseTaskRepository;
use Tourze\WarehouseOperationBundle\Service\Quality\Processor\QualityResultBuilder;
use Tourze\WarehouseOperationBundle\Service\Quality\Processor\QualityStandardsProcessor;
use Tourze\WarehouseOperationBundle\Service\Quality\Validator\QualityCheckValidatorRegistry;
use Tourze\WarehouseOperationBundle\Service\Quality\Validator\QualityCheckValidatorRegistryInterface;

/**
 * 质检执行服务
 *
 * 专门负责质检流程的执行逻辑，包括标准匹配、检查执行和结果评估。
 * 使用策略模式处理不同类型的质检项目。
 */
final class QualityCheckExecutorService
{
    private EventDispatcherInterface $eventDispatcher;

    private QualityStandardRepository $qualityStandardRepository;

    private WarehouseTaskRepository $qualityTaskRepository;

    private QualityCheckValidatorRegistryInterface $validatorRegistry;

    private QualityStandardsProcessor $standardsProcessor;

    private QualityResultBuilder $resultBuilder;

    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        QualityStandardRepository $qualityStandardRepository,
        WarehouseTaskRepository $qualityTaskRepository,
        ?QualityCheckValidatorRegistryInterface $validatorRegistry = null,
        ?QualityStandardsProcessor $standardsProcessor = null,
        ?QualityResultBuilder $resultBuilder = null,
    ) {
        $this->eventDispatcher = $eventDispatcher;
        $this->qualityStandardRepository = $qualityStandardRepository;
        $this->qualityTaskRepository = $qualityTaskRepository;
        $this->validatorRegistry = $validatorRegistry ?? new QualityCheckValidatorRegistry();
        $this->standardsProcessor = $standardsProcessor ?? new QualityStandardsProcessor($this->validatorRegistry);
        $this->resultBuilder = $resultBuilder ?? new QualityResultBuilder();
    }

    /**
     * 执行质检流程
     *
     * @param array<string, mixed> $checkData
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    public function performQualityCheck(QualityTask $task, array $checkData, array $options = []): array
    {
        $checkOptions = $this->extractCheckOptions($options);
        $productInfo = $this->extractProductInfo($checkData);

        $standards = $this->getApplicableStandards($productInfo);
        if (0 === count($standards)) {
            return $this->createNoStandardsResult();
        }

        $qualityResult = $this->standardsProcessor->processStandards($standards, $checkData, $checkOptions);
        $inspectorIdRaw = $checkOptions['inspector_id'] ?? null;
        // 确保 $inspectorId 是 int|null 类型，添加严格的类型检查
        $inspectorId = null === $inspectorIdRaw ? null : (is_int($inspectorIdRaw) ? $inspectorIdRaw : (is_numeric($inspectorIdRaw) ? (int) $inspectorIdRaw : null));
        $resultData = $this->resultBuilder->buildResultData($qualityResult, $checkData, $inspectorId);

        $this->saveTaskResult($task, $resultData);

        return $resultData;
    }

    /**
     * 提取商品信息
     *
     * @param array<string, mixed> $checkData
     * @return array<string, mixed>
     */
    private function extractProductInfo(array $checkData): array
    {
        $productInfo = $checkData['product_info'] ?? [];

        /** @var array<string, mixed> */
        return is_array($productInfo) ? $productInfo : [];
    }

    /**
     * 保存任务结果
     *
     * @param array<string, mixed> $resultData
     */
    private function saveTaskResult(QualityTask $task, array $resultData): void
    {
        $this->updateTaskWithResult($task, $resultData);
        $this->qualityTaskRepository->save($task);
    }

    /**
     * 获取适用的质检标准
     *
     * @param array<string, mixed> $productAttributes
     * @param array<string, mixed> $context
     * @return QualityStandard[]
     */
    public function getApplicableStandards(array $productAttributes, array $context = []): array
    {
        $productCategory = $productAttributes['category'] ?? $productAttributes['product_type'] ?? '';

        if ('' === $productCategory || !is_string($productCategory)) {
            return [];
        }

        $standards = $this->qualityStandardRepository->findByProductCategory($productCategory);

        // 根据特殊属性进一步筛选
        if (isset($productAttributes['special_attributes']) && is_array($productAttributes['special_attributes'])) {
            /** @var array<string> $specialAttrs */
            $specialAttrs = array_values(array_filter($productAttributes['special_attributes'], fn ($v): bool => is_string($v)));
            $standards = $this->filterStandardsBySpecialAttributes($standards, $specialAttrs);
        }

        // 按优先级排序
        usort($standards, fn (QualityStandard $a, QualityStandard $b) => $b->getPriority() <=> $a->getPriority());

        return $standards;
    }

    /**
     * 提取检查选项
     *
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    private function extractCheckOptions(array $options): array
    {
        return [
            'strict_mode' => $options['strict_mode'] ?? true,
            'skip_optional' => $options['skip_optional'] ?? false,
            'inspector_id' => $options['inspector_id'] ?? null,
        ];
    }

    /**
     * 创建无标准结果
     *
     * @return array<string, mixed>
     */
    private function createNoStandardsResult(): array
    {
        return [
            'overall_result' => 'fail',
            'quality_score' => 0,
            'defects' => [['type' => 'no_standards', 'description' => '未找到适用的质检标准']],
            'check_results' => [],
            'recommendations' => ['请配置对应商品类别的质检标准'],
            'inspector_notes' => '',
            'photos' => [],
            'checked_at' => new \DateTimeImmutable(),
        ];
    }

    /**
     * 更新任务结果
     *
     * @param array<string, mixed> $resultData
     */
    private function updateTaskWithResult(QualityTask $task, array $resultData): void
    {
        $task->setData(array_merge($task->getData(), ['quality_result' => $resultData]));

        if ($this->shouldMarkTaskAsFailed($resultData)) {
            $this->markTaskAsFailed($task, $resultData);
        } else {
            $task->setStatus(TaskStatus::COMPLETED);
        }
    }

    /**
     * 检查是否应该标记任务为失败
     *
     * @param array<string, mixed> $resultData
     */
    private function shouldMarkTaskAsFailed(array $resultData): bool
    {
        if ('fail' === $resultData['overall_result']) {
            return true;
        }

        return $this->hasIsolationRequiredDefects($resultData);
    }

    /**
     * 检查是否有需要隔离的缺陷
     *
     * @param array<string, mixed> $resultData
     */
    private function hasIsolationRequiredDefects(array $resultData): bool
    {
        $defects = is_array($resultData['defects'] ?? null) ? $resultData['defects'] : [];
        if (0 === count($defects)) {
            return false;
        }

        /** @var array<array<string, mixed>> $typedDefects */
        $typedDefects = array_filter($defects, fn ($d): bool => is_array($d));

        return $this->requiresIsolation($typedDefects);
    }

    /**
     * 标记任务为失败
     *
     * @param array<string, mixed> $resultData
     */
    private function markTaskAsFailed(QualityTask $task, array $resultData): void
    {
        $task->setStatus(TaskStatus::FAILED);

        /** @var array<array<string, mixed>> $typedDefects */
        $typedDefects = $this->extractTypedDefects($resultData);

        $this->dispatchQualityFailedEvent($task, $resultData, $typedDefects);
    }

    /**
     * 提取类型化的缺陷
     *
     * @param array<string, mixed> $resultData
     * @return array<array<string, mixed>>
     */
    private function extractTypedDefects(array $resultData): array
    {
        $defects = $resultData['defects'] ?? [];
        if (!is_array($defects)) {
            return [];
        }

        /** @var array<array<string, mixed>> */
        return array_filter($defects, fn ($d): bool => is_array($d));
    }

    /**
     * 派发质量失败事件
     *
     * @param array<string, mixed> $resultData
     * @param array<array<string, mixed>> $typedDefects
     */
    private function dispatchQualityFailedEvent(QualityTask $task, array $resultData, array $typedDefects): void
    {
        $this->eventDispatcher->dispatch(
            new QualityFailedEvent(
                $task,
                'Quality check failed',
                [
                    'type' => 'quality_check',
                    'severity' => $this->calculateSeverity($typedDefects),
                    'defects' => $typedDefects,
                    'score' => $resultData['quality_score'],
                    'requires_isolation' => $this->requiresIsolation($typedDefects),
                ]
            )
        );
    }

    /**
     * 根据特殊属性筛选标准
     *
     * @param QualityStandard[] $standards
     * @param array<string> $specialAttrs
     * @return QualityStandard[]
     */
    private function filterStandardsBySpecialAttributes(array $standards, array $specialAttrs): array
    {
        return array_filter($standards, fn (QualityStandard $standard) => $this->standardMatchesSpecialAttributes($standard, $specialAttrs));
    }

    /**
     * 检查标准是否匹配特殊属性
     *
     * @param array<string> $specialAttrs
     */
    private function standardMatchesSpecialAttributes(QualityStandard $standard, array $specialAttrs): bool
    {
        if (0 === count($specialAttrs)) {
            return true;
        }

        $checkItems = $standard->getCheckItems();

        foreach ($specialAttrs as $attr) {
            if ($this->hasEnabledCheckItem($checkItems, $attr)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 检查是否有启用的检查项
     *
     * @param array<string, mixed> $checkItems
     */
    private function hasEnabledCheckItem(array $checkItems, string $attr): bool
    {
        $checkKey = $attr . '_check';

        if (!isset($checkItems[$checkKey])) {
            return false;
        }

        $checkItem = $checkItems[$checkKey];
        if (!is_array($checkItem)) {
            return false;
        }

        return (bool) ($checkItem['enabled'] ?? false);
    }

    /**
     * @param array<array<string, mixed>> $defects
     */
    private function calculateSeverity(array $defects): string
    {
        $criticalCount = count(array_filter($defects, fn (array $d): bool => (bool) ($d['critical'] ?? false)));
        $totalCount = count($defects);

        if ($criticalCount > 0) {
            return 'critical';
        }

        if ($totalCount > 3) {
            return 'high';
        }

        if ($totalCount > 1) {
            return 'medium';
        }

        return 'low';
    }

    /**
     * @param array<array<string, mixed>> $defects
     */
    private function requiresIsolation(array $defects): bool
    {
        $isolationTypes = ['expired', 'damage', 'contamination', 'safety_issue'];

        foreach ($defects as $defect) {
            if (in_array($defect['type'], $isolationTypes, true)) {
                return true;
            }

            if (true === ($defect['critical'] ?? false)) {
                return true;
            }
        }

        return false;
    }
}
