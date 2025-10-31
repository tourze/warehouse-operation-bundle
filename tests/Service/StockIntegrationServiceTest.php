<?php

namespace Tourze\WarehouseOperationBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\ProductCoreBundle\Entity\Sku;
use Tourze\StockManageBundle\Entity\StockInbound;
use Tourze\StockManageBundle\Entity\StockOutbound;
use Tourze\StockManageBundle\Exception\InsufficientStockException;
use Tourze\StockManageBundle\Model\StockSummary;
use Tourze\StockManageBundle\Service\InboundServiceInterface;
use Tourze\StockManageBundle\Service\OutboundServiceInterface;
use Tourze\StockManageBundle\Service\StockServiceInterface;
use Tourze\WarehouseOperationBundle\Exception\StockIntegrationException;
use Tourze\WarehouseOperationBundle\Service\StockIntegrationService;

/**
 * @internal
 */
#[CoversClass(StockIntegrationService::class)]
#[RunTestsInSeparateProcesses]
class StockIntegrationServiceTest extends AbstractIntegrationTestCase
{
    private StockIntegrationService $service;

    private StockServiceInterface $stockService;

    private InboundServiceInterface $inboundService;

    private OutboundServiceInterface $outboundService;

    protected function onSetUp(): void
    {
        $this->stockService = $this->createMock(StockServiceInterface::class);
        $this->inboundService = $this->createMock(InboundServiceInterface::class);
        $this->outboundService = $this->createMock(OutboundServiceInterface::class);

        $this->service = parent::getService(StockIntegrationService::class);
    }

    public function testProcessInboundShouldCallPurchaseInboundService(): void
    {
        $sku = $this->createMock(SKU::class);
        $sku->method('getId')->willReturn('SKU001');
        $sku->method('getGtin')->willReturn('1234567890123');
        $sku->method('getMpn')->willReturn('MPN001');
        $sku->method('getRemark')->willReturn('Test SKU');
        $sku->method('isValid')->willReturn(true);

        $data = [
            'purchase_order_no' => 'PO001',
            'items' => [
                [
                    'sku' => $sku,
                    'batch_no' => 'B001',
                    'quantity' => 100,
                    'unit_cost' => 10.50,
                    'quality_level' => 'A',
                ],
            ],
            'operator' => 'worker001',
            'location_id' => 'LOC001',
        ];

        $expectedResult = $this->createMock(StockInbound::class);

        $this->inboundService
            ->expects($this->once())
            ->method('purchaseInbound')
            ->with($data)
            ->willReturn($expectedResult)
        ;

        $result = $this->service->processInbound($data);

        $this->assertSame($expectedResult, $result);
    }

    public function testProcessInboundWithExceptionShouldThrowStockIntegrationException(): void
    {
        $data = [
            'purchase_order_no' => 'PO001',
            'items' => [],
            'operator' => 'worker001',
        ];

        $this->inboundService
            ->expects($this->once())
            ->method('purchaseInbound')
            ->with($data)
            ->willThrowException(new \InvalidArgumentException('Invalid items'))
        ;

        $this->expectException(StockIntegrationException::class);
        $this->expectExceptionMessage('Stock inbound operation failed: Invalid items');

        $this->service->processInbound($data);
    }

    public function testProcessOutboundShouldCallSalesOutboundService(): void
    {
        $sku = $this->createMock(SKU::class);
        $sku->method('getId')->willReturn('SKU001');
        $sku->method('getGtin')->willReturn('1234567890123');
        $sku->method('getMpn')->willReturn('MPN001');
        $sku->method('getRemark')->willReturn('Test SKU');
        $sku->method('isValid')->willReturn(true);

        $data = [
            'order_no' => 'SO001',
            'items' => [
                [
                    'sku' => $sku,
                    'quantity' => 50,
                ],
            ],
            'operator' => 'worker001',
            'location_id' => 'LOC001',
        ];

        $expectedResult = $this->createMock(StockOutbound::class);

        $this->outboundService
            ->expects($this->once())
            ->method('salesOutbound')
            ->with($data)
            ->willReturn($expectedResult)
        ;

        $result = $this->service->processOutbound($data);

        $this->assertSame($expectedResult, $result);
    }

    public function testProcessOutboundWithInsufficientStockShouldThrowStockIntegrationException(): void
    {
        $sku = $this->createMock(SKU::class);
        $sku->method('getId')->willReturn('SKU001');
        $sku->method('getGtin')->willReturn('1234567890123');
        $sku->method('getMpn')->willReturn('MPN001');
        $sku->method('getRemark')->willReturn('Test SKU');
        $sku->method('isValid')->willReturn(true);

        $data = [
            'order_no' => 'SO001',
            'items' => [
                [
                    'sku' => $sku,
                    'quantity' => 1000,
                ],
            ],
            'operator' => 'worker001',
        ];

        $this->outboundService
            ->expects($this->once())
            ->method('salesOutbound')
            ->with($data)
            ->willThrowException(new InsufficientStockException('Not enough stock'))
        ;

        $this->expectException(StockIntegrationException::class);
        $this->expectExceptionMessage('Stock outbound operation failed: Not enough stock');

        $this->service->processOutbound($data);
    }

