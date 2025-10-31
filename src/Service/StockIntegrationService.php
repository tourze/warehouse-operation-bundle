<?php

declare(strict_types=1);

namespace Tourze\WarehouseOperationBundle\Service;

use Tourze\ProductServiceContracts\SKU;
use Tourze\StockManageBundle\Entity\StockInbound;
use Tourze\StockManageBundle\Entity\StockOutbound;
use Tourze\StockManageBundle\Model\StockSummary;
use Tourze\StockManageBundle\Service\InboundServiceInterface;
use Tourze\StockManageBundle\Service\OutboundServiceInterface;
use Tourze\StockManageBundle\Service\StockServiceInterface;
use Tourze\WarehouseOperationBundle\Exception\StockIntegrationException;

/**
 * 库存集成服务
 *
 * 提供stock-manage-bundle集成操作的统一接口。
 * 处理库存操作、错误处理和批处理功能。
 */
class StockIntegrationService
{
    public function __construct(
        private readonly StockServiceInterface $stockService,
        private readonly InboundServiceInterface $inboundService,
        private readonly OutboundServiceInterface $outboundService,
    ) {
    }

    /**
     * 处理库存入库操作
     *
     * @param array{
     *     purchase_order_no: string,
     *     items: array<array{
     *         sku: SKU,
     *         batch_no: string,
     *         quantity: int,
     *         unit_cost: float,
     *         quality_level: string,
     *         production_date?: \DateTimeInterface,
     *         expiry_date?: \DateTimeInterface
     *     }>,
     *     operator: string,
     *     location_id?: string,
     *     notes?: string
     * } $data
     *
     * @throws StockIntegrationException
     */
    public function processInbound(array $data): StockInbound
    {
        try {
            return $this->inboundService->purchaseInbound($data);
        } catch (\Exception $e) {
            throw new StockIntegrationException(sprintf('Stock inbound operation failed: %s', $e->getMessage()), 0, $e);
        }
    }

    /**
     * 处理库存出库操作
     *
     * @param array{
     *     order_no: string,
     *     items: array<array{
     *         sku: SKU,
     *         quantity: int,
     *         allocation_strategy?: string
     *     }>,
     *     operator: string,
     *     location_id?: string,
     *     notes?: string
     * } $data
     *
     * @throws StockIntegrationException
     */
    public function processOutbound(array $data): StockOutbound
    {
        try {
            return $this->outboundService->salesOutbound($data);
        } catch (\Exception $e) {
            throw new StockIntegrationException(sprintf('Stock outbound operation failed: %s', $e->getMessage()), 0, $e);
        }
    }

    /**
     * 检查指定SKU和数量的库存可用性
     *
     * @param array<string, mixed> $criteria
     *
     * @throws StockIntegrationException
     */
    public function checkStockAvailability(SKU $sku, int $quantity, array $criteria = []): bool
    {
        try {
            return $this->stockService->checkStockAvailability($sku, $quantity, $criteria);
        } catch (\Exception $e) {
            throw new StockIntegrationException(sprintf('Stock availability check failed: %s', $e->getMessage()), 0, $e);
        }
    }

    /**
     * 获取可用库存摘要
     *
     * @param array<string, mixed> $criteria
     *
     * @throws StockIntegrationException
     */
    public function getAvailableStock(SKU $sku, array $criteria = []): StockSummary
    {
        try {
            /** @var array{location_id?: string, quality_level?: string, exclude_expired?: bool, include_reserved?: bool} $typedCriteria */
            $typedCriteria = array_filter([
                'location_id' => is_string($criteria['location_id'] ?? null) ? $criteria['location_id'] : null,
                'quality_level' => is_string($criteria['quality_level'] ?? null) ? $criteria['quality_level'] : null,
                'exclude_expired' => is_bool($criteria['exclude_expired'] ?? null) ? $criteria['exclude_expired'] : null,
                'include_reserved' => is_bool($criteria['include_reserved'] ?? null) ? $criteria['include_reserved'] : null,
            ], fn ($v) => null !== $v);

            return $this->stockService->getAvailableStock($sku, $typedCriteria);
        } catch (\Exception $e) {
            throw new StockIntegrationException(sprintf('Get available stock failed: %s', $e->getMessage()), 0, $e);
        }
    }

    /**
     * 处理调拨入库操作
     *
     * @param array{
     *     transfer_no: string,
     *     from_location: string,
     *     items: array<array{
     *         batch_id: string,
     *         quantity: int
     *     }>,
     *     operator: string,
     *     location_id?: string,
     *     notes?: string
     * } $data
     *
     * @throws StockIntegrationException
     */
    public function processTransferInbound(array $data): StockInbound
    {
        try {
            return $this->inboundService->transferInbound($data);
        } catch (\Exception $e) {
            throw new StockIntegrationException(sprintf('Transfer inbound operation failed: %s', $e->getMessage()), 0, $e);
        }
    }

    /**
     * 处理调拨出库操作
     *
     * @param array{
     *     transfer_no: string,
     *     to_location: string,
     *     items: array<array{
     *         batch_id: string,
     *         quantity: int
     *     }>,
     *     operator: string,
     *     location_id?: string,
     *     notes?: string
     * } $data
     *
     * @throws StockIntegrationException
     */
    public function processTransferOutbound(array $data): StockOutbound
    {
        try {
            return $this->outboundService->transferOutbound($data);
        } catch (\Exception $e) {
            throw new StockIntegrationException(sprintf('Transfer outbound operation failed: %s', $e->getMessage()), 0, $e);
        }
    }

    /**
     * 处理损耗出库操作
     *
     * @param array{
     *     damage_no: string,
     *     items: array<array{
     *         batch_id: string,
     *         quantity: int,
     *         reason: string
     *     }>,
     *     operator: string,
     *     location_id?: string,
     *     notes?: string
     * } $data
     *
     * @throws StockIntegrationException
     */
    public function processDamageOutbound(array $data): StockOutbound
    {
        try {
            return $this->outboundService->damageOutbound($data);
        } catch (\Exception $e) {
            throw new StockIntegrationException(sprintf('Damage outbound operation failed: %s', $e->getMessage()), 0, $e);
        }
    }

    /**
     * 批量处理多个入库操作
     *
     * @param array<array{
     *     purchase_order_no: string,
     *     items: array<array{
     *         sku: SKU,
     *         batch_no: string,
     *         quantity: int,
     *         unit_cost: float,
     *         quality_level: string,
     *         production_date?: \DateTimeInterface,
     *         expiry_date?: \DateTimeInterface
     *     }>,
     *     operator: string,
     *     location_id?: string,
     *     notes?: string
     * }> $operationsData
     *
     * @return array<StockInbound> Successful results only
     */
    public function batchProcessInbound(array $operationsData): array
    {
        /** @var array<StockInbound> $results */
        $results = [];

        foreach ($operationsData as $data) {
            try {
                $results[] = $this->inboundService->purchaseInbound($data);
            } catch (\Exception) {
                // Skip failed operations in batch processing
                continue;
            }
        }

        return $results;
    }

    /**
     * 获取库存统计信息
     *
     * @return array<string, mixed>
     *
     * @throws StockIntegrationException
     */
    public function getStockStats(): array
    {
        try {
            return $this->stockService->getStockStats();
        } catch (\Exception $e) {
            throw new StockIntegrationException(sprintf('Get stock statistics failed: %s', $e->getMessage()), 0, $e);
        }
    }
}
