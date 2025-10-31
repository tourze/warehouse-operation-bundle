<?php

namespace Tourze\WarehouseOperationBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Tourze\ProductServiceContracts\SKU;
use Tourze\StockManageBundle\Entity\StockInbound;
use Tourze\WarehouseOperationBundle\Entity\InboundTask;
use Tourze\WarehouseOperationBundle\Entity\WarehouseTask;
use Tourze\WarehouseOperationBundle\Enum\TaskStatus;
use Tourze\WarehouseOperationBundle\Enum\TaskType;
use Tourze\WarehouseOperationBundle\Event\TaskCompletedEvent;
use Tourze\WarehouseOperationBundle\Event\TaskStartedEvent;
use Tourze\WarehouseOperationBundle\Exception\TaskNotFoundException;
use Tourze\WarehouseOperationBundle\Exception\TaskStatusException;
use Tourze\WarehouseOperationBundle\Repository\WarehouseTaskRepository;
use Tourze\WarehouseOperationBundle\Service\InboundProcess;
use Tourze\WarehouseOperationBundle\Service\StockIntegrationService;
use Tourze\WarehouseOperationBundle\Service\TaskManagerInterface;

/**
 * @internal
 */
#[CoversClass(InboundProcess::class)]
class InboundProcessTest extends TestCase
{
    private InboundProcess $service;

    /** @var WarehouseTaskRepository&MockObject */
    private WarehouseTaskRepository $taskRepository;

    /** @var EventDispatcherInterface&MockObject */
    private EventDispatcherInterface $eventDispatcher;

    /** @var TaskManagerInterface&MockObject */
    private TaskManagerInterface $taskManager;

    /** @var StockIntegrationService&MockObject */
    private StockIntegrationService $stockIntegrationService;

    public function testStartInboundShouldCreateInboundTaskAndReturnIt(): void
    {
        $items = [
            [
                'sku' => 'TEST001',
                'quantity' => 100,
                'expected_quality' => 'A',
                'supplier_batch' => 'B001',
            ],
            [
                'sku' => 'TEST002',
                'quantity' => 50,
                'expected_quality' => 'B',
                'supplier_batch' => 'B002',
            ],
        ];
        $warehouseId = 1;

        $mockTask = $this->createMock(InboundTask::class);
        $mockTask->method('getType')->willReturn(TaskType::INBOUND);
        $mockTask->method('getStatus')->willReturn(TaskStatus::PENDING);
        $mockTask->method('getData')->willReturn([
            'items' => $items,
            'warehouse_id' => $warehouseId,
            'step' => 'receiving',
        ]);

        $this->taskManager
            ->expects($this->once())
            ->method('createTask')
            ->with(TaskType::INBOUND, [
                'items' => $items,
                'warehouse_id' => $warehouseId,
                'step' => 'receiving',
            ])
            ->willReturn($mockTask)
        ;

        $result = $this->service->startInbound($items, $warehouseId);

        $this->assertInstanceOf(InboundTask::class, $result);
        $this->assertEquals(TaskType::INBOUND, $result->getType());
        $this->assertEquals(TaskStatus::PENDING, $result->getStatus());
    }

    public function testStartInboundWithoutWarehouseIdShouldUseDefaultWarehouse(): void
    {
        $items = [
            [
                'sku' => 'TEST001',
                'quantity' => 100,
            ],
        ];

        $mockTask = $this->createMock(InboundTask::class);
        $mockTask->method('getType')->willReturn(TaskType::INBOUND);
        $mockTask->method('getStatus')->willReturn(TaskStatus::PENDING);
        $mockTask->method('getData')->willReturn([
            'items' => $items,
            'warehouse_id' => null,
            'step' => 'receiving',
        ]);

        $this->taskManager
            ->expects($this->once())
            ->method('createTask')
            ->with(TaskType::INBOUND, [
                'items' => $items,
                'warehouse_id' => null,
                'step' => 'receiving',
            ])
            ->willReturn($mockTask)
        ;

        $result = $this->service->startInbound($items);

        $this->assertInstanceOf(InboundTask::class, $result);
        $this->assertEquals(TaskType::INBOUND, $result->getType());
    }

