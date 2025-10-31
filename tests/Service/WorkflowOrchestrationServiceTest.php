<?php

namespace Tourze\WarehouseOperationBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\WarehouseOperationBundle\Entity\CountPlan;
use Tourze\WarehouseOperationBundle\Entity\QualityStandard;
use Tourze\WarehouseOperationBundle\Service\InventoryCountServiceInterface;
use Tourze\WarehouseOperationBundle\Service\PathOptimizationServiceInterface;
use Tourze\WarehouseOperationBundle\Service\QualityControlServiceInterface;
use Tourze\WarehouseOperationBundle\Service\TaskSchedulingServiceInterface;
use Tourze\WarehouseOperationBundle\Service\WorkflowOrchestrationService;

/**
 * @group warehouse-operation
 * @group service
 * @group workflow-orchestration
 * @internal
 */
#[CoversClass(WorkflowOrchestrationService::class)]
#[RunTestsInSeparateProcesses]
class WorkflowOrchestrationServiceTest extends AbstractIntegrationTestCase
{
    private WorkflowOrchestrationService $service;

    private TaskSchedulingServiceInterface&MockObject $schedulingService;

    private QualityControlServiceInterface&MockObject $qualityService;

    private InventoryCountServiceInterface&MockObject $inventoryService;

    private PathOptimizationServiceInterface&MockObject $pathService;

    private LoggerInterface&MockObject $logger;

