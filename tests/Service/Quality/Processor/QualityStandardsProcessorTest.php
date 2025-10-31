<?php

namespace Tourze\WarehouseOperationBundle\Tests\Service\Quality\Processor;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Tourze\WarehouseOperationBundle\Entity\QualityStandard;
use Tourze\WarehouseOperationBundle\Service\Quality\Processor\QualityStandardsProcessor;
use Tourze\WarehouseOperationBundle\Service\Quality\Validator\QualityCheckValidatorRegistryInterface;

/**
 * QualityStandardsProcessor 单元测试
 *
 * @internal
 */
#[CoversClass(QualityStandardsProcessor::class)]
class QualityStandardsProcessorTest extends TestCase
{
    private QualityStandardsProcessor $processor;

    /** @var QualityCheckValidatorRegistryInterface&MockObject */
    private QualityCheckValidatorRegistryInterface $validatorRegistry;

    protected function setUp(): void
    {
        self::$idCounter = 0;
        $this->validatorRegistry = $this->createMock(QualityCheckValidatorRegistryInterface::class);
        $this->processor = new QualityStandardsProcessor($this->validatorRegistry);
    }

    /**
     * 测试服务正确创建
     */
    public function testServiceCreation(): void
    {
        $this->assertInstanceOf(QualityStandardsProcessor::class, $this->processor);
    }

    /**
     * 测试处理质检标准 - 基本功能
     */
    public function testProcessStandardsBasic(): void
    {
        $standard = $this->createQualityStandard('electronics', [
            'visual_check' => [
                'enabled' => true,
                'weight' => 50,
                'criteria' => ['allowed_conditions' => ['good', 'perfect']],
            ],
        ]);

        $mockValidator = $this->createMockValidator();
        $this->validatorRegistry->expects($this->once())
            ->method('getValidator')
            ->with('visual_check')
            ->willReturn($mockValidator)
        ;

        $mockValidator->expects($this->once())
            ->method('validate')
            ->willReturn([
                'result' => 'pass',
                'score' => 100,
                'defects' => [],
                'details' => [],
            ])
        ;

        $checkData = ['visual_check' => 'good'];
        $options = [];

        $result = $this->processor->processStandards([$standard], $checkData, $options);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('check_results', $result);
        $this->assertArrayHasKey('all_defects', $result);
        $this->assertArrayHasKey('total_score', $result);
        $this->assertArrayHasKey('total_weight', $result);
        $this->assertArrayHasKey('overall_result', $result);
    }

    /**
     * 测试处理质检标准 - 多个标准
     */
    public function testProcessStandardsMultiple(): void
    {
        $standard1 = $this->createQualityStandard('electronics', [
            'visual_check' => [
                'enabled' => true,
                'weight' => 30,
                'criteria' => [],
            ],
        ]);

        $standard2 = $this->createQualityStandard('electronics', [
            'quantity_check' => [
                'enabled' => true,
                'weight' => 70,
                'criteria' => [],
            ],
        ]);

        $mockValidator = $this->createMockValidator();
        $this->validatorRegistry->expects($this->exactly(2))
            ->method('getValidator')
            ->willReturn($mockValidator)
        ;

        $mockValidator->expects($this->exactly(2))
            ->method('validate')
            ->willReturn([
                'result' => 'pass',
                'score' => 100,
                'defects' => [],
                'details' => [],
            ])
        ;

        $checkData = [
            'visual_check' => 'good',
            'quantity_check' => ['expected' => 10, 'actual' => 10],
        ];
        $options = [];

        $result = $this->processor->processStandards([$standard1, $standard2], $checkData, $options);

        $this->assertIsArray($result);
        $this->assertIsArray($result['check_results']);
        $this->assertCount(2, $result['check_results']);
    }

    /**
     * 测试处理质检标准 - 空标准列表
     */
    public function testProcessStandardsEmptyList(): void
    {
        $checkData = [];
        $options = [];

        $result = $this->processor->processStandards([], $checkData, $options);

        $this->assertEquals('pass', $result['overall_result']);
        $this->assertEquals(0, $result['total_score']);
        $this->assertEquals(0, $result['total_weight']);
        $this->assertEmpty($result['check_results']);
        $this->assertEmpty($result['all_defects']);
    }