    public function testExecuteReceivingShouldUpdateTaskDataAndStatus(): void
    {
        $taskId = 123;
        $actualItems = [
            [
                'sku' => 'TEST001',
                'actual_quantity' => 98,
                'received_quality' => 'A',
                'batch_no' => 'B001',
                'damage_notes' => '2 damaged items',
            ],
        ];

        $task = $this->createMock(InboundTask::class);
        $task->method('getId')->willReturn($taskId);
        $task->method('getStatus')->willReturn(TaskStatus::PENDING);

        $this->taskRepository
            ->expects($this->once())
            ->method('find')
            ->with($taskId)
            ->willReturn($task)
        ;

        $task
            ->expects($this->once())
            ->method('setStatus')
            ->with(TaskStatus::IN_PROGRESS)
        ;

        $task
            ->expects($this->once())
            ->method('setData')
            ->with([
                'step' => 'quality_check',
                'received_items' => $actualItems,
            ])
        ;

        $this->taskRepository
            ->expects($this->once())
            ->method('save')
            ->with($task)
        ;

        $this->eventDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with(self::isInstanceOf(TaskStartedEvent::class))
        ;

        $result = $this->service->executeReceiving($taskId, $actualItems);

        $this->assertTrue($result);
    }

    public function testExecuteReceivingWithNonExistentTaskShouldThrowException(): void
    {
        $taskId = 999;
        $actualItems = [];

        $this->taskRepository
            ->expects($this->once())
            ->method('find')
            ->with($taskId)
            ->willReturn(null)
        ;

        $this->expectException(TaskNotFoundException::class);
        $this->expectExceptionMessage('Task with ID 999 not found');

        $this->service->executeReceiving($taskId, $actualItems);
    }

    public function testExecuteReceivingWithWrongTaskTypeShouldThrowException(): void
    {
        $taskId = 123;
        $actualItems = [];

        $task = $this->createMock(WarehouseTask::class);
        $task->method('getId')->willReturn($taskId);

        $this->taskRepository
            ->expects($this->once())
            ->method('find')
            ->with($taskId)
            ->willReturn($task)
        ;

        $this->expectException(TaskStatusException::class);
        $this->expectExceptionMessage('Task 123 is not an inbound task');

        $this->service->executeReceiving($taskId, $actualItems);
    }

    public function testExecuteQualityCheckShouldProcessQualityResultsAndUpdateStatus(): void
    {
        $taskId = 123;
        $qualityResults = [
            [
                'sku' => 'TEST001',
                'batch_no' => 'B001',
                'quantity' => 95,
                'quality_grade' => 'A',
                'passed' => true,
            ],
            [
                'sku' => 'TEST001',
                'batch_no' => 'B001',
                'quantity' => 3,
                'quality_grade' => 'C',
                'passed' => false,
                'reject_reason' => 'Damaged',
            ],
        ];

        $task = $this->createMock(InboundTask::class);
        $task->method('getId')->willReturn($taskId);
        $task->method('getStatus')->willReturn(TaskStatus::IN_PROGRESS);
        $task->method('getType')->willReturn(TaskType::INBOUND);

        $this->taskRepository
            ->expects($this->once())
            ->method('find')
            ->with($taskId)
            ->willReturn($task)
        ;

        $task
            ->expects($this->once())
            ->method('setData')
            ->with([
                'step' => 'putaway',
                'quality_results' => $qualityResults,
                'passed_items' => [
                    [
                        'sku' => 'TEST001',
                        'batch_no' => 'B001',
                        'quantity' => 95,
                        'quality_grade' => 'A',
                        'passed' => true,
                    ],
                ],
                'rejected_items' => [
                    [
                        'sku' => 'TEST001',
                        'batch_no' => 'B001',
                        'quantity' => 3,
                        'quality_grade' => 'C',
                        'passed' => false,
                        'reject_reason' => 'Damaged',
                    ],
                ],
            ])
        ;

        $this->taskRepository
            ->expects($this->once())
            ->method('save')
            ->with($task)
        ;

        $result = $this->service->executeQualityCheck($taskId, $qualityResults);

        $this->assertTrue($result);
    }

