<?php

declare(strict_types=1);

namespace Tourze\WarehouseOperationBundle\Tests\Service\Quality;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tourze\WarehouseOperationBundle\Entity\QualityTask;
use Tourze\WarehouseOperationBundle\Service\Quality\QualitySampleInspectionService;

/**
 * QualitySampleInspectionService 单元测试
 *
 * 测试质检样品抽检服务的完整功能，包括样品抽检执行、抽样方法计算、样本位置生成等核心业务逻辑。
 * 验证服务的正确性、抽样策略和边界处理。
 *
 * @internal
 */
#[CoversClass(QualitySampleInspectionService::class)]
final class QualitySampleInspectionServiceTest extends TestCase
{
    private QualitySampleInspectionService $service;

    protected function setUp(): void
    {
        $this->service = new QualitySampleInspectionService();
    }

    protected function tearDown(): void
    {
        unset($this->service);
    }

    /**
     * 测试基本的样品抽检执行功能
     */
    public function testExecuteSampleInspectionBasic(): void
    {
        $batchInfo = [
            'batch_id' => 'BATCH001',
            'total_quantity' => 100,
            'product_info' => [
                'sku' => 'SKU001',
                'name' => 'Test Product',
            ],
        ];

        $samplingRules = [
            'sampling_method' => 'random',
            'sample_size' => 10,
        ];

        $result = $this->service->executeSampleInspection($batchInfo, $samplingRules);

        // 验证返回结构
        $this->assertArrayHasKey('sample_tasks', $result);
        $this->assertArrayHasKey('sampling_plan', $result);
        $this->assertArrayHasKey('expected_completion', $result);

        // 验证样品任务
        $this->assertIsArray($result['sample_tasks']);
        $this->assertCount(10, $result['sample_tasks']);
        foreach ($result['sample_tasks'] as $task) {
            $this->assertInstanceOf(QualityTask::class, $task);
        }

        // 验证抽样计划
        $this->assertIsArray($result['sampling_plan']);
        $samplingPlan = $result['sampling_plan'];
        $this->assertArrayHasKey('batch_id', $samplingPlan);
        $this->assertArrayHasKey('total_quantity', $samplingPlan);
        $this->assertArrayHasKey('sample_size', $samplingPlan);
        $this->assertArrayHasKey('sampling_method', $samplingPlan);
        $this->assertArrayHasKey('sample_positions', $samplingPlan);
        $this->assertEquals('BATCH001', $samplingPlan['batch_id']);
        $this->assertEquals(100, $samplingPlan['total_quantity']);
        $this->assertEquals(10, $samplingPlan['sample_size']);
        $this->assertEquals('random', $samplingPlan['sampling_method']);
        $this->assertIsArray($samplingPlan['sample_positions']);
        $this->assertCount(10, $samplingPlan['sample_positions']);

        // 验证预计完成时间
        $this->assertInstanceOf(\DateTimeImmutable::class, $result['expected_completion']);
    }

    /**
     * 测试随机抽样方法
     */
    public function testExecuteSampleInspectionWithRandomMethod(): void
    {
        $batchInfo = [
            'batch_id' => 'BATCH002',
            'total_quantity' => 50,
            'product_info' => ['sku' => 'SKU002'],
        ];

        $samplingRules = [
            'sampling_method' => 'random',
            'sample_size' => 5,
        ];

        $result = $this->service->executeSampleInspection($batchInfo, $samplingRules);

        self::assertIsArray($result['sampling_plan']);
        /** @var array<string, mixed> $samplingPlan */
        $samplingPlan = $result['sampling_plan'];
        self::assertIsArray($samplingPlan['sample_positions']);
        /** @var array<mixed> $positions */
        $positions = $samplingPlan['sample_positions'];

        // 验证位置数量
        $this->assertCount(5, $positions);

        // 验证位置都在有效范围内
        self::assertIsIterable($positions);
        foreach ($positions as $position) {
            $this->assertGreaterThanOrEqual(1, $position);
            $this->assertLessThanOrEqual(50, $position);
        }

        // 验证任务数据包含必要信息
        self::assertIsArray($result['sample_tasks']);
        self::assertIsIterable($result['sample_tasks']);
        foreach ($result['sample_tasks'] as $task) {
            self::assertInstanceOf(QualityTask::class, $task);
            $taskData = $task->getData();
            self::assertIsArray($taskData);
            /** @var array<string, mixed> $taskData */
            $this->assertEquals('BATCH002', $taskData['batch_id']);
            $this->assertArrayHasKey('sample_position', $taskData);
            $this->assertEquals('random', $taskData['sampling_method']);
            $this->assertEquals(['sku' => 'SKU002'], $taskData['product_info']);
        }
    }

