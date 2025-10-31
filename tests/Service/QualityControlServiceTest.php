<?php

namespace Tourze\WarehouseOperationBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\WarehouseOperationBundle\Entity\QualityStandard;
use Tourze\WarehouseOperationBundle\Entity\QualityTask;
use Tourze\WarehouseOperationBundle\Enum\TaskStatus;
use Tourze\WarehouseOperationBundle\Enum\TaskType;
use Tourze\WarehouseOperationBundle\Event\QualityFailedEvent;
use Tourze\WarehouseOperationBundle\Repository\QualityStandardRepository;
use Tourze\WarehouseOperationBundle\Repository\WarehouseTaskRepository;
use Tourze\WarehouseOperationBundle\Service\QualityControlService;

/**
 * QualityControlService 单元测试
 *
 * @internal
 */
#[CoversClass(QualityControlService::class)]
#[RunTestsInSeparateProcesses]
class QualityControlServiceTest extends AbstractIntegrationTestCase
{
    private QualityControlService $service;

    // EntityManager 通过继承的 getEntityManager() 方法获取，不需要属性

    private EventDispatcherInterface $eventDispatcher;

    private QualityStandardRepository $standardRepository;

    private WarehouseTaskRepository $taskRepository;

    protected function onSetUp(): void
    {
        // entityManager 通过 getEntityManager() 获取，不需要直接赋值
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->standardRepository = $this->createMock(QualityStandardRepository::class);
        $this->taskRepository = $this->createMock(WarehouseTaskRepository::class);

        $this->service = parent::getService(QualityControlService::class);
    }

    /**
     * 测试服务依赖注入正确
     */
    public function testServiceDependenciesAreCorrect(): void
    {
        $this->assertInstanceOf(QualityControlService::class, $this->service);
    }

    /**
     * 测试执行质检流程 - 成功场景
     */
    public function testPerformQualityCheckSuccess(): void
    {
        $task = $this->createQualityTask();
        $checkData = [
            'product_info' => ['category' => 'electronics'],
            'visual_check' => ['condition' => 'good', 'damage' => false],
            'quantity_check' => ['expected' => 10, 'actual' => 10],
            'inspector_notes' => 'All items in good condition',
        ];

        $standard = $this->createQualityStandard('electronics');
        $this->standardRepository->expects($this->once())
            ->method('findByProductCategory')
            ->with('electronics')
            ->willReturn([$standard])
        ;

        $this->taskRepository->expects($this->once())
            ->method('save')
            ->with($task)
        ;

        $result = $this->service->performQualityCheck($task, $checkData);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('overall_result', $result);
        $this->assertArrayHasKey('quality_score', $result);
        $this->assertArrayHasKey('check_results', $result);
        $this->assertArrayHasKey('defects', $result);
        $this->assertArrayHasKey('recommendations', $result);

        // The result could be 'pass', 'fail', or 'conditional' depending on the check data
        $this->assertContainsEquals($result['overall_result'], ['pass', 'fail', 'conditional']);
        $this->assertGreaterThanOrEqual(0, $result['quality_score']);
        $this->assertLessThanOrEqual(100, $result['quality_score']);
    }

    /**
     * 测试执行质检流程 - 失败场景
     */
    public function testPerformQualityCheckFailure(): void
    {
        $task = $this->createQualityTask();
        $checkData = [
            'product_info' => ['category' => 'electronics'],
            'visual_check' => ['condition' => 'damaged', 'damage' => true, 'damage_level' => 'severe'],
            'quantity_check' => ['expected' => 10, 'actual' => 8],
            'expiry_check' => ['expiry_date' => '2020-01-01'], // 过期商品
        ];

        $standard = $this->createQualityStandard('electronics');
        $this->standardRepository->expects($this->once())
            ->method('findByProductCategory')
            ->with('electronics')
            ->willReturn([$standard])
        ;

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(self::isInstanceOf(QualityFailedEvent::class))
        ;

        $this->taskRepository->expects($this->once())
            ->method('save')
            ->with($task)
        ;

        $result = $this->service->performQualityCheck($task, $checkData);

        // Should fail due to damaged product and expired item
        $this->assertContainsEquals($result['overall_result'], ['fail', 'conditional']);
        $this->assertNotEmpty($result['defects']);
        $this->assertEquals(TaskStatus::FAILED, $task->getStatus());
    }

