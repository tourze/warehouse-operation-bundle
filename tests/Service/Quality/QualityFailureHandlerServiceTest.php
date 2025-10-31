<?php

declare(strict_types=1);

namespace Tourze\WarehouseOperationBundle\Tests\Service\Quality;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\WarehouseOperationBundle\Entity\QualityTask;
use Tourze\WarehouseOperationBundle\Enum\TaskType;
use Tourze\WarehouseOperationBundle\Service\Quality\QualityFailureHandlerService;

/**
 * QualityFailureHandlerService 单元测试
 *
 * 测试质检失败处理服务的完整功能，包括失败处理、隔离决策、成本估算等核心业务逻辑。
 * 验证服务的正确性、处理策略和异常处理。
 * @internal
 */
#[CoversClass(QualityFailureHandlerService::class)]
#[RunTestsInSeparateProcesses]
class QualityFailureHandlerServiceTest extends AbstractIntegrationTestCase
{
    private QualityFailureHandlerService $service;

    protected function onSetUp(): void
    {
        $this->service = parent::getService(QualityFailureHandlerService::class);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Quality\QualityFailureHandlerService::handleQualityFailure
     */
    public function testHandleQualityFailureWithCriticalSeverity(): void
    {
        $task = new QualityTask();
        $task->setTaskName('严重质检失败任务');
        $task->setType(TaskType::QUALITY);
        $task->setData([]);

        $failureReason = '严重质检失败';
        $failureDetails = [
            'failure_type' => 'damage',
            'severity_level' => 'critical',
            'affected_quantity' => 50,
        ];

        $handlingOptions = [
            'auto_isolate' => true,
            'create_claim' => true,
        ];

        // 执行测试
        $result = $this->service->handleQualityFailure($task, $failureReason, $failureDetails, $handlingOptions);

        // 验证处理结果
        $this->assertArrayHasKey('handling_actions', $result);
        $this->assertArrayHasKey('isolation_location', $result);
        $this->assertArrayHasKey('follow_up_tasks', $result);
        $this->assertArrayHasKey('cost_estimation', $result);
        $this->assertArrayHasKey('timeline', $result);

        // 验证隔离动作
        $this->assertNotNull($result['isolation_location']);
        $this->assertIsString($result['isolation_location']);
        $this->assertStringContainsString('QUARANTINE_DAMAGED', $result['isolation_location']);
        $this->assertStringContainsString('_CRITICAL', $result['isolation_location']);

        // 验证隔离动作存在
        $this->assertIsArray($result['handling_actions']);
        $isolateAction = null;
        foreach ($result['handling_actions'] as $action) {
            $this->assertIsArray($action);
            if ('isolate' === $action['action']) {
                $isolateAction = $action;
                break;
            }
        }
        $this->assertNotNull($isolateAction);
        $this->assertIsArray($isolateAction);
        $this->assertEquals(50, $isolateAction['quantity']);

        // 验证严重问题的紧急停止动作
        $emergencyAction = null;
        foreach ($result['handling_actions'] as $action) {
            $this->assertIsArray($action);
            if ('immediate_stop' === $action['action']) {
                $emergencyAction = $action;
                break;
            }
        }
        $this->assertNotNull($emergencyAction);
        $this->assertIsArray($emergencyAction);
        $this->assertEquals('Critical quality issue', $emergencyAction['reason']);

        // 验证后续任务
        $this->assertIsArray($result['follow_up_tasks']);
        $this->assertNotEmpty($result['follow_up_tasks']);
        $hasEmergencyReview = false;
        $hasCreateClaim = false;
        foreach ($result['follow_up_tasks'] as $followUpTask) {
            $this->assertIsArray($followUpTask);
            if ('emergency_review' === $followUpTask['type']) {
                $hasEmergencyReview = true;
                $this->assertEquals(100, $followUpTask['priority']);
            }
            if ('create_claim' === $followUpTask['type']) {
                $hasCreateClaim = true;
                $this->assertTrue($followUpTask['supplier_notify']);
            }
        }
        $this->assertTrue($hasEmergencyReview);
        $this->assertTrue($hasCreateClaim);

        // 验证成本估算
        $cost = $result['cost_estimation'];
        $this->assertIsArray($cost);
        $this->assertArrayHasKey('cost', $cost);
        $this->assertArrayHasKey('currency', $cost);
        $this->assertArrayHasKey('breakdown', $cost);
        $this->assertEquals('CNY', $cost['currency']);
        $this->assertGreaterThan(0, $cost['cost']);

        // 验证时间线
        $this->assertIsArray($result['timeline']);
        $timeline = $result['timeline'];
        $this->assertNotEmpty($timeline);
        $this->assertIsArray($timeline[0]);
        $this->assertArrayHasKey('scheduled_time', $timeline[0]);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Quality\QualityFailureHandlerService::handleQualityFailure
     */
    public function testHandleQualityFailureWithHighSeverity(): void
    {
        $task = new QualityTask();
        $task->setTaskName('高严重性质检失败');
        $task->setType(TaskType::QUALITY);
        $task->setData([]);

        $failureDetails = [
            'failure_type' => 'expiry',
            'severity_level' => 'high',
            'affected_quantity' => 20,
        ];

        $handlingOptions = [
            'notify_supplier' => true,
        ];

        $result = $this->service->handleQualityFailure($task, '高严重性失败', $failureDetails, $handlingOptions);

        // 验证隔离位置为过期商品隔离区
        $this->assertIsString($result['isolation_location']);
        $this->assertStringContainsString('QUARANTINE_EXPIRED', $result['isolation_location']);
        $this->assertStringContainsString('_HIGH', $result['isolation_location']);

        // 验证后续任务包含供应商通知
        $this->assertIsArray($result['follow_up_tasks']);
        $hasSupplierNotification = false;
        foreach ($result['follow_up_tasks'] as $followUpTask) {
            $this->assertIsArray($followUpTask);
            if ('supplier_notification' === $followUpTask['type']) {
                $hasSupplierNotification = true;
                break;
            }
        }
        $this->assertTrue($hasSupplierNotification);

        // 验证成本计算（高严重性应该有3倍乘数）
        $this->assertIsArray($result['cost_estimation']);
        $cost = $result['cost_estimation'];
        $expectedBaseCost = (10 + 30) * 3 * 20; // (base + type) * multiplier * quantity
        $this->assertEquals($expectedBaseCost, $cost['cost']);
        $this->assertIsArray($cost['breakdown']);
        $this->assertEquals(3, $cost['breakdown']['severity_multiplier']);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Quality\QualityFailureHandlerService::handleQualityFailure
     */
    public function testHandleQualityFailureWithMediumSeverity(): void
    {
        $task = new QualityTask();
        $task->setTaskName('中等严重性质检失败');
        $task->setType(TaskType::QUALITY);
        $task->setData([]);

        $failureDetails = [
            'failure_type' => 'contamination',
            'severity_level' => 'medium',
            'affected_quantity' => 10,
        ];

        $result = $this->service->handleQualityFailure($task, '中等严重性失败', $failureDetails);

        // 验证隔离位置
        $this->assertArrayHasKey('isolation_location', $result);
        $this->assertNotNull($result['isolation_location']);
        $this->assertIsString($result['isolation_location']);
        $this->assertStringContainsString('QUARANTINE_CONTAMINATED', $result['isolation_location']);
        $this->assertStringContainsString('_MEDIUM', $result['isolation_location']);

        // 验证后续任务包含返工评估
        $this->assertIsArray($result['follow_up_tasks']);
        $hasReworkEvaluation = false;
        foreach ($result['follow_up_tasks'] as $followUpTask) {
            $this->assertIsArray($followUpTask);
            if ('rework_evaluation' === $followUpTask['type']) {
                $hasReworkEvaluation = true;
                $this->assertEquals(50, $followUpTask['priority']);
                break;
            }
        }
        $this->assertTrue($hasReworkEvaluation);

        // 验证成本计算（contamination类型成本更高）
        $this->assertIsArray($result['cost_estimation']);
        $cost = $result['cost_estimation'];
        $expectedBaseCost = (10 + 100) * 2 * 10; // 污染类型成本100
        $this->assertEquals($expectedBaseCost, $cost['cost']);
        $this->assertIsArray($cost['breakdown']);
        $this->assertEquals(100, $cost['breakdown']['type_cost']);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Quality\QualityFailureHandlerService::handleQualityFailure
     */
    public function testHandleQualityFailureWithLowSeverity(): void
    {
        $task = new QualityTask();
        $task->setTaskName('低严重性质检失败');
        $task->setType(TaskType::QUALITY);
        $task->setData([]);

        $failureDetails = [
            'failure_type' => 'appearance',
            'severity_level' => 'low',
            'affected_quantity' => 5,
        ];

        $result = $this->service->handleQualityFailure($task, '低严重性失败', $failureDetails);

        // 验证隔离位置
        $this->assertIsString($result['isolation_location']);
        $this->assertStringContainsString('QUARANTINE_GENERAL', $result['isolation_location']);
        $this->assertStringContainsString('_LOW', $result['isolation_location']);

        // 验证记录动作存在
        $this->assertIsArray($result['handling_actions']);
        $hasDocumentAction = false;
        foreach ($result['handling_actions'] as $action) {
            $this->assertIsArray($action);
            if ('document' === $action['action']) {
                $hasDocumentAction = true;
                $this->assertEquals('minor_defect', $action['type']);
                break;
            }
        }
        $this->assertTrue($hasDocumentAction);

        // 验证没有后续任务（低严重性）
        $this->assertIsArray($result['follow_up_tasks']);
        $this->assertEmpty($result['follow_up_tasks']);

        // 验证成本最低
        $this->assertIsArray($result['cost_estimation']);
        $cost = $result['cost_estimation'];
        $this->assertIsArray($cost['breakdown']);
        $this->assertEquals(1, $cost['breakdown']['severity_multiplier']);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Quality\QualityFailureHandlerService::handleQualityFailure
     */
    public function testHandleQualityFailureWithNoAutoIsolation(): void
    {
        $task = new QualityTask();
        $task->setTaskName('不自动隔离测试');
        $task->setType(TaskType::QUALITY);
        $task->setData([]);

        $failureDetails = [
            'failure_type' => 'damage',
            'severity_level' => 'medium',
            'affected_quantity' => 15,
        ];

        $handlingOptions = [
            'auto_isolate' => false, // 禁用自动隔离
        ];

        $result = $this->service->handleQualityFailure($task, '不自动隔离', $failureDetails, $handlingOptions);

        // 验证没有隔离位置
        $this->assertNull($result['isolation_location']);

        // 验证没有隔离动作
        $this->assertIsArray($result['handling_actions']);
        $hasIsolateAction = false;
        foreach ($result['handling_actions'] as $action) {
            $this->assertIsArray($action);
            if ('isolate' === $action['action']) {
                $hasIsolateAction = true;
                break;
            }
        }
        $this->assertFalse($hasIsolateAction);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Quality\QualityFailureHandlerService::handleQualityFailure
     */
    public function testHandleQualityFailureWithUnknownFailureType(): void
    {
        $task = new QualityTask();
        $task->setTaskName('未知失败类型测试');
        $task->setType(TaskType::QUALITY);
        $task->setData([]);

        $failureDetails = [
            'failure_type' => 'unknown_type',
            'severity_level' => 'medium',
            'affected_quantity' => 8,
        ];

        $result = $this->service->handleQualityFailure($task, '未知失败类型', $failureDetails);

        // 验证使用通用隔离位置
        $this->assertArrayHasKey('isolation_location', $result);
        $this->assertNotNull($result['isolation_location']);
        $this->assertIsString($result['isolation_location']);
        $this->assertStringContainsString('QUARANTINE_GENERAL', $result['isolation_location']);

        // 验证使用默认类型成本
        $this->assertIsArray($result['cost_estimation']);
        $cost = $result['cost_estimation'];
        $this->assertIsArray($cost['breakdown']);
        $this->assertEquals(20, $cost['breakdown']['type_cost']); // 默认类型成本
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Quality\QualityFailureHandlerService::handleQualityFailure
     */
    public function testHandleQualityFailureWithDefaultValues(): void
    {
        $task = new QualityTask();
        $task->setTaskName('默认值测试');
        $task->setType(TaskType::QUALITY);
        $task->setData([]);

        // 使用最少的参数
        $result = $this->service->handleQualityFailure($task, '默认值测试');

        // 验证使用默认值
        $this->assertIsArray($result['cost_estimation']);
        $cost = $result['cost_estimation'];
        $this->assertIsArray($cost['breakdown']);
        $this->assertEquals('medium', 2 === $cost['breakdown']['severity_multiplier'] ? 'medium' : 'other');
        $this->assertEquals(1, $cost['breakdown']['quantity']); // 默认数量

        // 验证时间线存在
        $this->assertIsArray($result['timeline']);
        $this->assertNotEmpty($result['timeline']);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Quality\QualityFailureHandlerService::handleQualityFailure
     */
    public function testHandleQualityFailureTaskDataUpdate(): void
    {
        $task = new QualityTask();
        $task->setTaskName('任务数据更新测试');
        $task->setType(TaskType::QUALITY);
        $task->setData(['existing_data' => 'value']);

        $failureDetails = [
            'failure_type' => 'damage',
            'severity_level' => 'high',
            'affected_quantity' => 25,
        ];

        $this->service->handleQualityFailure($task, '任务数据更新', $failureDetails);

        // 验证任务数据包含失败处理信息
        $taskData = $task->getData();
        $this->assertIsArray($taskData);
        $this->assertArrayHasKey('failure_handling', $taskData);
        $this->assertArrayHasKey('existing_data', $taskData); // 原有数据保持

        $failureHandling = $taskData['failure_handling'];
        $this->assertIsArray($failureHandling);
        $this->assertArrayHasKey('failure_handled_at', $failureHandling);
        $this->assertArrayHasKey('handling_actions', $failureHandling);
        $this->assertArrayHasKey('isolation_location', $failureHandling);
        $this->assertArrayHasKey('follow_up_tasks', $failureHandling);
        $this->assertInstanceOf(\DateTimeImmutable::class, $failureHandling['failure_handled_at']);
    }

    public function testServiceConstructorAndBasicFunctionality(): void
    {
        // 验证服务可以正确实例化
        $this->assertInstanceOf(QualityFailureHandlerService::class, $this->service);

        // 验证基本功能工作正常
        $task = new QualityTask();
        $task->setTaskName('基本功能测试');
        $task->setType(TaskType::QUALITY);
        $task->setData([]);

        $result = $this->service->handleQualityFailure($task, '基本测试');
        $this->assertArrayHasKey('handling_actions', $result);
        $this->assertArrayHasKey('cost_estimation', $result);
    }

    /**
     * 测试时间线生成的准确性
     */
    public function testTimelineGeneration(): void
    {
        $task = new QualityTask();
        $task->setTaskName('时间线测试');
        $task->setType(TaskType::QUALITY);
        $task->setData([]);

        $failureDetails = [
            'failure_type' => 'damage',
            'severity_level' => 'critical',
            'affected_quantity' => 30,
        ];

        $result = $this->service->handleQualityFailure($task, '时间线测试', $failureDetails);

        $timeline = $result['timeline'];
        $this->assertIsArray($timeline);
        $this->assertNotEmpty($timeline);

        // 验证时间线项目包含必要字段
        foreach ($timeline as $item) {
            $this->assertIsArray($item);
            $this->assertArrayHasKey('scheduled_time', $item);
            $this->assertInstanceOf(\DateTimeImmutable::class, $item['scheduled_time']);

            if (isset($item['action'])) {
                $this->assertArrayHasKey('estimated_duration', $item);
            }

            if (isset($item['task'])) {
                $this->assertArrayHasKey('estimated_completion', $item);
                $this->assertInstanceOf(\DateTimeImmutable::class, $item['estimated_completion']);
            }
        }

        // 验证时间线按时间顺序排列
        for ($i = 1; $i < count($timeline); ++$i) {
            $this->assertIsArray($timeline[$i - 1]);
            $this->assertIsArray($timeline[$i]);
            $this->assertArrayHasKey('scheduled_time', $timeline[$i - 1]);
            $this->assertArrayHasKey('scheduled_time', $timeline[$i]);
            $this->assertInstanceOf(\DateTimeImmutable::class, $timeline[$i - 1]['scheduled_time']);
            $this->assertInstanceOf(\DateTimeImmutable::class, $timeline[$i]['scheduled_time']);
            $this->assertGreaterThanOrEqual(
                $timeline[$i - 1]['scheduled_time'],
                $timeline[$i]['scheduled_time']
            );
        }
    }

    /**
     * 测试成本计算的边界情况
     */
    public function testCostCalculationBoundaryConditions(): void
    {
        $task = new QualityTask();
        $task->setTaskName('成本边界测试');
        $task->setType(TaskType::QUALITY);
        $task->setData([]);

        // 测试零数量
        $result1 = $this->service->handleQualityFailure($task, '零数量测试', [
            'affected_quantity' => 0,
            'severity_level' => 'high',
        ]);
        $this->assertIsArray($result1['cost_estimation']);
        $this->assertEquals(0, $result1['cost_estimation']['cost']);

        // 测试大数量
        $result2 = $this->service->handleQualityFailure($task, '大数量测试', [
            'affected_quantity' => 1000,
            'severity_level' => 'critical',
            'failure_type' => 'contamination',
        ]);
        $this->assertIsArray($result2['cost_estimation']);
        $expectedCost = (10 + 100) * 5 * 1000; // 550,000
        $this->assertEquals($expectedCost, $result2['cost_estimation']['cost']);
    }

    /**
     * 测试高成本影响的自动索赔创建
     */
    public function testAutoClaimCreationForHighCostImpact(): void
    {
        $task = new QualityTask();
        $task->setTaskName('自动索赔测试');
        $task->setType(TaskType::QUALITY);
        $task->setData([]);

        $failureDetails = [
            'failure_type' => 'contamination',
            'severity_level' => 'critical',
            'affected_quantity' => 100, // 高数量导致高成本
            'cost_impact' => 2000, // 超过1000阈值
        ];

        $handlingOptions = [
            'create_claim' => false, // 即使设为false，高成本也应触发索赔
        ];

        $result = $this->service->handleQualityFailure($task, '高成本测试', $failureDetails, $handlingOptions);

        // 验证创建了索赔任务
        $hasCreateClaim = false;
        $this->assertIsArray($result['follow_up_tasks']);
        foreach ($result['follow_up_tasks'] as $followUpTask) {
            $this->assertIsArray($followUpTask);
            if ('create_claim' === $followUpTask['type']) {
                $hasCreateClaim = true;
                break;
            }
        }
        $this->assertTrue($hasCreateClaim, '高成本影响应该自动创建索赔任务');
    }
}