    /**
     * 测试系统抽样方法
     */
    public function testExecuteSampleInspectionWithSystematicMethod(): void
    {
        $batchInfo = [
            'batch_id' => 'BATCH003',
            'total_quantity' => 100,
            'product_info' => ['sku' => 'SKU003'],
        ];

        $samplingRules = [
            'sampling_method' => 'systematic',
            'sample_size' => 10,
        ];

        $result = $this->service->executeSampleInspection($batchInfo, $samplingRules);

        self::assertIsArray($result['sampling_plan']);
        /** @var array<string, mixed> $samplingPlan */
        $samplingPlan = $result['sampling_plan'];
        self::assertIsArray($samplingPlan['sample_positions']);
        /** @var array<mixed> $positions */
        $positions = $samplingPlan['sample_positions'];

        // 验证位置数量
        $this->assertCount(10, $positions);

        // 验证位置都在有效范围内
        self::assertIsIterable($positions);
        foreach ($positions as $position) {
            $this->assertGreaterThanOrEqual(1, $position);
            $this->assertLessThanOrEqual(100, $position);
        }
    }

    /**
     * 测试分层抽样方法
     */
    public function testExecuteSampleInspectionWithStratifiedMethod(): void
    {
        $batchInfo = [
            'batch_id' => 'BATCH004',
            'total_quantity' => 100,
            'product_info' => ['sku' => 'SKU004'],
        ];

        $samplingRules = [
            'sampling_method' => 'stratified',
            'sample_size' => 10,
        ];

        $result = $this->service->executeSampleInspection($batchInfo, $samplingRules);

        self::assertIsArray($result['sampling_plan']);
        /** @var array<string, mixed> $samplingPlan */
        $samplingPlan = $result['sampling_plan'];
        self::assertIsArray($samplingPlan['sample_positions']);
        /** @var array<mixed> $positions */
        $positions = $samplingPlan['sample_positions'];

        // 验证位置数量
        $this->assertCount(10, $positions);

        // 验证位置都在有效范围内
        self::assertIsIterable($positions);
        foreach ($positions as $position) {
            $this->assertGreaterThanOrEqual(1, $position);
            $this->assertLessThanOrEqual(100, $position);
        }
    }

    /**
     * 测试未知抽样方法使用默认策略
     */
    public function testExecuteSampleInspectionWithUnknownMethod(): void
    {
        $batchInfo = [
            'batch_id' => 'BATCH005',
            'total_quantity' => 20,
            'product_info' => ['sku' => 'SKU005'],
        ];

        $samplingRules = [
            'sampling_method' => 'unknown_method',
            'sample_size' => 5,
        ];

        $result = $this->service->executeSampleInspection($batchInfo, $samplingRules);

        self::assertIsArray($result['sampling_plan']);
        /** @var array<string, mixed> $samplingPlan */
        $samplingPlan = $result['sampling_plan'];
        self::assertIsArray($samplingPlan['sample_positions']);
        /** @var array<mixed> $positions */
        $positions = $samplingPlan['sample_positions'];

        // 验证使用默认方法（连续序列）
        $this->assertCount(5, $positions);
        $this->assertEquals([1, 2, 3, 4, 5], $positions);
    }