    /**
     * 测试处理质检标准 - 严格模式
     */
    public function testProcessStandardsStrictMode(): void
    {
        $standard = $this->createQualityStandard('electronics', [
            'visual_check' => [
                'enabled' => true,
                'weight' => 50,
                'criteria' => [],
            ],
        ]);

        $mockValidator = $this->createMockValidator();
        $this->validatorRegistry->expects($this->once())
            ->method('getValidator')
            ->willReturn($mockValidator)
        ;

        $mockValidator->expects($this->once())
            ->method('validate')
            ->willReturn([
                'result' => 'conditional',
                'score' => 80,
                'defects' => [['type' => 'minor_issue', 'description' => 'Minor issue']],
                'details' => [],
            ])
        ;

        $checkData = ['visual_check' => 'acceptable'];
        $options = ['strict_mode' => true];

        $result = $this->processor->processStandards([$standard], $checkData, $options);

        $this->assertEquals('conditional', $result['overall_result']);
    }

    /**
     * 测试处理质检标准 - 跳过可选检查项
     */
    public function testProcessStandardsSkipOptional(): void
    {
        $standard = $this->createQualityStandard('electronics', [
            'required_check' => [
                'enabled' => true,
                'required' => true,
                'weight' => 100,
                'criteria' => [],
            ],
            'optional_check' => [
                'enabled' => true,
                'required' => false,
                'weight' => 50,
                'criteria' => [],
            ],
        ]);

        $mockValidator = $this->createMockValidator();
        $this->validatorRegistry->expects($this->once()) // 只调用一次，因为可选的跳过了
            ->method('getValidator')
            ->willReturn($mockValidator)
        ;

        $mockValidator->expects($this->once())
            ->method('validate')
            ->willReturn([
                'result' => 'pass',
                'score' => 100,
                'defects' => [],
                'details' => [],
            ])
        ;

        $checkData = [
            'required_check' => 'good',
            'optional_check' => 'optional_value',
        ];
        $options = ['skip_optional' => true];

        $result = $this->processor->processStandards([$standard], $checkData, $options);

        // 应该只处理必需的检查项
        $standardId = $standard->getId();
        $this->assertIsArray($result['check_results']);
        $this->assertArrayHasKey($standardId, $result['check_results']);
        $standardResult = $result['check_results'][$standardId];
        $this->assertIsArray($standardResult);
        $this->assertArrayHasKey('check_results', $standardResult);
        $checkResults = $standardResult['check_results'];
        $this->assertIsArray($checkResults);
        $this->assertArrayHasKey('required_check', $checkResults);
        $this->assertArrayNotHasKey('optional_check', $checkResults);
    }

    /**
     * 测试处理质检标准 - 缺失检查数据
     */
    public function testProcessStandardsMissingCheckData(): void
    {
        $standard = $this->createQualityStandard('electronics', [
            'visual_check' => [
                'enabled' => true,
                'weight' => 50,
                'criteria' => [],
            ],
        ]);

        $checkData = []; // 缺少 visual_check 数据
        $options = [];

        $result = $this->processor->processStandards([$standard], $checkData, $options);

        $this->assertEquals('fail', $result['overall_result']);
        $this->assertIsArray($result['all_defects']);
        $this->assertNotEmpty($result['all_defects']);
        $this->assertContainsEquals(['type' => 'visual_check', 'description' => '缺少检查数据'], $result['all_defects']);
    }

    /**
     * 测试处理质检标准 - 禁用的检查项
     */
    public function testProcessStandardsDisabledCheckItem(): void
    {
        $standard = $this->createQualityStandard('electronics', [
            'disabled_check' => [
                'enabled' => false,
                'weight' => 50,
                'criteria' => [],
            ],
        ]);

        $checkData = ['disabled_check' => 'value'];
        $options = [];

        // 验证器不应该被调用，因为检查项被禁用了
        $this->validatorRegistry->expects($this->never())
            ->method('getValidator')
        ;

        $result = $this->processor->processStandards([$standard], $checkData, $options);

        $this->assertEquals('pass', $result['overall_result']);
        $standardId = $standard->getId();
        $this->assertIsArray($result['check_results']);
        $this->assertArrayHasKey($standardId, $result['check_results']);
        $standardResult = $result['check_results'][$standardId];
        $this->assertIsArray($standardResult);
        $this->assertArrayHasKey('check_results', $standardResult);
        $checkResults = $standardResult['check_results'];
        $this->assertIsArray($checkResults);
        $this->assertArrayNotHasKey('disabled_check', $checkResults);
    }