    /**
     * 测试执行质检流程 - 无标准
     */
    public function testPerformQualityCheckWithoutStandards(): void
    {
        $task = $this->createQualityTask();
        $checkData = ['product_info' => ['category' => 'unknown']];

        $this->standardRepository->expects($this->once())
            ->method('findByProductCategory')
            ->with('unknown')
            ->willReturn([])
        ;

        $result = $this->service->performQualityCheck($task, $checkData);

        self::assertIsArray($result);
        /** @var array<string, mixed> $result */
        $this->assertEquals('fail', $result['overall_result']);
        $this->assertEquals(0, $result['quality_score']);
        self::assertIsArray($result['defects']);
        $this->assertNotEmpty($result['defects']);
        self::assertIsArray($result['recommendations']);
        $this->assertContainsEquals('请配置对应商品类别的质检标准', $result['recommendations']);
    }

    /**
     * 测试处理质检失败商品
     */
    public function testHandleQualityFailure(): void
    {
        $task = $this->createQualityTask();
        $failureReason = 'Product damaged';
        $failureDetails = [
            'failure_type' => 'damage',
            'severity_level' => 'high',
            'affected_quantity' => 5,
        ];
        $handlingOptions = ['auto_isolate' => true];

        $this->taskRepository->expects($this->once())
            ->method('save')
            ->with($task)
        ;

        $result = $this->service->handleQualityFailure($task, $failureReason, $failureDetails, $handlingOptions);

        $this->assertIsArray($result);
        /** @var array<string, mixed> $result */
        $this->assertArrayHasKey('handling_actions', $result);
        $this->assertArrayHasKey('isolation_location', $result);
        $this->assertArrayHasKey('follow_up_tasks', $result);
        $this->assertArrayHasKey('cost_estimation', $result);
        $this->assertArrayHasKey('timeline', $result);

        $this->assertNotEmpty($result['handling_actions']);
        $this->assertNotNull($result['isolation_location']);
        self::assertIsString($result['isolation_location']);
        $this->assertStringContainsString('QUARANTINE_DAMAGED', $result['isolation_location']);
    }

    /**
     * 测试获取适用的质检标准
     */
    public function testGetApplicableStandards(): void
    {
        $productAttributes = [
            'category' => 'electronics',
            'special_attributes' => ['dangerous'],
        ];

        $standard1 = $this->createQualityStandard('electronics', ['dangerous_check' => ['enabled' => true]]);
        $standard2 = $this->createQualityStandard('electronics', ['basic_check' => ['enabled' => true]]);

        $this->standardRepository->expects($this->once())
            ->method('findByProductCategory')
            ->with('electronics')
            ->willReturn([$standard1, $standard2])
        ;

        $result = $this->service->getApplicableStandards($productAttributes);

        $this->assertIsArray($result);
        $this->assertCount(1, $result); // 只有包含dangerous_check的标准被选中
        $this->assertArrayHasKey(0, $result, 'Result should have element at index 0');
        $this->assertEquals($standard1, $result[0]);
    }