    /**
     * 测试自动计算样本大小（当未提供时）
     */
    public function testExecuteSampleInspectionAutoCalculateSampleSize(): void
    {
        $batchInfo = [
            'batch_id' => 'BATCH006',
            'total_quantity' => 200,
            'product_info' => ['sku' => 'SKU006'],
        ];

        $samplingRules = [
            'sampling_method' => 'random',
            // 不提供 sample_size
        ];

        $result = $this->service->executeSampleInspection($batchInfo, $samplingRules);

        // 200 * 0.1 = 20, 但最大为10
        self::assertIsArray($result['sampling_plan']);
        /** @var array<string, mixed> $samplingPlan */
        $samplingPlan = $result['sampling_plan'];
        $this->assertEquals(10, $samplingPlan['sample_size']);
        self::assertIsArray($result['sample_tasks']);
        $this->assertCount(10, $result['sample_tasks']);
    }

    /**
     * 测试小批次的样本大小计算
     */
    public function testExecuteSampleInspectionSmallBatch(): void
    {
        $batchInfo = [
            'batch_id' => 'BATCH007',
            'total_quantity' => 5,
            'product_info' => ['sku' => 'SKU007'],
        ];

        $samplingRules = [
            'sampling_method' => 'random',
        ];

        $result = $this->service->executeSampleInspection($batchInfo, $samplingRules);

        // floor(5 * 0.1) = 0, min(10, max(1, 0)) = 1
        self::assertIsArray($result['sampling_plan']);
        /** @var array<string, mixed> $samplingPlan */
        $samplingPlan = $result['sampling_plan'];
        $this->assertEquals(1, $samplingPlan['sample_size']);
        self::assertIsArray($result['sample_tasks']);
        $this->assertCount(1, $result['sample_tasks']);
    }

    /**
     * 测试验收标准的传递
     */
    public function testExecuteSampleInspectionWithAcceptanceCriteria(): void
    {
        $batchInfo = [
            'batch_id' => 'BATCH008',
            'total_quantity' => 100,
            'product_info' => ['sku' => 'SKU008'],
        ];

        $acceptanceCriteria = [
            'defect_rate' => 0.05,
            'critical_defects' => 0,
        ];

        $samplingRules = [
            'sampling_method' => 'random',
            'sample_size' => 10,
            'acceptance_criteria' => $acceptanceCriteria,
        ];

        $result = $this->service->executeSampleInspection($batchInfo, $samplingRules);

        self::assertIsArray($result['sampling_plan']);
        /** @var array<string, mixed> $samplingPlan */
        $samplingPlan = $result['sampling_plan'];
        $this->assertEquals($acceptanceCriteria, $samplingPlan['acceptance_criteria']);
    }

    /**
     * 测试默认验收标准（空数组）
     */
    public function testExecuteSampleInspectionWithDefaultAcceptanceCriteria(): void
    {
        $batchInfo = [
            'batch_id' => 'BATCH009',
            'total_quantity' => 100,
            'product_info' => ['sku' => 'SKU009'],
        ];

        $samplingRules = [
            'sampling_method' => 'random',
            'sample_size' => 10,
        ];

        $result = $this->service->executeSampleInspection($batchInfo, $samplingRules);

        self::assertIsArray($result['sampling_plan']);
        /** @var array<string, mixed> $samplingPlan */
        $samplingPlan = $result['sampling_plan'];
        $this->assertEquals([], $samplingPlan['acceptance_criteria']);
    }