    protected function onSetUp(): void
    {
        $this->schedulingService = $this->createMock(TaskSchedulingServiceInterface::class);
        $this->qualityService = $this->createMock(QualityControlServiceInterface::class);
        $this->inventoryService = $this->createMock(InventoryCountServiceInterface::class);
        $this->pathService = $this->createMock(PathOptimizationServiceInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->service = parent::getService(WorkflowOrchestrationService::class);
    }

    public function testStartInboundWorkflowSuccess(): void
    {
        $parameters = [
            'receipt_id' => 'REC001',
            'items' => [
                ['id' => 1, 'sku' => 'PROD001', 'quantity' => 10],
                ['id' => 2, 'sku' => 'PROD002', 'quantity' => 5],
            ],
            'require_quality_check' => false,
        ];

        // Mock服务调用
        $this->schedulingService
            ->expects($this->once())
            ->method('scheduleTaskBatch')
            ->willReturn(['assignments' => [], 'unassigned' => []])
        ;

        $result = $this->service->startWorkflow('inbound', $parameters);

        self::assertIsArray($result);
        self::assertArrayHasKey('workflow_id', $result);
        self::assertArrayHasKey('status', $result);
        self::assertArrayHasKey('result', $result);
        self::assertArrayHasKey('estimated_completion', $result);

        self::assertSame('started', $result['status']);

        $workflowId = $result['workflow_id'];
        self::assertIsString($workflowId);
        self::assertStringStartsWith('inbound_', $workflowId);

        // 验证result结构
        $workflowResult = $result['result'];
        self::assertIsArray($workflowResult);
        self::assertArrayHasKey('receiving_tasks', $workflowResult);
        self::assertArrayHasKey('quality_tasks', $workflowResult);
        self::assertArrayHasKey('putaway_tasks', $workflowResult);
        self::assertArrayHasKey('assignments', $workflowResult);
    }

    public function testStartInboundWorkflowWithQualityCheck(): void
    {
        $parameters = [
            'receipt_id' => 'REC001',
            'items' => [['id' => 1, 'sku' => 'PROD001', 'quantity' => 10]],
            'require_quality_check' => true,
        ];

        $this->schedulingService
            ->expects($this->once())
            ->method('scheduleTaskBatch')
            ->willReturn(['assignments' => [], 'unassigned' => []])
        ;

        // 由于createPutawayTasks返回空数组，路径优化不会被调用
        $this->pathService
            ->expects($this->never())
            ->method('optimizeBatchPaths')
        ;

        $result = $this->service->startWorkflow('inbound', $parameters);

        self::assertSame('started', $result['status']);
        // 由于没有putaway任务，path_optimization应该是null
        $workflowResult = $result['result'];
        self::assertIsArray($workflowResult);
        self::assertNull($workflowResult['path_optimization']);
    }

    public function testStartOutboundWorkflowSuccess(): void
    {
        $parameters = [
            'order_id' => 'ORD001',
            'items' => [
                ['id' => 1, 'sku' => 'PROD001', 'quantity' => 2],
            ],
            'path_strategy' => 's_shape',
        ];

        $this->schedulingService
            ->expects($this->once())
            ->method('scheduleTaskBatch')
            ->willReturn(['assignments' => [], 'unassigned' => []])
        ;

        // 由于createPickingTasks返回空数组，路径优化不会被调用
        $this->pathService
            ->expects($this->never())
            ->method('optimizeBatchPaths')
        ;

        $result = $this->service->startWorkflow('outbound', $parameters);

        if ('failed' === $result['status']) {
            $errorMessage = $result['error'] ?? 'unknown error';
            self::assertIsString($errorMessage);
            self::fail('Outbound workflow failed with error: ' . $errorMessage);
        }

        self::assertSame('started', $result['status']);

        $workflowResult = $result['result'];
        self::assertIsArray($workflowResult);
        self::assertArrayHasKey('picking_tasks', $workflowResult);
        self::assertArrayHasKey('packing_tasks', $workflowResult);
        self::assertArrayHasKey('shipping_tasks', $workflowResult);
        self::assertArrayHasKey('path_optimization', $workflowResult);
        // 由于没有picking任务，path_optimization应该是空数组
        self::assertSame([], $workflowResult['path_optimization']);
    }

    public function testStartInventoryCountWorkflowSuccess(): void
    {
        $parameters = [
            'count_type' => 'cycle',
            'criteria' => ['zone_id' => 1],
        ];

        $mockCountPlan = $this->createMock(CountPlan::class);
        $mockCountPlan->method('getId')->willReturn(123);

        $this->inventoryService
            ->expects($this->once())
            ->method('generateCountPlan')
            ->with('cycle', ['zone_id' => 1])
            ->willReturn($mockCountPlan)
        ;

        $this->schedulingService
            ->expects($this->once())
            ->method('scheduleTaskBatch')
            ->willReturn(['assignments' => [], 'unassigned' => []])
        ;

        $result = $this->service->startWorkflow('inventory_count', $parameters);

        self::assertSame('started', $result['status']);

        $workflowResult = $result['result'];
        self::assertIsArray($workflowResult);
        self::assertArrayHasKey('count_plan_id', $workflowResult);
        self::assertArrayHasKey('count_tasks', $workflowResult);
        self::assertSame(123, $workflowResult['count_plan_id']);
    }

    public function testStartQualityControlWorkflowSuccess(): void
    {
        $parameters = [
            'items' => [['id' => 1]],
            'quality_standards' => ['standard1'],
            'product_attributes' => ['category' => 'electronics'],
        ];

        $mockStandard = $this->createMock(QualityStandard::class);

        $this->qualityService
            ->expects($this->once())
            ->method('getApplicableStandards')
            ->with(['category' => 'electronics'])
            ->willReturn([$mockStandard])
        ;

        $this->schedulingService
            ->expects($this->once())
            ->method('scheduleTaskBatch')
            ->willReturn(['assignments' => [], 'unassigned' => []])
        ;

        $result = $this->service->startWorkflow('quality_control', $parameters);

        self::assertSame('started', $result['status']);

        $workflowResult = $result['result'];
        self::assertIsArray($workflowResult);
        self::assertArrayHasKey('quality_tasks', $workflowResult);
        self::assertArrayHasKey('standards', $workflowResult);
        self::assertSame(1, $workflowResult['standards']);
    }

    public function testStartMaintenanceWorkflowSuccess(): void
    {
        $parameters = [
            'equipment_id' => 'EQP001',
            'maintenance_type' => 'routine',
        ];

        $this->schedulingService
            ->expects($this->once())
            ->method('scheduleTaskBatch')
            ->willReturn(['assignments' => [], 'unassigned' => []])
        ;

        $result = $this->service->startWorkflow('maintenance', $parameters);

        self::assertSame('started', $result['status']);

        $workflowResult = $result['result'];
        self::assertIsArray($workflowResult);
        self::assertArrayHasKey('maintenance_tasks', $workflowResult);
        self::assertArrayHasKey('assignments', $workflowResult);
    }

    public function testStartWorkflowWithInvalidType(): void
    {
        $result = $this->service->startWorkflow('invalid_type', []);

        self::assertSame('failed', $result['status']);
        self::assertArrayHasKey('error', $result);
        $errorMessage = $result['error'] ?? '';
        self::assertIsString($errorMessage);
        self::assertStringContainsString('不支持的工作流类型', $errorMessage);
    }

    public function testStartWorkflowWithMissingParameters(): void
    {
        $this->logger
            ->expects($this->once())
            ->method('error')
        ;

        $result = $this->service->startWorkflow('inbound', []); // 缺少必要参数

        self::assertSame('failed', $result['status']);
        self::assertArrayHasKey('error', $result);
    }

    public function testGetWorkflowStatusNotFound(): void
    {
        $result = $this->service->getWorkflowStatus('non_existent_workflow');

        self::assertSame('not_found', $result['status']);
        self::assertSame('工作流不存在或已完成', $result['message']);
    }

    public function testHandleWorkflowExceptionNotFound(): void
    {
        $result = $this->service->handleWorkflowException('non_existent_workflow', [
            'type' => 'test_error',
            'severity' => 'low',
        ]);

        self::assertSame('not_found', $result['status']);
    }

    public function testHandleWorkflowExceptionLowSeverity(): void
    {
        // 首先启动一个工作流以便测试异常处理
        $parameters = [
            'receipt_id' => 'REC001',
            'items' => [['id' => 1, 'sku' => 'PROD001', 'quantity' => 10]],
        ];

        $this->schedulingService
            ->expects($this->once())
            ->method('scheduleTaskBatch')
            ->willReturn(['assignments' => [], 'unassigned' => []])
        ;

        $startResult = $this->service->startWorkflow('inbound', $parameters);
        $workflowId = $startResult['workflow_id'];
        self::assertIsString($workflowId);

        // 测试低严重程度异常处理
        $exceptionData = [
            'type' => 'minor_error',
            'severity' => 'low',
            'message' => 'Something minor went wrong',
        ];

        $this->logger
            ->expects($this->once())
            ->method('warning')
        ;

        $result = $this->service->handleWorkflowException($workflowId, $exceptionData);

        self::assertSame('exception_handled', $result['status']);
        self::assertArrayHasKey('handling_strategy', $result);
        $handlingStrategy = $result['handling_strategy'] ?? [];
        self::assertIsArray($handlingStrategy);
        self::assertTrue($result['can_continue']);
        self::assertSame('continue', $handlingStrategy['action'] ?? '');
    }

    public function testHandleWorkflowExceptionMediumSeverity(): void
    {
        // 启动工作流
        $parameters = [
            'receipt_id' => 'REC001',
            'items' => [['id' => 1]],
        ];

        $this->schedulingService->method('scheduleTaskBatch')->willReturn(['assignments' => []]);
        $startResult = $this->service->startWorkflow('inbound', $parameters);
        $workflowId = $startResult['workflow_id'];
        self::assertIsString($workflowId);

        // 中等严重程度异常
        $exceptionData = [
            'type' => 'medium_error',
            'severity' => 'medium',
        ];

        $result = $this->service->handleWorkflowException($workflowId, $exceptionData);

        self::assertSame('exception_handled', $result['status']);
        $handlingStrategy = $result['handling_strategy'] ?? [];
        self::assertIsArray($handlingStrategy);
        self::assertSame('retry', $handlingStrategy['action'] ?? '');
        self::assertTrue($result['can_continue']);
    }

    public function testHandleWorkflowExceptionHighSeverity(): void
    {
        // 启动工作流
        $parameters = ['receipt_id' => 'REC001', 'items' => [['id' => 1]]];
        $this->schedulingService->method('scheduleTaskBatch')->willReturn(['assignments' => []]);
        $startResult = $this->service->startWorkflow('inbound', $parameters);
        $workflowId = $startResult['workflow_id'];
        self::assertIsString($workflowId);

        // 高严重程度异常
        $exceptionData = [
            'type' => 'high_error',
            'severity' => 'high',
        ];

        $result = $this->service->handleWorkflowException($workflowId, $exceptionData);

        self::assertSame('exception_handled', $result['status']);
        $handlingStrategy = $result['handling_strategy'] ?? [];
        self::assertIsArray($handlingStrategy);
        self::assertSame('pause', $handlingStrategy['action'] ?? '');
        self::assertFalse($result['can_continue']);
    }

    public function testHandleWorkflowExceptionCriticalSeverity(): void
    {
        // 启动工作流
        $parameters = ['receipt_id' => 'REC001', 'items' => [['id' => 1]]];
        $this->schedulingService->method('scheduleTaskBatch')->willReturn(['assignments' => []]);
        $startResult = $this->service->startWorkflow('inbound', $parameters);
        $workflowId = $startResult['workflow_id'];
        self::assertIsString($workflowId);

        // 严重异常
        $exceptionData = [
            'type' => 'critical_error',
            'severity' => 'critical',
        ];

        $result = $this->service->handleWorkflowException($workflowId, $exceptionData);

        self::assertSame('exception_handled', $result['status']);
        $handlingStrategy = $result['handling_strategy'] ?? [];
        self::assertIsArray($handlingStrategy);
        self::assertSame('terminate', $handlingStrategy['action'] ?? '');
        self::assertFalse($result['can_continue']);
    }

    public function testHandleWorkflowExceptionUnknownSeverity(): void
    {
        // 启动工作流
        $parameters = ['receipt_id' => 'REC001', 'items' => [['id' => 1]]];
        $this->schedulingService->method('scheduleTaskBatch')->willReturn(['assignments' => []]);
        $startResult = $this->service->startWorkflow('inbound', $parameters);
        $workflowId = $startResult['workflow_id'];
        self::assertIsString($workflowId);

        // 未知严重程度（应该按medium处理）
        $exceptionData = [
            'type' => 'unknown_error',
            'severity' => 'unknown',
        ];

        $result = $this->service->handleWorkflowException($workflowId, $exceptionData);

        self::assertSame('exception_handled', $result['status']);
        $handlingStrategy = $result['handling_strategy'] ?? [];
        self::assertIsArray($handlingStrategy);
        self::assertSame('retry', $handlingStrategy['action'] ?? ''); // 应该按medium处理
    }

    public function testWorkflowIdGeneration(): void
    {
        $parameters = ['receipt_id' => 'REC001', 'items' => [['id' => 1]]];
        $this->schedulingService->method('scheduleTaskBatch')->willReturn(['assignments' => []]);

        $result1 = $this->service->startWorkflow('inbound', $parameters);
        $result2 = $this->service->startWorkflow('inbound', $parameters);

        $workflowId1 = $result1['workflow_id'];
        $workflowId2 = $result2['workflow_id'];
        self::assertIsString($workflowId1);
        self::assertIsString($workflowId2);

        self::assertNotSame($workflowId1, $workflowId2);
        self::assertStringStartsWith('inbound_', $workflowId1);
        self::assertStringStartsWith('inbound_', $workflowId2);
    }

    public function testEstimatedCompletionCalculation(): void
    {
        $parameters = [
            'receipt_id' => 'REC001',
            'items' => [
                ['id' => 1], ['id' => 2], ['id' => 3], // 3个items
            ],
        ];

        $this->schedulingService->method('scheduleTaskBatch')->willReturn(['assignments' => []]);

        $result = $this->service->startWorkflow('inbound', $parameters);

        $estimatedCompletion = $result['estimated_completion'];
        self::assertInstanceOf(\DateTime::class, $estimatedCompletion);

        // 入库工作流预计时间：30 + (3 * 2) = 36分钟
        $now = new \DateTime();
        $diffMinutes = ($estimatedCompletion->getTimestamp() - $now->getTimestamp()) / 60;

        self::assertGreaterThan(30, $diffMinutes); // 至少30分钟
        self::assertLessThan(40, $diffMinutes); // 不超过40分钟
    }

    public function testMultipleWorkflowsRunning(): void
    {
        $this->schedulingService->method('scheduleTaskBatch')->willReturn(['assignments' => []]);

        // 启动多个工作流
        $result1 = $this->service->startWorkflow('inbound', ['receipt_id' => 'R1', 'items' => [['id' => 1]]]);
        $result2 = $this->service->startWorkflow('outbound', ['order_id' => 'O1', 'items' => [['id' => 1]]]);

        self::assertSame('started', $result1['status']);
        self::assertSame('started', $result2['status']);

        // 检查两个工作流状态
        $workflowId1 = $result1['workflow_id'];
        $workflowId2 = $result2['workflow_id'];
        self::assertIsString($workflowId1);
        self::assertIsString($workflowId2);

        $status1 = $this->service->getWorkflowStatus($workflowId1);
        $status2 = $this->service->getWorkflowStatus($workflowId2);

        self::assertNotSame('not_found', $status1['status']);
        self::assertNotSame('not_found', $status2['status']);
        self::assertSame('inbound', $status1['type']);
        self::assertSame('outbound', $status2['type']);
    }
}