    public function testExecuteQualityCheckWithAllItemsFailedShouldCreateReturnTask(): void
    {
        $taskId = 123;
        $qualityResults = [
            [
                'sku' => 'TEST001',
                'batch_no' => 'B001',
                'quantity' => 100,
                'quality_grade' => 'D',
                'passed' => false,
                'reject_reason' => 'All items damaged',
            ],
        ];

        $task = $this->createMock(InboundTask::class);
        $task->method('getId')->willReturn($taskId);
        $task->method('getStatus')->willReturn(TaskStatus::IN_PROGRESS);
        $task->method('getType')->willReturn(TaskType::INBOUND);

        $this->taskRepository
            ->expects($this->once())
            ->method('find')
            ->with($taskId)
            ->willReturn($task)
        ;

        $this->taskManager
            ->expects($this->once())
            ->method('createTask')
            ->with(
                TaskType::OUTBOUND,
                [
                    'type' => 'return',
                    'original_task_id' => $taskId,
                    'items' => $qualityResults,
                ]
            )
        ;

        $task
            ->expects($this->once())
            ->method('setStatus')
            ->with(TaskStatus::COMPLETED)
        ;

        $this->taskRepository
            ->expects($this->once())
            ->method('save')
            ->with($task)
        ;

        $this->eventDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with(self::isInstanceOf(TaskCompletedEvent::class))
        ;

        $result = $this->service->executeQualityCheck($taskId, $qualityResults);

        $this->assertTrue($result);
    }

    public function testExecutePutawayShouldUpdateInventoryAndCompleteTask(): void
    {
        $taskId = 123;
        $skuMock = $this->createMock(SKU::class);

        $locationAssignments = [
            [
                'sku' => $skuMock,
                'batch_no' => 'B001',
                'quantity' => 95,
                'location_code' => 'A-01-01',
                'shelf_id' => 1,
            ],
        ];

        $task = $this->createMock(InboundTask::class);
        $task->method('getId')->willReturn($taskId);
        $task->method('getStatus')->willReturn(TaskStatus::IN_PROGRESS);
        $task->method('getType')->willReturn(TaskType::INBOUND);

        $this->taskRepository
            ->expects($this->once())
            ->method('find')
            ->with($taskId)
            ->willReturn($task)
        ;

        $stockInboundMock = $this->createMock(StockInbound::class);

        $this->stockIntegrationService
            ->expects($this->once())
            ->method('processInbound')
            ->willReturn($stockInboundMock)
        ;

        $task
            ->expects($this->once())
            ->method('setStatus')
            ->with(TaskStatus::COMPLETED)
        ;

        $task
            ->expects($this->once())
            ->method('setData')
            ->with(self::callback(function ($data) use ($locationAssignments) {
                return 'completed' === $data['step']
                    && $data['location_assignments'] === $locationAssignments
                    && is_string($data['completed_at'])
                    && '' !== $data['completed_at'];
            }))
        ;

        $this->taskRepository
            ->expects($this->once())
            ->method('save')
            ->with($task)
        ;

        $this->eventDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with(self::isInstanceOf(TaskCompletedEvent::class))
        ;

        $result = $this->service->executePutaway($taskId, $locationAssignments);

        $this->assertTrue($result);
    }

