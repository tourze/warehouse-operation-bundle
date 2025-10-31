<?php

declare(strict_types=1);

namespace Tourze\WarehouseOperationBundle\Tests\Service\Count;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\WarehouseOperationBundle\Service\Count\CountDataValidatorService;

/**
 * CountDataValidatorService 单元测试
 *
 * 测试盘点数据验证服务的完整功能，包括数据验证、质量检查、错误处理等核心业务逻辑。
 * 验证服务的正确性、验证规则和异常处理。
 * @internal
 */
#[CoversClass(CountDataValidatorService::class)]
#[RunTestsInSeparateProcesses]
class CountDataValidatorServiceTest extends AbstractIntegrationTestCase
{
    private CountDataValidatorService $service;

    protected function onSetUp(): void
    {
        $this->service = parent::getService(CountDataValidatorService::class);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Count\CountDataValidatorService::validateCountData
     */
    public function testValidateCountDataWithValidData(): void
    {
        $countData = [
            'system_quantity' => 100,
            'actual_quantity' => 98,
            'location_code' => 'A1-001',
            'product_info' => ['sku' => 'PROD-001', 'name' => '测试商品'],
        ];

        $result = $this->service->validateCountData($countData);

        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
        $this->assertIsArray($result['errors']);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Count\CountDataValidatorService::validateCountData
     */
    public function testValidateCountDataWithMissingRequiredFields(): void
    {
        $countData = [
            'actual_quantity' => 50,
            // 缺少 system_quantity
        ];

        $result = $this->service->validateCountData($countData);

        $this->assertFalse($result['valid']);
        $errors = $result['errors'];
        $this->assertIsArray($errors);
        $this->assertNotEmpty($errors);
        $this->assertContainsEquals('Missing system_quantity', $errors);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Count\CountDataValidatorService::validateCountData
     */
    public function testValidateCountDataWithInvalidDataTypes(): void
    {
        $countData = [
            'system_quantity' => 'invalid_number',
            'actual_quantity' => -5,
        ];

        $result = $this->service->validateCountData($countData);

        $this->assertFalse($result['valid']);
        $errors = $result['errors'];
        $this->assertIsArray($errors);
        $this->assertNotEmpty($errors);
        $this->assertContainsEquals('system_quantity must be numeric', $errors);
        $this->assertContainsEquals('actual_quantity cannot be negative', $errors);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Count\CountDataValidatorService::validateCountDataQuality
     */
    public function testValidateCountDataQualityWithValidBatch(): void
    {
        $countDataBatch = [
            [
                'system_quantity' => 100,
                'actual_quantity' => 98,
                'location_code' => 'A1-001',
                'product_info' => ['sku' => 'PROD-001'],
            ],
            [
                'system_quantity' => 50,
                'actual_quantity' => 50,
                'location_code' => 'A1-002',
                'product_info' => ['sku' => 'PROD-002'],
            ],
        ];

        $result = $this->service->validateCountDataQuality($countDataBatch);

        $this->assertTrue($result['validation_passed']);
        $this->assertEquals(100, $result['data_quality_score']);
        $this->assertEmpty($result['validation_errors']);
        $this->assertEmpty($result['data_corrections']);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Count\CountDataValidatorService::validateCountDataQuality
     */
    public function testValidateCountDataQualityWithEmptyBatch(): void
    {
        $result = $this->service->validateCountDataQuality([]);

        $this->assertTrue($result['validation_passed']);
        $this->assertEquals(100, $result['data_quality_score']);
        $this->assertEmpty($result['validation_errors']);
        $this->assertEmpty($result['data_corrections']);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Count\CountDataValidatorService::validateCountDataQuality
     */
    public function testValidateCountDataQualityWithInvalidBatch(): void
    {
        $countDataBatch = [
            [
                // 缺少必填字段
                'actual_quantity' => -5, // 负数
                'location_code' => 'A1-001',
            ],
            [
                'system_quantity' => 'invalid', // 非数字
                'actual_quantity' => 1000,
                'location_code' => 'A1-002',
                'product_info' => ['sku' => 'PROD-002'],
            ],
        ];

        $result = $this->service->validateCountDataQuality($countDataBatch);

        $this->assertFalse($result['validation_passed']);
        $this->assertLessThan(100, $result['data_quality_score']);
        $this->assertNotEmpty($result['validation_errors']);

        // 验证具体错误信息
        $errors = $result['validation_errors'];
        $this->assertIsArray($errors);
        $this->assertContainsEquals('Row 0: Missing required field \'system_quantity\'', $errors);
        $this->assertContainsEquals('Row 0: actual_quantity cannot be negative', $errors);
        $this->assertContainsEquals('Row 1: system_quantity must be numeric', $errors);

        // 验证纠正建议
        $corrections = $result['data_corrections'];
        $this->assertIsArray($corrections);
        $this->assertNotEmpty($corrections);
        $this->assertContainsEquals('Row 0: Set actual_quantity to 0', $corrections);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Count\CountDataValidatorService::validateCountDataQuality
     */
    public function testValidateCountDataQualityWithExcessiveDifference(): void
    {
        $countDataBatch = [
            [
                'system_quantity' => 100,
                'actual_quantity' => 400, // 差异400% > 200%阈值
                'location_code' => 'A1-001',
                'product_info' => ['sku' => 'PROD-001'],
            ],
        ];

        $result = $this->service->validateCountDataQuality($countDataBatch);

        $this->assertFalse($result['validation_passed']);
        $this->assertLessThan(100, $result['data_quality_score']);
        $errors = $result['validation_errors'];
        $this->assertIsArray($errors);
        $this->assertContainsEquals('Row 0: Excessive difference (>200%) may indicate data error', $errors);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Count\CountDataValidatorService::validateCountDataQuality
     */
    public function testValidateCountDataQualityWithMultipleIssues(): void
    {
        $countDataBatch = [
            [
                'system_quantity' => 100,
                'actual_quantity' => 98,
                'location_code' => 'A1-001',
                'product_info' => ['sku' => 'PROD-001'],
            ], // 正常数据
            [
                // 多种问题：缺少字段、负数、类型错误
                'system_quantity' => 'abc',
                'actual_quantity' => -10,
                // 缺少 location_code 和 product_info
            ],
        ];

        $result = $this->service->validateCountDataQuality($countDataBatch);

        $this->assertFalse($result['validation_passed']);
        $this->assertLessThan(100, $result['data_quality_score']);
        $errors = $result['validation_errors'];
        $this->assertIsArray($errors);
        $this->assertGreaterThan(3, count($errors));

        // 验证质量分数计算正确（100 - 10*2 - 5 - 3 = 72）
        $expectedScore = 100 - (10 * 2) - 5 - 3; // 缺少2个字段(-20) + 类型错误(-5) + 负数(-3)
        $this->assertEquals($expectedScore, $result['data_quality_score']);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Count\CountDataValidatorService::validateCountDataQuality
     */
    public function testValidateCountDataQualityWithBoundaryValues(): void
    {
        $countDataBatch = [
            [
                'system_quantity' => 0,
                'actual_quantity' => 0,
                'location_code' => 'A1-001',
                'product_info' => ['sku' => 'PROD-001'],
            ],
            [
                'system_quantity' => 1,
                'actual_quantity' => 0,
                'location_code' => 'A1-002',
                'product_info' => ['sku' => 'PROD-002'],
            ],
        ];

        $result = $this->service->validateCountDataQuality($countDataBatch);

        // 边界值应该通过验证
        $this->assertTrue($result['validation_passed']);
        $this->assertEquals(100, $result['data_quality_score']);
        $this->assertEmpty($result['validation_errors']);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Count\CountDataValidatorService::validateCountDataQuality
     */
    public function testValidateCountDataQualityWithCustomValidationRules(): void
    {
        $countDataBatch = [
            [
                'system_quantity' => 100,
                'actual_quantity' => 98,
                'location_code' => 'A1-001',
                'product_info' => ['sku' => 'PROD-001'],
            ],
        ];

        $validationRules = [
            'max_discrepancy_ratio' => 0.01, // 1%
            'required_fields' => ['system_quantity', 'actual_quantity', 'location_code'],
        ];

        $result = $this->service->validateCountDataQuality($countDataBatch, $validationRules);

        // 即使传入自定义规则，服务也应该正常运行（当前实现忽略这些规则）
        $this->assertTrue($result['validation_passed']);
        $this->assertEquals(100, $result['data_quality_score']);
    }

    public function testServiceConstructorAndBasicFunctionality(): void
    {
        // 验证服务可以正确实例化
        $this->assertInstanceOf(CountDataValidatorService::class, $this->service);

        // 验证基本功能工作正常
        $result = $this->service->validateCountData(['system_quantity' => 10, 'actual_quantity' => 10]);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('valid', $result);
        $this->assertArrayHasKey('errors', $result);
    }

    /**
     * 测试数据质量分数计算的准确性
     */
    public function testQualityScoreCalculation(): void
    {
        $countDataBatch = [
            [
                // 缺少1个必填字段 (-10分)
                'system_quantity' => 100,
                'actual_quantity' => 98,
                'location_code' => 'A1-001',
                // 缺少 product_info
            ],
            [
                // 类型错误 (-5分)
                'system_quantity' => 'invalid',
                'actual_quantity' => 50,
                'location_code' => 'A1-002',
                'product_info' => ['sku' => 'PROD-002'],
            ],
        ];

        $result = $this->service->validateCountDataQuality($countDataBatch);

        $expectedScore = 100 - 10 - 5; // 85分
        $this->assertEquals($expectedScore, $result['data_quality_score']);
        $this->assertFalse($result['validation_passed']);
    }
}
