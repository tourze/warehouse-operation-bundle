<?php

declare(strict_types=1);

namespace Tourze\WarehouseOperationBundle\Service\Quality\Validator;

/**
 * 质检验证器接口
 *
 * 使用策略模式将不同类型的质检验证逻辑分离到专门的类中，
 * 降低主服务的认知复杂度。
 */
interface QualityCheckValidatorInterface
{
    /**
     * 执行质检验证
     *
     * @param mixed $checkValue
     * @param array<string, mixed> $criteria
     * @param bool $strictMode
     * @return array<string, mixed>
     */
    public function validate(mixed $checkValue, array $criteria, bool $strictMode): array;

    /**
     * 获取验证器支持的检查类型
     */
    public function getSupportedCheckType(): string;
}