    public function testExecutePutawayWithStockIntegrationFailureShouldReturnFalse(): void
    {
        $taskId = 123;
        $skuMock = $this->createMock(SKU::class);

        $locationAssignments = [
            [
                'sku' => $skuMock,
                'batch_no' => 'B001',
                'quantity' => 95,
                'location_code' => 'A-01-01',
                'shelf_id' => 1,
            ],
        ];

        $task = $this->createMock(InboundTask::class);
        $task->method('getId')->willReturn($taskId);
        $task->method('getStatus')->willReturn(TaskStatus::IN_PROGRESS);
        $task->method('getType')->willReturn(TaskType::INBOUND);

        $this->taskRepository
            ->expects($this->once())
            ->method('find')
            ->with($taskId)
            ->willReturn($task)
        ;

        $this->stockIntegrationService
            ->expects($this->once())
            ->method('processInbound')
            ->willThrowException(new \RuntimeException('Stock integration failed'))
        ;

        $task
            ->expects($this->once())
            ->method('setStatus')
            ->with(TaskStatus::FAILED)
        ;

        $this->taskRepository
            ->expects($this->once())
            ->method('save')
            ->with($task)
        ;

        $result = $this->service->executePutaway($taskId, $locationAssignments);

        $this->assertFalse($result);
    }

    public function testExecuteReceivingWithInvalidTaskStatusShouldThrowException(): void
    {
        $taskId = 123;
        $actualItems = [];

        $task = $this->createMock(InboundTask::class);
        $task->method('getId')->willReturn($taskId);
        $task->method('getStatus')->willReturn(TaskStatus::COMPLETED);
        $task->method('getType')->willReturn(TaskType::INBOUND);

        $this->taskRepository
            ->expects($this->once())
            ->method('find')
            ->with($taskId)
            ->willReturn($task)
        ;

        $this->expectException(TaskStatusException::class);
        $this->expectExceptionMessage('Task 123 is not in PENDING status, current status: COMPLETED');

        $this->service->executeReceiving($taskId, $actualItems);
    }

    public function testExecuteQualityCheckWithInvalidTaskStatusShouldThrowException(): void
    {
        $taskId = 123;
        $qualityResults = [];

        $task = $this->createMock(InboundTask::class);
        $task->method('getId')->willReturn($taskId);
        $task->method('getStatus')->willReturn(TaskStatus::PENDING);
        $task->method('getType')->willReturn(TaskType::INBOUND);

        $this->taskRepository
            ->expects($this->once())
            ->method('find')
            ->with($taskId)
            ->willReturn($task)
        ;

        $this->expectException(TaskStatusException::class);
        $this->expectExceptionMessage('Task 123 is not in IN_PROGRESS status, current status: PENDING');

        $this->service->executeQualityCheck($taskId, $qualityResults);
    }

    public function testExecutePutawayWithInvalidTaskStatusShouldThrowException(): void
    {
        $taskId = 123;
        $locationAssignments = [];

        $task = $this->createMock(InboundTask::class);
        $task->method('getId')->willReturn($taskId);
        $task->method('getStatus')->willReturn(TaskStatus::PENDING);
        $task->method('getType')->willReturn(TaskType::INBOUND);

        $this->taskRepository
            ->expects($this->once())
            ->method('find')
            ->with($taskId)
            ->willReturn($task)
        ;

        $this->expectException(TaskStatusException::class);
        $this->expectExceptionMessage('Task 123 is not in IN_PROGRESS status, current status: PENDING');

        $this->service->executePutaway($taskId, $locationAssignments);
    }

    protected function setUp(): void
    {
        // Initialize mock objects
        $this->taskRepository = $this->createMock(WarehouseTaskRepository::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->taskManager = $this->createMock(TaskManagerInterface::class);
        $this->stockIntegrationService = $this->createMock(StockIntegrationService::class);

        // Create service instance with mocked dependencies
        $this->service = new InboundProcess(
            $this->taskManager,
            $this->stockIntegrationService,
            $this->taskRepository,
            $this->eventDispatcher,
        );
    }
}
