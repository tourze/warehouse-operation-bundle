<?php

namespace Tourze\WarehouseOperationBundle\Tests\Service\Quality\Processor;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\WarehouseOperationBundle\Service\Quality\Processor\QualityResultBuilder;

/**
 * QualityResultBuilder 单元测试
 *
 * @internal
 */
#[CoversClass(QualityResultBuilder::class)]
class QualityResultBuilderTest extends TestCase
{
    private QualityResultBuilder $builder;

    protected function setUp(): void
    {
        $this->builder = new QualityResultBuilder();
    }

    /**
     * 测试服务正确创建
     */
    public function testServiceCreation(): void
    {
        $this->assertInstanceOf(QualityResultBuilder::class, $this->builder);
    }

    /**
     * 测试构建结果数据 - 通过场景
     */
    public function testBuildResultDataPass(): void
    {
        $qualityResult = [
            'overall_result' => 'pass',
            'total_weight' => 100,
            'total_score' => 95,
            'check_results' => [
                'visual_check' => ['result' => 'pass', 'score' => 100],
                'quantity_check' => ['result' => 'pass', 'score' => 90],
            ],
            'all_defects' => [],
        ];

        $checkData = [
            'inspector_notes' => 'All items are in good condition',
            'photos' => ['photo1.jpg', 'photo2.jpg'],
        ];

        $result = $this->builder->buildResultData($qualityResult, $checkData, 123);

        $this->assertIsArray($result);
        $this->assertEquals('pass', $result['overall_result']);
        $this->assertEquals(0.95, $result['quality_score']);
        $this->assertIsArray($result['check_results']);
        $this->assertIsArray($result['defects']);
        $this->assertIsArray($result['recommendations']);
        $this->assertEquals('All items are in good condition', $result['inspector_notes']);
        $this->assertEquals(['photo1.jpg', 'photo2.jpg'], $result['photos']);
        $this->assertEquals(123, $result['inspector_id']);
        $this->assertInstanceOf(\DateTimeImmutable::class, $result['checked_at']);

        // 通过场景应该有默认的建议
        $this->assertContainsEquals('商品质检合格，可以正常入库', $result['recommendations']);
    }

    /**
     * 测试构建结果数据 - 失败场景
     */
    public function testBuildResultDataFail(): void
    {
        $qualityResult = [
            'overall_result' => 'fail',
            'total_weight' => 100,
            'total_score' => 60,
            'check_results' => [
                'visual_check' => ['result' => 'fail', 'score' => 50],
            ],
            'all_defects' => [
                ['type' => 'damage', 'description' => 'Product is damaged'],
                ['type' => 'expired', 'description' => 'Product is expired'],
            ],
        ];

        $checkData = [
            'inspector_notes' => 'Products have quality issues',
        ];

        $result = $this->builder->buildResultData($qualityResult, $checkData, 456);

        $this->assertEquals('fail', $result['overall_result']);
        $this->assertEquals(0.6, $result['quality_score']);
        $defects = $result['defects'];
        $this->assertIsArray($defects);
        $this->assertCount(2, $defects);

        // 失败场景应该有相关建议
        $recommendations = $result['recommendations'];
        $this->assertIsArray($recommendations);
        $this->assertNotEmpty($recommendations);
        $this->assertContainsEquals('检查商品损坏程度，考虑降价销售或退货', $recommendations);
        $this->assertContainsEquals('立即隔离过期商品，联系供应商处理', $recommendations);
    }

    /**
     * 测试构建结果数据 - 条件性通过场景
     */
    public function testBuildResultDataConditional(): void
    {
        $qualityResult = [
            'overall_result' => 'conditional',
            'total_weight' => 100,
            'total_score' => 75,
            'check_results' => [],
            'all_defects' => [
                ['type' => 'quantity_mismatch', 'description' => 'Quantity mismatch'],
            ],
        ];

        $checkData = [];

        $result = $this->builder->buildResultData($qualityResult, $checkData, null);

        $this->assertEquals('conditional', $result['overall_result']);
        $this->assertEquals(0.75, $result['quality_score']);
        $this->assertNull($result['inspector_id']);

        // 条件性通过应该有对应的建议
        $recommendations = $result['recommendations'];
        $this->assertIsArray($recommendations);
        $this->assertContainsEquals('核实数量差异，更新库存记录', $recommendations);
    }

