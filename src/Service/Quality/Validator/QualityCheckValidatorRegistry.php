<?php

declare(strict_types=1);

namespace Tourze\WarehouseOperationBundle\Service\Quality\Validator;

/**
 * 质检验证器注册表
 *
 * 管理各种质检验证器，使用策略模式降低主服务的认知复杂度。
 * 按需返回对应的验证器实例。
 */
final class QualityCheckValidatorRegistry implements QualityCheckValidatorRegistryInterface
{
    /** @var array<string, QualityCheckValidatorInterface> */
    private array $validators = [];

    /** @var QualityCheckValidatorInterface */
    private QualityCheckValidatorInterface $defaultValidator;

    public function __construct()
    {
        $this->registerDefaultValidators();
        $this->defaultValidator = new GenericCheckValidator();
    }

    /**
     * 注册默认验证器
     */
    private function registerDefaultValidators(): void
    {
        $this->register(new VisualCheckValidator());
        $this->register(new QuantityCheckValidator());
        $this->register(new GenericCheckValidator());
    }

    /**
     * 注册验证器
     */
    public function register(QualityCheckValidatorInterface $validator): void
    {
        $this->validators[$validator->getSupportedCheckType()] = $validator;
    }

    /**
     * 获取验证器
     */
    public function getValidator(string $checkType): QualityCheckValidatorInterface
    {
        return $this->validators[$checkType] ?? $this->defaultValidator;
    }

    /**
     * 检查是否支持某种检查类型
     */
    public function supports(string $checkType): bool
    {
        return isset($this->validators[$checkType]);
    }
}
