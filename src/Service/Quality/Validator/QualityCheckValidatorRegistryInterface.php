<?php

declare(strict_types=1);

namespace Tourze\WarehouseOperationBundle\Service\Quality\Validator;

/**
 * 质检验证器注册表接口
 */
interface QualityCheckValidatorRegistryInterface
{
    /**
     * 注册验证器
     */
    public function register(QualityCheckValidatorInterface $validator): void;

    /**
     * 获取验证器
     */
    public function getValidator(string $checkType): QualityCheckValidatorInterface;

    /**
     * 检查是否支持某种检查类型
     */
    public function supports(string $checkType): bool;
}