    public function testCheckStockAvailabilityShouldReturnBoolean(): void
    {
        $sku = $this->createMock(SKU::class);
        $sku->method('getId')->willReturn('SKU001');
        $sku->method('getGtin')->willReturn('1234567890123');
        $sku->method('getMpn')->willReturn('MPN001');
        $sku->method('getRemark')->willReturn('Test SKU');
        $sku->method('isValid')->willReturn(true);

        $quantity = 100;
        $criteria = ['location_id' => 'LOC001'];

        $this->stockService
            ->expects($this->once())
            ->method('checkStockAvailability')
            ->with($sku, $quantity, $criteria)
            ->willReturn(true)
        ;

        $result = $this->service->checkStockAvailability($sku, $quantity, $criteria);

        $this->assertTrue($result);
    }

    public function testCheckStockAvailabilityWithExceptionShouldThrowStockIntegrationException(): void
    {
        $sku = $this->createMock(SKU::class);
        $sku->method('getId')->willReturn('SKU001');
        $sku->method('getGtin')->willReturn('1234567890123');
        $sku->method('getMpn')->willReturn('MPN001');
        $sku->method('getRemark')->willReturn('Test SKU');
        $sku->method('isValid')->willReturn(true);

        $quantity = 100;

        $this->stockService
            ->expects($this->once())
            ->method('checkStockAvailability')
            ->with($sku, $quantity, [])
            ->willThrowException(new \RuntimeException('Database error'))
        ;

        $this->expectException(StockIntegrationException::class);
        $this->expectExceptionMessage('Stock availability check failed: Database error');

        $this->service->checkStockAvailability($sku, $quantity);
    }

    public function testGetAvailableStockShouldReturnStockSummary(): void
    {
        $sku = $this->createMock(SKU::class);
        $sku->method('getId')->willReturn('SKU001');
        $sku->method('getGtin')->willReturn('1234567890123');
        $sku->method('getMpn')->willReturn('MPN001');
        $sku->method('getRemark')->willReturn('Test SKU');
        $sku->method('isValid')->willReturn(true);

        $criteria = ['location_id' => 'LOC001'];
        $expectedSummary = $this->createMock(StockSummary::class);

        $this->stockService
            ->expects($this->once())
            ->method('getAvailableStock')
            ->with($sku, $criteria)
            ->willReturn($expectedSummary)
        ;

        $result = $this->service->getAvailableStock($sku, $criteria);

        $this->assertSame($expectedSummary, $result);
    }

    public function testGetAvailableStockWithExceptionShouldThrowStockIntegrationException(): void
    {
        $sku = $this->createMock(SKU::class);
        $sku->method('getId')->willReturn('SKU001');
        $sku->method('getGtin')->willReturn('1234567890123');
        $sku->method('getMpn')->willReturn('MPN001');
        $sku->method('getRemark')->willReturn('Test SKU');
        $sku->method('isValid')->willReturn(true);

        $this->stockService
            ->expects($this->once())
            ->method('getAvailableStock')
            ->with($sku, [])
            ->willThrowException(new \RuntimeException('Query failed'))
        ;

        $this->expectException(StockIntegrationException::class);
        $this->expectExceptionMessage('Get available stock failed: Query failed');

        $this->service->getAvailableStock($sku);
    }

    public function testProcessTransferInboundShouldCallTransferInboundService(): void
    {
        $data = [
            'transfer_no' => 'TF001',
            'from_location' => 'LOC001',
            'items' => [
                [
                    'batch_id' => 'BATCH001',
                    'quantity' => 50,
                ],
            ],
            'operator' => 'worker001',
            'location_id' => 'LOC002',
        ];

        $expectedResult = $this->createMock(StockInbound::class);

        $this->inboundService
            ->expects($this->once())
            ->method('transferInbound')
            ->with($data)
            ->willReturn($expectedResult)
        ;

        $result = $this->service->processTransferInbound($data);

        $this->assertSame($expectedResult, $result);
    }

    public function testProcessTransferOutboundShouldCallTransferOutboundService(): void
    {
        $data = [
            'transfer_no' => 'TF001',
            'to_location' => 'LOC002',
            'items' => [
                [
                    'batch_id' => 'BATCH001',
                    'quantity' => 50,
                ],
            ],
            'operator' => 'worker001',
            'location_id' => 'LOC001',
        ];

        $expectedResult = $this->createMock(StockOutbound::class);

        $this->outboundService
            ->expects($this->once())
            ->method('transferOutbound')
            ->with($data)
            ->willReturn($expectedResult)
        ;

        $result = $this->service->processTransferOutbound($data);

        $this->assertSame($expectedResult, $result);
    }