    /**
     * @return array<string, array{batchInfo: array<string, mixed>, samplingRules: array<string, mixed>, expectedSize: int}>
     */
    public static function totalQuantityDataProvider(): array
    {
        return [
            '数值型数量' => [
                'batchInfo' => [
                    'batch_id' => 'TEST001',
                    'total_quantity' => 100,
                    'product_info' => [],
                ],
                'samplingRules' => ['sample_size' => 5],
                'expectedSize' => 5,
            ],
            '字符串型数量' => [
                'batchInfo' => [
                    'batch_id' => 'TEST002',
                    'total_quantity' => '50',
                    'product_info' => [],
                ],
                'samplingRules' => ['sample_size' => 3],
                'expectedSize' => 3,
            ],
            '无效数量默认为0' => [
                'batchInfo' => [
                    'batch_id' => 'TEST003',
                    'total_quantity' => 'invalid',
                    'product_info' => [],
                ],
                'samplingRules' => ['sample_size' => 2],
                'expectedSize' => 2,
            ],
            '缺失数量默认为0' => [
                'batchInfo' => [
                    'batch_id' => 'TEST004',
                    'product_info' => [],
                ],
                'samplingRules' => ['sample_size' => 1],
                'expectedSize' => 1,
            ],
        ];
    }

    /**
     * 测试不同类型的总数量处理
     *
     * @param array<string, mixed> $batchInfo
     * @param array<string, mixed> $samplingRules
     */
    #[DataProvider('totalQuantityDataProvider')]
    public function testExecuteSampleInspectionWithVariousTotalQuantityTypes(
        array $batchInfo,
        array $samplingRules,
        int $expectedSize,
    ): void {
        $result = $this->service->executeSampleInspection($batchInfo, $samplingRules);

        self::assertIsArray($result['sampling_plan']);
        /** @var array<string, mixed> $samplingPlan */
        $samplingPlan = $result['sampling_plan'];
        $this->assertEquals($expectedSize, $samplingPlan['sample_size']);
        self::assertIsArray($result['sample_tasks']);
        $this->assertCount($expectedSize, $result['sample_tasks']);
    }

    /**
     * @return array<string, array{samplingMethod: mixed, expectedMethod: string}>
     */
    public static function samplingMethodDataProvider(): array
    {
        return [
            'random方法' => [
                'samplingMethod' => 'random',
                'expectedMethod' => 'random',
            ],
            'systematic方法' => [
                'samplingMethod' => 'systematic',
                'expectedMethod' => 'systematic',
            ],
            'stratified方法' => [
                'samplingMethod' => 'stratified',
                'expectedMethod' => 'stratified',
            ],
            '非字符串类型默认为random' => [
                'samplingMethod' => 123,
                'expectedMethod' => 'random',
            ],
            '缺失方法默认为random' => [
                'samplingMethod' => null,
                'expectedMethod' => 'random',
            ],
        ];
    }

    /**
     * 测试不同类型的抽样方法处理
     *
     * @param mixed $samplingMethod
     */
    #[DataProvider('samplingMethodDataProvider')]
    public function testExecuteSampleInspectionWithVariousSamplingMethodTypes(
        mixed $samplingMethod,
        string $expectedMethod,
    ): void {
        $batchInfo = [
            'batch_id' => 'TEST',
            'total_quantity' => 100,
            'product_info' => [],
        ];

        $samplingRules = [
            'sample_size' => 5,
        ];

        if (null !== $samplingMethod) {
            $samplingRules['sampling_method'] = $samplingMethod;
        }

        $result = $this->service->executeSampleInspection($batchInfo, $samplingRules);

        self::assertIsArray($result['sampling_plan']);
        /** @var array<string, mixed> $samplingPlan */
        $samplingPlan = $result['sampling_plan'];
        $this->assertEquals($expectedMethod, $samplingPlan['sampling_method']);
    }

    /**
     * 测试样本大小不能超过总数量
     */
    public function testSampleSizeDoesNotExceedTotalQuantity(): void
    {
        $batchInfo = [
            'batch_id' => 'BATCH010',
            'total_quantity' => 3,
            'product_info' => ['sku' => 'SKU010'],
        ];

        $samplingRules = [
            'sampling_method' => 'random',
            'sample_size' => 10, // 要求10个，但总数只有3
        ];

        $result = $this->service->executeSampleInspection($batchInfo, $samplingRules);

        // 实际创建的任务数不会超过总数量
        self::assertIsArray($result['sample_tasks']);
        $this->assertLessThanOrEqual(3, count($result['sample_tasks']));
    }

