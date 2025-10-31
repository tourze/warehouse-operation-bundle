<?php

namespace Tourze\WarehouseOperationBundle\Service\Extension;

use Tourze\WarehouseOperationBundle\Model\QualityResult;

/**
 * 质检规则接口
 *
 * 定义质检规则的标准接口，支持多种质检策略的扩展。
 */
interface QualityRuleInterface
{
    /**
     * 获取规则名称
     *
     * @return string 规则的唯一名称
     */
    public function getName(): string;

    /**
     * 执行质检
     *
     * @param string $sku 商品SKU
     * @param int $quantity 数量
     * @param array<string, mixed> $context 上下文数据
     * @return QualityResult 质检结果
     */
    public function check(string $sku, int $quantity, array $context = []): QualityResult;

    /**
     * 获取规则优先级
     *
     * @return int 优先级，数字越小优先级越高
     */
    public function getPriority(): int;

    /**
     * 判断规则是否适用于指定商品
     *
     * @param string $sku 商品SKU
     * @return bool 是否适用
     */
    public function supports(string $sku): bool;
}