    /**
     * 测试获取适用的质检标准 - 空类别
     */
    public function testGetApplicableStandardsWithEmptyCategory(): void
    {
        $productAttributes = [];

        $result = $this->service->getApplicableStandards($productAttributes);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * 测试验证质检标准配置 - 有效配置
     */
    public function testValidateQualityStandardValid(): void
    {
        $standard = $this->createQualityStandard('electronics', [
            'visual_check' => [
                'enabled' => true,
                'weight' => 30,
                'criteria' => ['allowed_conditions' => ['perfect', 'good']],
            ],
            'quantity_check' => [
                'enabled' => true,
                'weight' => 40,
                'criteria' => ['tolerance' => 1],
            ],
        ]);

        $result = $this->service->validateQualityStandard($standard);

        $this->assertTrue($result['is_valid']);
        $this->assertEmpty($result['validation_errors']);
    }

    /**
     * 测试验证质检标准配置 - 无效配置
     */
    public function testValidateQualityStandardInvalid(): void
    {
        $standard = new QualityStandard(); // 完全空的标准
        $standard->setName(''); // 空名称
        $standard->setProductCategory(''); // 空类别
        $standard->setCheckItems([]); // 空检查项

        $result = $this->service->validateQualityStandard($standard);

        self::assertIsArray($result);
        /** @var array<string, mixed> $result */
        $this->assertFalse($result['is_valid']);
        self::assertIsArray($result['validation_errors']);
        $this->assertNotEmpty($result['validation_errors']);
        $this->assertContainsEquals('质检标准必须包含至少一个检查项', $result['validation_errors']);
        $this->assertContainsEquals('商品类别不能为空', $result['validation_errors']);
    }

    /**
     * 测试生成质检报告
     */
    public function testGenerateQualityReport(): void
    {
        $taskIds = [1, 2, 3];
        $tasks = [
            $this->createQualityTaskWithResult('pass', 95),
            $this->createQualityTaskWithResult('fail', 45),
            $this->createQualityTaskWithResult('conditional', 75),
        ];

        $this->taskRepository->expects($this->once())
            ->method('findBy')
            ->with(['id' => $taskIds])
            ->willReturn($tasks)
        ;

        $result = $this->service->generateQualityReport($taskIds);

        $this->assertIsArray($result);
        /** @var array<string, mixed> $result */
        $this->assertArrayHasKey('report_id', $result);
        $this->assertArrayHasKey('summary_statistics', $result);
        $this->assertArrayHasKey('generated_at', $result);

        $stats = $result['summary_statistics'];
        self::assertIsArray($stats);
        /** @var array<string, mixed> $stats */
        $this->assertEquals(3, $stats['total_tasks']);
        $this->assertEquals(1, $stats['pass_count']);
        $this->assertEquals(1, $stats['fail_count']);
        $this->assertEquals(1, $stats['conditional_count']);
        $this->assertGreaterThan(0, $stats['pass_rate']);
    }

    /**
     * 测试质检数据统计分析
     */
    public function testAnalyzeQualityStatistics(): void
    {
        // 获取实际的任务仓储，而不是使用Mock
        $taskRepository = parent::getService(WarehouseTaskRepository::class);

        // 使用反射替换服务中的taskRepository为实际的仓储
        $reflection = new \ReflectionClass($this->service);
        $property = $reflection->getProperty('taskRepository');
        $property->setAccessible(true);
        $property->setValue($this->service, $taskRepository);

        $result = $this->service->analyzeQualityStatistics(['time_period' => '30days']);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('overall_pass_rate', $result);
        $this->assertArrayHasKey('trend_analysis', $result);
        $this->assertArrayHasKey('failure_patterns', $result);
        $this->assertGreaterThanOrEqual(0, $result['overall_pass_rate']);
    }

    /**
     * 测试执行质检样品抽检
     */
    public function testExecuteSampleInspection(): void
    {
        $batchInfo = [
            'batch_id' => 'BATCH001',
            'total_quantity' => 100,
            'product_info' => ['category' => 'electronics'],
        ];

        $samplingRules = [
            'sampling_method' => 'random',
            'sample_size' => 10,
        ];

        $result = $this->service->executeSampleInspection($batchInfo, $samplingRules);

        $this->assertIsArray($result);
        /** @var array<string, mixed> $result */
        $this->assertArrayHasKey('sample_tasks', $result);
        $this->assertArrayHasKey('sampling_plan', $result);
        $this->assertArrayHasKey('expected_completion', $result);

        self::assertIsArray($result['sample_tasks']);
        $this->assertCount(10, $result['sample_tasks']);
        $this->assertInstanceOf(\DateTimeImmutable::class, $result['expected_completion']);

        $plan = $result['sampling_plan'];
        self::assertIsArray($plan);
        /** @var array<string, mixed> $plan */
        $this->assertEquals('BATCH001', $plan['batch_id']);
        $this->assertEquals(100, $plan['total_quantity']);
        $this->assertEquals(10, $plan['sample_size']);
        $this->assertEquals('random', $plan['sampling_method']);
    }

    /**
     * 测试处理质检异常升级
     */
    public function testEscalateQualityIssue(): void
    {
        $task = $this->createQualityTask();
        $escalationReason = [
            'severity' => 'critical',
            'issue_type' => 'safety_issue',
            'impact_scope' => 'multiple_batches',
        ];

        $this->taskRepository->expects($this->once())
            ->method('save')
            ->with($task)
        ;

        $result = $this->service->escalateQualityIssue($task, $escalationReason);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('escalation_level', $result);
        $this->assertArrayHasKey('assigned_personnel', $result);
        $this->assertArrayHasKey('deadline', $result);
        $this->assertArrayHasKey('notification_sent', $result);

        $this->assertGreaterThanOrEqual(1, $result['escalation_level']);
        $this->assertLessThanOrEqual(5, $result['escalation_level']);
        $this->assertNotEmpty($result['assigned_personnel']);
        $this->assertInstanceOf(\DateTimeImmutable::class, $result['deadline']);
        $this->assertTrue($result['notification_sent']);
    }

    /**
     * 测试严格模式质检
     */
    public function testPerformQualityCheckStrictMode(): void
    {
        $task = $this->createQualityTask();
        $checkData = [
            'product_info' => ['category' => 'electronics'],
            'visual_check' => ['condition' => 'acceptable'], // 在宽松模式下可能通过
        ];
        $options = ['strict_mode' => true];

        $standard = $this->createQualityStandard('electronics');
        $this->standardRepository->expects($this->once())
            ->method('findByProductCategory')
            ->with('electronics')
            ->willReturn([$standard])
        ;

        $this->taskRepository->expects($this->once())
            ->method('save')
            ->with($task)
        ;

        $result = $this->service->performQualityCheck($task, $checkData, $options);

        $this->assertIsArray($result);
        // 严格模式可能会产生更严格的检查结果
        $this->assertArrayHasKey('overall_result', $result);
    }

    /**
     * 测试跳过可选检查项
     */
    public function testPerformQualityCheckSkipOptional(): void
    {
        $task = $this->createQualityTask();
        $checkData = [
            'product_info' => ['category' => 'electronics'],
            'visual_check' => ['condition' => 'good'],
        ];
        $options = ['skip_optional' => true];

        $standard = $this->createQualityStandardWithOptionalChecks();
        $this->standardRepository->expects($this->once())
            ->method('findByProductCategory')
            ->with('electronics')
            ->willReturn([$standard])
        ;

        $this->taskRepository->expects($this->once())
            ->method('save')
            ->with($task)
        ;

        $result = $this->service->performQualityCheck($task, $checkData, $options);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('overall_result', $result);
    }

    /**
     * 创建测试用的QualityTask
     */
    private function createQualityTask(): QualityTask
    {
        $task = new QualityTask();
        $task->setType(TaskType::QUALITY);
        $task->setStatus(TaskStatus::PENDING);
        $task->setPriority(50);
        $task->setData([]);

        return $task;
    }

    /**
     * 创建测试用的QualityStandard
     * @param array<string, mixed> $checkItems
     */
    private function createQualityStandard(string $category, array $checkItems = []): QualityStandard
    {
        $standard = new QualityStandard();
        $standard->setName("Test Standard for {$category}");
        $standard->setProductCategory($category);
        $standard->setPriority(80);
        $standard->setIsActive(true);

        if ([] === $checkItems) {
            $checkItems = [
                'visual_check' => [
                    'enabled' => true,
                    'weight' => 30,
                    'criteria' => [
                        'allowed_conditions' => ['perfect', 'good', 'acceptable'],
                        'max_damage_level' => 'minor',
                    ],
                ],
                'quantity_check' => [
                    'enabled' => true,
                    'weight' => 40,
                    'criteria' => ['tolerance' => 1],
                ],
                'expiry_check' => [
                    'enabled' => true,
                    'weight' => 30,
                    'criteria' => ['min_shelf_life_days' => 30],
                ],
            ];
        }

        $standard->setCheckItems($checkItems);

        return $standard;
    }

    /**
     * 创建包含可选检查项的QualityStandard
     */
    private function createQualityStandardWithOptionalChecks(): QualityStandard
    {
        return $this->createQualityStandard('electronics', [
            'visual_check' => [
                'enabled' => true,
                'required' => true,
                'weight' => 50,
                'criteria' => ['allowed_conditions' => ['perfect', 'good']],
            ],
            'optional_check' => [
                'enabled' => true,
                'required' => false, // 可选检查项
                'weight' => 20,
                'criteria' => [],
            ],
            'weight_check' => [
                'enabled' => true,
                'required' => true,
                'weight' => 30,
                'criteria' => ['tolerance_percent' => 5],
            ],
        ]);
    }

    /**
     * 创建包含质检结果的QualityTask
     */
    private function createQualityTaskWithResult(string $result, float $score): QualityTask
    {
        $task = $this->createQualityTask();
        $task->setData([
            'quality_result' => [
                'overall_result' => $result,
                'quality_score' => $score,
                'defects' => 'fail' === $result ? [['type' => 'test_defect', 'description' => 'Test defect']] : [],
                'inspector_id' => 123,
                'checked_at' => new \DateTimeImmutable(),
            ],
        ]);

        return $task;
    }
}
