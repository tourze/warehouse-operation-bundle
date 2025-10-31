<?php

declare(strict_types=1);

namespace Tourze\WarehouseOperationBundle\Service;

use Tourze\WarehouseOperationBundle\Entity\QualityStandard;
use Tourze\WarehouseOperationBundle\Entity\QualityTask;
use Tourze\WarehouseOperationBundle\Repository\WarehouseTaskRepository;
use Tourze\WarehouseOperationBundle\Service\Quality\QualityCheckExecutorService;
use Tourze\WarehouseOperationBundle\Service\Quality\QualityEscalationService;
use Tourze\WarehouseOperationBundle\Service\Quality\QualityFailureHandlerService;
use Tourze\WarehouseOperationBundle\Service\Quality\QualityReportService;
use Tourze\WarehouseOperationBundle\Service\Quality\QualitySampleInspectionService;
use Tourze\WarehouseOperationBundle\Service\Quality\QualityStandardValidationService;
use Tourze\WarehouseOperationBundle\Service\Quality\QualityStatisticsService;

/**
 * 质检控制服务实现
 *
 * 重构后的主服务类，协调各个子服务完成质检业务逻辑。
 * 通过依赖注入使用专门的子服务，降低认知复杂度。
 */
class QualityControlService implements QualityControlServiceInterface
{
    private QualityCheckExecutorService $checkExecutorService;

    private QualityFailureHandlerService $failureHandlerService;

    private QualityReportService $reportService;

    private QualityStatisticsService $statisticsService;

    private QualityStandardValidationService $validationService;

    private QualitySampleInspectionService $sampleInspectionService;

    private QualityEscalationService $escalationService;

    public function __construct(
        WarehouseTaskRepository $qualityTaskRepository,
        QualityCheckExecutorService $checkExecutorService,
        QualityFailureHandlerService $failureHandlerService,
        ?QualityReportService $reportService = null,
        ?QualityStatisticsService $statisticsService = null,
        ?QualityStandardValidationService $validationService = null,
        ?QualitySampleInspectionService $sampleInspectionService = null,
        ?QualityEscalationService $escalationService = null,
    ) {
        $this->checkExecutorService = $checkExecutorService;
        $this->failureHandlerService = $failureHandlerService;
        $this->reportService = $reportService ?? new QualityReportService($qualityTaskRepository);
        $this->statisticsService = $statisticsService ?? new QualityStatisticsService($qualityTaskRepository);
        $this->validationService = $validationService ?? new QualityStandardValidationService();
        $this->sampleInspectionService = $sampleInspectionService ?? new QualitySampleInspectionService();
        $this->escalationService = $escalationService ?? new QualityEscalationService($qualityTaskRepository);
    }

    /**
     * 执行质检流程
     */
    public function performQualityCheck(QualityTask $task, array $checkData, array $options = []): array
    {
        return $this->checkExecutorService->performQualityCheck($task, $checkData, $options);
    }

    /**
     * 处理质检失败商品
     */
    public function handleQualityFailure(QualityTask $task, string $failureReason, array $failureDetails = [], array $handlingOptions = []): array
    {
        return $this->failureHandlerService->handleQualityFailure($task, $failureReason, $failureDetails, $handlingOptions);
    }

    /**
     * 获取适用的质检标准
     */
    public function getApplicableStandards(array $productAttributes, array $context = []): array
    {
        return $this->checkExecutorService->getApplicableStandards($productAttributes, $context);
    }

    /**
     * 验证质检标准配置
     */
    public function validateQualityStandard(QualityStandard $standard, array $validationContext = []): array
    {
        return $this->validationService->validateQualityStandard($standard, $validationContext);
    }

    /**
     * 生成质检报告
     */
    public function generateQualityReport(array $taskIds, array $reportOptions = []): array
    {
        return $this->reportService->generateQualityReport($taskIds, $reportOptions);
    }

    /**
     * 质检数据统计分析
     */
    public function analyzeQualityStatistics(array $analysisParams = []): array
    {
        return $this->statisticsService->analyzeQualityStatistics($analysisParams);
    }

    /**
     * 执行质检样品抽检
     */
    public function executeSampleInspection(array $batchInfo, array $samplingRules): array
    {
        return $this->sampleInspectionService->executeSampleInspection($batchInfo, $samplingRules);
    }

    /**
     * 处理质检异常升级
     */
    public function escalateQualityIssue(QualityTask $task, array $escalationReason): array
    {
        return $this->escalationService->escalateQualityIssue($task, $escalationReason);
    }
}