    public function testProcessDamageOutboundShouldCallDamageOutboundService(): void
    {
        $data = [
            'damage_no' => 'DM001',
            'items' => [
                [
                    'batch_id' => 'BATCH001',
                    'quantity' => 10,
                    'reason' => 'Damaged during handling',
                ],
            ],
            'operator' => 'worker001',
            'location_id' => 'LOC001',
        ];

        $expectedResult = $this->createMock(StockOutbound::class);

        $this->outboundService
            ->expects($this->once())
            ->method('damageOutbound')
            ->with($data)
            ->willReturn($expectedResult)
        ;

        $result = $this->service->processDamageOutbound($data);

        $this->assertSame($expectedResult, $result);
    }

    public function testBatchProcessInboundShouldProcessMultipleOperations(): void
    {
        $sku1 = $this->createMock(SKU::class);
        $sku1->method('getId')->willReturn('SKU001');
        $sku1->method('getGtin')->willReturn('1234567890123');
        $sku1->method('getMpn')->willReturn('MPN001');
        $sku1->method('getRemark')->willReturn('Test SKU 1');
        $sku1->method('isValid')->willReturn(true);

        $sku2 = $this->createMock(SKU::class);
        $sku2->method('getId')->willReturn('SKU002');
        $sku2->method('getGtin')->willReturn('1234567890124');
        $sku2->method('getMpn')->willReturn('MPN002');
        $sku2->method('getRemark')->willReturn('Test SKU 2');
        $sku2->method('isValid')->willReturn(true);

        $operationsData = [
            [
                'purchase_order_no' => 'PO001',
                'items' => [
                    [
                        'sku' => $sku1,
                        'batch_no' => 'B001',
                        'quantity' => 100,
                        'unit_cost' => 10.50,
                        'quality_level' => 'A',
                    ],
                ],
                'operator' => 'worker001',
            ],
            [
                'purchase_order_no' => 'PO002',
                'items' => [
                    [
                        'sku' => $sku2,
                        'batch_no' => 'B002',
                        'quantity' => 50,
                        'unit_cost' => 20.00,
                        'quality_level' => 'B',
                    ],
                ],
                'operator' => 'worker001',
            ],
        ];

        $this->inboundService
            ->expects($this->exactly(2))
            ->method('purchaseInbound')
            ->willReturn($this->createMock(StockInbound::class))
        ;

        $results = $this->service->batchProcessInbound($operationsData);

        $this->assertCount(2, $results);
        $this->assertContainsOnlyInstancesOf(StockInbound::class, $results);
    }

    public function testBatchProcessInboundWithPartialFailureShouldReturnSuccessfulResults(): void
    {
        $sku2 = $this->createMock(SKU::class);
        $sku2->method('getId')->willReturn('SKU002');
        $sku2->method('getGtin')->willReturn('1234567890124');
        $sku2->method('getMpn')->willReturn('MPN002');
        $sku2->method('getRemark')->willReturn('Test SKU 2');
        $sku2->method('isValid')->willReturn(true);

        $operationsData = [
            [
                'purchase_order_no' => 'PO001',
                'items' => [],
                'operator' => 'worker001',
            ],
            [
                'purchase_order_no' => 'PO002',
                'items' => [
                    [
                        'sku' => $sku2,
                        'batch_no' => 'B002',
                        'quantity' => 50,
                        'unit_cost' => 20.00,
                        'quality_level' => 'B',
                    ],
                ],
                'operator' => 'worker001',
            ],
        ];

        $this->inboundService
            ->expects($this->exactly(2))
            ->method('purchaseInbound')
            ->willReturnOnConsecutiveCalls(
                $this->throwException(new \InvalidArgumentException('Invalid items')),
                $this->createMock(StockInbound::class)
            )
        ;

        $results = $this->service->batchProcessInbound($operationsData);

        $this->assertCount(1, $results);
        $this->assertContainsOnlyInstancesOf(StockInbound::class, $results);
    }

    public function testGetStockStatsShouldReturnStatistics(): void
    {
        $expectedStats = [
            'total_batches' => 150,
            'total_quantity' => 50000,
            'total_value' => 125000.50,
            'low_stock_count' => 5,
        ];

        $this->stockService
            ->expects($this->once())
            ->method('getStockStats')
            ->willReturn($expectedStats)
        ;

        $result = $this->service->getStockStats();

        $this->assertEquals($expectedStats, $result);
    }
}