    /**
     * 测试处理质检标准 - 包含缺陷的结果
     */
    public function testProcessStandardsWithDefects(): void
    {
        $standard = $this->createQualityStandard('electronics', [
            'visual_check' => [
                'enabled' => true,
                'weight' => 50,
                'criteria' => [],
            ],
        ]);

        $mockValidator = $this->createMockValidator();
        $this->validatorRegistry->expects($this->once())
            ->method('getValidator')
            ->willReturn($mockValidator)
        ;

        $mockValidator->expects($this->once())
            ->method('validate')
            ->willReturn([
                'result' => 'fail',
                'score' => 60,
                'defects' => [
                    ['type' => 'damage', 'description' => 'Product damaged', 'critical' => true],
                    ['type' => 'scratch', 'description' => 'Minor scratch', 'critical' => false],
                ],
                'details' => [],
            ])
        ;

        $checkData = ['visual_check' => 'damaged'];
        $options = [];

        $result = $this->processor->processStandards([$standard], $checkData, $options);

        $this->assertEquals('fail', $result['overall_result']);
        $this->assertIsArray($result['all_defects']);
        $this->assertNotEmpty($result['all_defects']);
        $this->assertCount(2, $result['all_defects']);
        // 计算逻辑：
        // 1. checkItem level: score(60) * weight(50) = 3000, total_weight = 50
        // 2. standard level: standard_score = 3000 / 50 = 60
        // 3. overall level: standard_score(60) * priority(80) = 4800, total_weight = 80
        $this->assertEquals(4800.0, $result['total_score']);
        $this->assertEquals(80, $result['total_weight']);
    }

    /**
     * 测试处理质检标准 - 无效检查项配置
     */
    public function testProcessStandardsInvalidCheckItem(): void
    {
        $standard = $this->createQualityStandard('electronics', [
            'invalid_check' => 'invalid_config', // 不是数组
        ]);

        $checkData = ['invalid_check' => 'value'];
        $options = [];

        $this->validatorRegistry->expects($this->never())
            ->method('getValidator')
        ;

        $result = $this->processor->processStandards([$standard], $checkData, $options);

        // 无效配置应该被跳过
        $this->assertEquals('pass', $result['overall_result']);
        $standardId = $standard->getId();
        $this->assertIsArray($result['check_results']);
        $this->assertArrayHasKey($standardId, $result['check_results']);
        $standardResult = $result['check_results'][$standardId];
        $this->assertIsArray($standardResult);
        $this->assertArrayHasKey('check_results', $standardResult);
        $checkResults = $standardResult['check_results'];
        $this->assertIsArray($checkResults);
        $this->assertArrayNotHasKey('invalid_check', $checkResults);
    }

    /**
     * 创建模拟的验证器
     *
     * @return MockObject&\Tourze\WarehouseOperationBundle\Service\Quality\Validator\QualityCheckValidatorInterface
     */
    private function createMockValidator(): MockObject
    {
        return $this->createMock(\Tourze\WarehouseOperationBundle\Service\Quality\Validator\QualityCheckValidatorInterface::class);
    }

    private static int $idCounter = 0;

    /**
     * 创建测试用的质量标准
     *
     * @param array<string, mixed> $checkItems
     */
    private function createQualityStandard(string $category, array $checkItems = []): QualityStandard
    {
        $standard = new QualityStandard();
        $standard->setName("Test Standard for {$category}");
        $standard->setProductCategory($category);
        $standard->setPriority(80);
        $standard->setIsActive(true);
        $standard->setCheckItems($checkItems);

        // 使用反射设置私有ID属性
        $reflection = new \ReflectionClass($standard);
        $idProperty = $reflection->getProperty('id');
        $idProperty->setAccessible(true);
        $idProperty->setValue($standard, ++self::$idCounter);

        return $standard;
    }
}