    /**
     * 测试构建结果数据 - 空输入
     */
    public function testBuildResultDataEmptyInput(): void
    {
        $qualityResult = [];
        $checkData = [];

        $result = $this->builder->buildResultData($qualityResult, $checkData, null);

        $this->assertEquals('fail', $result['overall_result']);
        $this->assertEquals(0, $result['quality_score']);
        $this->assertIsArray($result['check_results']);
        $this->assertIsArray($result['defects']);
        $this->assertIsArray($result['recommendations']);
        $this->assertEquals('', $result['inspector_notes']);
        $this->assertEquals([], $result['photos']);
        $this->assertNull($result['inspector_id']);
    }

    /**
     * 测试构建结果数据 - 无效缺陷数据
     */
    public function testBuildResultDataInvalidDefects(): void
    {
        $qualityResult = [
            'overall_result' => 'fail',
            'total_weight' => 0,
            'total_score' => 0,
            'check_results' => [],
            'all_defects' => [
                'invalid_string',
                null,
                ['type' => 'damage', 'description' => 'Valid defect'],
                123,
            ],
        ];

        $checkData = [];

        $result = $this->builder->buildResultData($qualityResult, $checkData, null);

        // 基本检查：确保方法返回了结果
        $this->assertNotNull($result, 'buildResultData should not return null');
        $this->assertIsArray($result);

        // 调试：打印结果结构
        if (!isset($result['defects'])) {
            self::fail('Result does not contain defects key: ' . json_encode(array_keys($result)));
        }

        // 只有有效的数组缺陷应该被包含
        $this->assertIsArray($result['defects'], 'Defects should be an array');
        $this->assertCount(1, $result['defects']);
        $this->assertEquals(['type' => 'damage', 'description' => 'Valid defect'], $result['defects'][0]);
    }

    /**
     * 测试构建结果数据 - 未知缺陷类型
     */
    public function testBuildResultDataUnknownDefectType(): void
    {
        $qualityResult = [
            'overall_result' => 'fail',
            'total_weight' => 100,
            'total_score' => 50,
            'check_results' => [],
            'all_defects' => [
                ['type' => 'unknown_type', 'description' => 'Unknown defect'],
            ],
        ];

        $checkData = [];

        $result = $this->builder->buildResultData($qualityResult, $checkData, null);

        // 未知缺陷类型应该返回默认建议
        $this->assertEquals('fail', $result['overall_result']);
        $this->assertEquals(0.5, $result['quality_score']);
        $recommendations = $result['recommendations'];
        $this->assertIsArray($recommendations);
        $this->assertContainsEquals('商品存在质量问题，建议隔离处理', $recommendations);
    }

    /**
     * 测试构建结果数据 - 权重为0的情况
     */
    public function testBuildResultDataZeroWeight(): void
    {
        $qualityResult = [
            'overall_result' => 'pass',
            'total_weight' => 0,
            'total_score' => 100,
            'check_results' => [],
            'all_defects' => [],
        ];

        $checkData = [];

        $result = $this->builder->buildResultData($qualityResult, $checkData, null);

        // 权重为0时分数应该为0
        $this->assertEquals(0, $result['quality_score']);
    }

    /**
     * 测试构建结果数据 - 数值类型转换
     */
    public function testBuildResultDataTypeConversion(): void
    {
        $qualityResult = [
            'overall_result' => 'pass',
            'total_weight' => '100', // 字符串
            'total_score' => '95.5', // 字符串
            'check_results' => [],
            'all_defects' => [],
        ];

        $checkData = [];

        $result = $this->builder->buildResultData($qualityResult, $checkData, null);

        // 应该正确转换为浮点数
        $this->assertEquals(0.96, $result['quality_score']); // 95.5 / 100 = 0.96
    }
}