    /**
     * 测试预计完成时间在未来
     */
    public function testExpectedCompletionIsInFuture(): void
    {
        $batchInfo = [
            'batch_id' => 'BATCH011',
            'total_quantity' => 100,
            'product_info' => [],
        ];

        $samplingRules = [
            'sampling_method' => 'random',
            'sample_size' => 5,
        ];

        $now = new \DateTimeImmutable();
        $result = $this->service->executeSampleInspection($batchInfo, $samplingRules);

        $this->assertGreaterThan($now, $result['expected_completion']);
    }

    /**
     * 测试任务数据完整性
     */
    public function testSampleTaskDataCompleteness(): void
    {
        $batchInfo = [
            'batch_id' => 'BATCH012',
            'total_quantity' => 50,
            'product_info' => [
                'sku' => 'SKU012',
                'name' => 'Product 012',
                'category' => 'Electronics',
            ],
        ];

        $samplingRules = [
            'sampling_method' => 'random',
            'sample_size' => 3,
        ];

        $result = $this->service->executeSampleInspection($batchInfo, $samplingRules);

        self::assertIsArray($result['sample_tasks']);
        self::assertIsIterable($result['sample_tasks']);
        foreach ($result['sample_tasks'] as $task) {
            self::assertInstanceOf(QualityTask::class, $task);
            $taskData = $task->getData();
            self::assertIsArray($taskData);
            /** @var array<string, mixed> $taskData */

            // 验证所有必要字段存在
            $this->assertArrayHasKey('batch_id', $taskData);
            $this->assertArrayHasKey('sample_position', $taskData);
            $this->assertArrayHasKey('sampling_method', $taskData);
            $this->assertArrayHasKey('product_info', $taskData);

            // 验证字段值
            $this->assertEquals('BATCH012', $taskData['batch_id']);
            $this->assertEquals('random', $taskData['sampling_method']);
            $this->assertEquals($batchInfo['product_info'], $taskData['product_info']);
        }
    }

    /**
     * 测试位置去重（随机方法不应有重复位置）
     */
    public function testRandomPositionsAreUnique(): void
    {
        $batchInfo = [
            'batch_id' => 'BATCH013',
            'total_quantity' => 100,
            'product_info' => [],
        ];

        $samplingRules = [
            'sampling_method' => 'random',
            'sample_size' => 10,
        ];

        $result = $this->service->executeSampleInspection($batchInfo, $samplingRules);

        self::assertIsArray($result['sampling_plan']);
        /** @var array<string, mixed> $samplingPlan */
        $samplingPlan = $result['sampling_plan'];
        self::assertIsArray($samplingPlan['sample_positions']);
        /** @var array<mixed> $positions */
        $positions = $samplingPlan['sample_positions'];
        $uniquePositions = array_unique($positions);

        $this->assertCount(count($positions), $uniquePositions, '随机位置应该是唯一的');
    }

    /**
     * 测试服务实例化
     */
    public function testServiceInstantiation(): void
    {
        $this->assertInstanceOf(QualitySampleInspectionService::class, $this->service);
    }

    /**
     * 测试零数量批次
     */
    public function testZeroQuantityBatch(): void
    {
        $batchInfo = [
            'batch_id' => 'BATCH014',
            'total_quantity' => 0,
            'product_info' => [],
        ];

        $samplingRules = [
            'sampling_method' => 'random',
        ];

        $result = $this->service->executeSampleInspection($batchInfo, $samplingRules);

        // 零数量批次应生成至少1个样本（根据逻辑 min(10, max(1, 0)) = 1）
        self::assertIsArray($result['sampling_plan']);
        /** @var array<string, mixed> $samplingPlan */
        $samplingPlan = $result['sampling_plan'];
        $this->assertEquals(1, $samplingPlan['sample_size']);
    }
}
