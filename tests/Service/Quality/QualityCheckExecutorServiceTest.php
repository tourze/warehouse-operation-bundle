<?php

declare(strict_types=1);

namespace Tourze\WarehouseOperationBundle\Tests\Service\Quality;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\WarehouseOperationBundle\Entity\QualityStandard;
use Tourze\WarehouseOperationBundle\Entity\QualityTask;
use Tourze\WarehouseOperationBundle\Enum\TaskStatus;
use Tourze\WarehouseOperationBundle\Event\QualityFailedEvent;
use Tourze\WarehouseOperationBundle\Repository\QualityStandardRepository;
use Tourze\WarehouseOperationBundle\Repository\WarehouseTaskRepository;
use Tourze\WarehouseOperationBundle\Service\Quality\QualityCheckExecutorService;
use Tourze\WarehouseOperationBundle\Service\Quality\Validator\GenericCheckValidator;
use Tourze\WarehouseOperationBundle\Service\Quality\Validator\QualityCheckValidatorInterface;
use Tourze\WarehouseOperationBundle\Service\Quality\Validator\QualityCheckValidatorRegistryInterface;

/**
 * QualityCheckExecutorService 单元测试
 *
 * 测试质检执行服务的完整功能，包括质检流程执行、标准匹配、结果评估等核心业务逻辑。
 * 验证服务的正确性、验证逻辑和异常处理。
 * @internal
 */
#[CoversClass(QualityCheckExecutorService::class)]
#[RunTestsInSeparateProcesses]
class QualityCheckExecutorServiceTest extends AbstractIntegrationTestCase
{
    private QualityCheckExecutorService $service;

    private EventDispatcherInterface $eventDispatcher;

    private QualityStandardRepository $qualityStandardRepository;

    private QualityCheckValidatorRegistryInterface $validatorRegistry;

    protected function onSetUp(): void
    {
        /** @phpstan-ignore-next-line */
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->qualityStandardRepository = parent::getService(QualityStandardRepository::class);

        $qualityTaskRepository = parent::getService(WarehouseTaskRepository::class);

        // 使用匿名类实现接口，因为 QualityCheckValidatorRegistry 是 final 类
        $this->validatorRegistry = new class () implements QualityCheckValidatorRegistryInterface {
            /** @var QualityCheckValidatorInterface|null */
            private ?QualityCheckValidatorInterface $mockValidator = null;

            public function setMockValidator(?QualityCheckValidatorInterface $validator): void
            {
                $this->mockValidator = $validator;
            }

            public function register(QualityCheckValidatorInterface $validator): void
            {
            }

            public function getValidator(string $checkType): QualityCheckValidatorInterface
            {
                // 如果设置了Mock验证器，总是返回它（用于测试）
                return $this->mockValidator ?? new GenericCheckValidator();
            }

            public function supports(string $checkType): bool
            {
                return true;
            }
        };

        // 手动实例化服务以注入 mock registry
        // @phpstan-ignore integrationTest.noDirectInstantiationOfCoveredClass
        $this->service = new QualityCheckExecutorService(
            $this->eventDispatcher,
            $this->qualityStandardRepository,
            $qualityTaskRepository,
            $this->validatorRegistry
        );
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Quality\QualityCheckExecutorService::performQualityCheck
     */
    public function testPerformQualityCheckWithNoApplicableStandards(): void
    {
        $task = new QualityTask();
        $task->setTaskName('无标准质检任务');
        $task->setTaskType('quality_check');
        $task->setData([]);

        $checkData = [
            'product_info' => [], // 没有产品类别信息
            'inspector_notes' => '测试检查',
        ];

        // 执行测试
        $result = $this->service->performQualityCheck($task, $checkData);

        // 验证无标准结果
        $this->assertIsArray($result);
        $this->assertEquals('fail', $result['overall_result']);
        $this->assertEquals(0, $result['quality_score']);
        $this->assertIsArray($result['defects']);
        $this->assertCount(1, $result['defects']);
        $this->assertIsArray($result['defects'][0]);
        $this->assertEquals('no_standards', $result['defects'][0]['type']);
        $this->assertIsArray($result['recommendations']);
        $this->assertContainsEquals('请配置对应商品类别的质检标准', $result['recommendations']);
        $this->assertEmpty($result['photos']);
        $this->assertInstanceOf(\DateTimeImmutable::class, $result['checked_at']);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Quality\QualityCheckExecutorService::performQualityCheck
     */
    public function testPerformQualityCheckWithValidStandards(): void
    {
        // 清理现有数据以确保测试隔离
        $existingStandards = $this->qualityStandardRepository->findAll();
        foreach ($existingStandards as $existing) {
            $this->qualityStandardRepository->remove($existing);
        }

        // 创建质检标准
        $standard = new QualityStandard();
        $standard->setName('电子产品质检标准');
        $standard->setProductCategory('electronics');
        $standard->setCheckItems([
            'appearance' => [
                'enabled' => true,
                'required' => true,
                'weight' => 30,
                'criteria' => ['no_damage', 'clean_surface'],
            ],
            'function' => [
                'enabled' => true,
                'required' => true,
                'weight' => 50,
                'criteria' => ['powers_on', 'all_functions_work'],
            ],
        ]);
        $standard->setIsActive(true);
        $standard->setPriority(80);
        $this->qualityStandardRepository->save($standard);

        // 模拟验证器返回成功结果
        $mockValidator = new class () implements QualityCheckValidatorInterface {
            public function validate(mixed $checkValue, array $criteria, bool $strictMode): array
            {
                return ['valid' => true, 'defects' => [], 'details' => ['status' => 'pass']];
            }

            public function getSupportedCheckType(): string
            {
                return 'test_check';
            }
        };

        /** @phpstan-ignore method.notFound */
        $this->validatorRegistry->setMockValidator($mockValidator);

        $task = new QualityTask();
        $task->setTaskName('电子产品质检');
        $task->setTaskType('quality_check');
        $task->setData([]);

        $checkData = [
            'product_info' => ['category' => 'electronics'],
            'appearance' => 'good',
            'function' => 'working',
            'inspector_notes' => '检查通过',
            'photos' => ['photo1.jpg', 'photo2.jpg'],
        ];

        $options = [
            'inspector_id' => 123,
            'strict_mode' => true,
        ];

        // 执行测试
        $result = $this->service->performQualityCheck($task, $checkData, $options);

        // 验证结果
        $this->assertEquals('pass', $result['overall_result']);
        $this->assertGreaterThan(0, $result['quality_score']);
        $this->assertEmpty($result['defects']);
        $this->assertIsArray($result['recommendations']);
        $this->assertContainsEquals('商品质检合格，可以正常入库', $result['recommendations']);
        $this->assertEquals('检查通过', $result['inspector_notes']);
        $this->assertEquals(['photo1.jpg', 'photo2.jpg'], $result['photos']);
        $this->assertEquals(123, $result['inspector_id']);

        // 验证任务状态更新
        $this->assertEquals(TaskStatus::COMPLETED, $task->getStatus());
        $taskData = $task->getData();
        $this->assertArrayHasKey('quality_result', $taskData);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Quality\QualityCheckExecutorService::performQualityCheck
     */
    public function testPerformQualityCheckWithFailureResult(): void
    {
        // 创建质检标准
        $standard = new QualityStandard();
        $standard->setName('严格质检标准');
        $standard->setProductCategory('electronics');
        $standard->setCheckItems([
            'appearance' => [
                'enabled' => true,
                'required' => true,
                'weight' => 100,
                'criteria' => ['perfect_condition'],
            ],
        ]);
        $standard->setIsActive(true);
        $standard->setPriority(90);
        $this->qualityStandardRepository->save($standard);

        // 模拟验证器返回失败结果
        $mockValidator = new class () implements QualityCheckValidatorInterface {
            public function validate(mixed $checkValue, array $criteria, bool $strictMode): array
            {
                return [
                    'valid' => false,
                    'defects' => [
                        ['type' => 'damage', 'description' => '外观损坏', 'critical' => true],
                    ],
                    'details' => ['damage_location' => 'corner'],
                ];
            }

            public function getSupportedCheckType(): string
            {
                return 'test_check';
            }
        };

        /** @phpstan-ignore method.notFound */
        $this->validatorRegistry->setMockValidator($mockValidator);

        $task = new QualityTask();
        $task->setTaskName('质检失败测试');
        $task->setTaskType('quality_check');
        $task->setData([]);

        $checkData = [
            'product_info' => ['category' => 'electronics'],
            'appearance' => 'damaged',
        ];

        // 期望派发失败事件
        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(self::isInstanceOf(QualityFailedEvent::class))
        ;

        // 执行测试
        $result = $this->service->performQualityCheck($task, $checkData);

        // 验证失败结果
        $this->assertIsArray($result);
        $this->assertEquals('fail', $result['overall_result']);
        $this->assertLessThan(100, $result['quality_score']);
        $this->assertIsArray($result['defects']);
        $this->assertNotEmpty($result['defects']);
        $this->assertIsArray($result['defects'][0]);
        $this->assertEquals('damage', $result['defects'][0]['type']);
        $this->assertTrue($result['defects'][0]['critical']);

        // 验证任务状态更新为失败
        $this->assertEquals(TaskStatus::FAILED, $task->getStatus());
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Quality\QualityCheckExecutorService::getApplicableStandards
     */
    public function testGetApplicableStandardsWithProductCategory(): void
    {
        // 清理现有数据以确保测试隔离
        $existingStandards = $this->qualityStandardRepository->findAll();
        foreach ($existingStandards as $existing) {
            $this->qualityStandardRepository->remove($existing);
        }

        // 创建多个不同类别的质检标准
        $electronics = new QualityStandard();
        $electronics->setName('电子产品标准');
        $electronics->setProductCategory('electronics');
        $electronics->setIsActive(true);
        $electronics->setPriority(80);

        $clothing = new QualityStandard();
        $clothing->setName('服装标准');
        $clothing->setProductCategory('clothing');
        $clothing->setIsActive(true);
        $clothing->setPriority(70);

        $this->qualityStandardRepository->save($electronics, false);
        $this->qualityStandardRepository->save($clothing, false);
        parent::getEntityManager()->flush();

        // 测试按类别获取标准
        $productAttributes = ['category' => 'electronics'];
        $result = $this->service->getApplicableStandards($productAttributes);

        $this->assertCount(1, $result);
        $this->assertEquals('电子产品标准', $result[0]->getName());
        $this->assertEquals('electronics', $result[0]->getProductCategory());
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Quality\QualityCheckExecutorService::getApplicableStandards
     */
    public function testGetApplicableStandardsWithAlternativeFieldNames(): void
    {
        // 清理现有数据以确保测试隔离
        $existingStandards = $this->qualityStandardRepository->findAll();
        foreach ($existingStandards as $existing) {
            $this->qualityStandardRepository->remove($existing);
        }

        $standard = new QualityStandard();
        $standard->setName('食品标准');
        $standard->setProductCategory('food');
        $standard->setIsActive(true);
        $standard->setPriority(90);
        $this->qualityStandardRepository->save($standard);

        // 测试使用 product_type 字段
        $productAttributes = ['product_type' => 'food'];
        $result = $this->service->getApplicableStandards($productAttributes);

        $this->assertCount(1, $result);
        $this->assertEquals('food', $result[0]->getProductCategory());
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Quality\QualityCheckExecutorService::getApplicableStandards
     */
    public function testGetApplicableStandardsWithSpecialAttributes(): void
    {
        $standard = new QualityStandard();
        $standard->setName('特殊属性标准');
        $standard->setProductCategory('electronics');
        $standard->setCheckItems([
            'fragile_check' => [
                'enabled' => true,
                'criteria' => ['extra_protection'],
            ],
            'waterproof_check' => [
                'enabled' => false,
            ],
        ]);
        $standard->setIsActive(true);
        $standard->setPriority(85);
        $this->qualityStandardRepository->save($standard);

        $productAttributes = [
            'category' => 'electronics',
            'special_attributes' => ['fragile', 'waterproof'],
        ];

        $result = $this->service->getApplicableStandards($productAttributes);

        $this->assertCount(1, $result);
        $this->assertEquals('特殊属性标准', $result[0]->getName());
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Quality\QualityCheckExecutorService::getApplicableStandards
     */
    public function testGetApplicableStandardsWithNoCategoryReturnsEmpty(): void
    {
        $productAttributes = []; // 没有类别信息
        $result = $this->service->getApplicableStandards($productAttributes);

        $this->assertEmpty($result);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Quality\QualityCheckExecutorService::getApplicableStandards
     */
    public function testGetApplicableStandardsSortedByPriority(): void
    {
        // 创建多个同类别不同优先级的标准
        $highPriority = new QualityStandard();
        $highPriority->setName('高优先级标准');
        $highPriority->setProductCategory('test');
        $highPriority->setIsActive(true);
        $highPriority->setPriority(90);

        $lowPriority = new QualityStandard();
        $lowPriority->setName('低优先级标准');
        $lowPriority->setProductCategory('test');
        $lowPriority->setIsActive(true);
        $lowPriority->setPriority(60);

        $this->qualityStandardRepository->save($lowPriority, false);
        $this->qualityStandardRepository->save($highPriority, false);
        parent::getEntityManager()->flush();

        $productAttributes = ['category' => 'test'];
        $result = $this->service->getApplicableStandards($productAttributes);

        $this->assertCount(2, $result);
        // 应该按优先级从高到低排序
        $this->assertEquals('高优先级标准', $result[0]->getName());
        $this->assertEquals('低优先级标准', $result[1]->getName());
    }

    public function testServiceConstructorAndDependencies(): void
    {
        // 验证服务可以正确实例化
        $this->assertInstanceOf(QualityCheckExecutorService::class, $this->service);

        // 服务应该通过容器正确创建，具有必要的依赖
        $reflection = new \ReflectionClass($this->service);
        $this->assertTrue($reflection->hasMethod('performQualityCheck'));
        $this->assertTrue($reflection->hasMethod('getApplicableStandards'));
    }

    /**
     * 测试跳过可选检查项的功能
     */
    public function testPerformQualityCheckWithSkipOptional(): void
    {
        // 清理现有数据以确保测试隔离
        $existingStandards = $this->qualityStandardRepository->findAll();
        foreach ($existingStandards as $existing) {
            $this->qualityStandardRepository->remove($existing);
        }

        $standard = new QualityStandard();
        $standard->setName('可选检查项标准');
        $standard->setProductCategory('electronics');
        $standard->setCheckItems([
            'required_check' => [
                'enabled' => true,
                'required' => true,
                'weight' => 60,
                'criteria' => [],
            ],
            'optional_check' => [
                'enabled' => true,
                'required' => false,
                'weight' => 40,
                'criteria' => [],
            ],
        ]);
        $standard->setIsActive(true);
        $standard->setPriority(70);
        $this->qualityStandardRepository->save($standard);

        $mockValidator = new class () implements QualityCheckValidatorInterface {
            public function validate(mixed $checkValue, array $criteria, bool $strictMode): array
            {
                return ['valid' => true, 'defects' => [], 'details' => []];
            }

            public function getSupportedCheckType(): string
            {
                return 'test_check';
            }
        };

        /** @phpstan-ignore method.notFound */
        $this->validatorRegistry->setMockValidator($mockValidator);

        $task = new QualityTask();
        $task->setTaskName('跳过可选项测试');
        $task->setTaskType('quality_check');
        $task->setData([]);

        $checkData = [
            'product_info' => ['category' => 'electronics'],
            'required_check' => 'pass',
            'optional_check' => 'pass',
        ];

        $options = ['skip_optional' => true];

        $result = $this->service->performQualityCheck($task, $checkData, $options);

        // 验证质检通过（即使跳过了可选项）
        $this->assertEquals('pass', $result['overall_result']);
        $this->assertGreaterThan(0, $result['quality_score']);
    }

    /**
     * 测试禁用检查项的跳过逻辑
     */
    public function testPerformQualityCheckWithDisabledItems(): void
    {
        // 清理现有数据以确保测试隔离
        $existingStandards = $this->qualityStandardRepository->findAll();
        foreach ($existingStandards as $existing) {
            $this->qualityStandardRepository->remove($existing);
        }

        $standard = new QualityStandard();
        $standard->setName('禁用检查项标准');
        $standard->setProductCategory('electronics');
        $standard->setCheckItems([
            'enabled_check' => [
                'enabled' => true,
                'required' => true,
                'weight' => 70,
                'criteria' => [],
            ],
            'disabled_check' => [
                'enabled' => false,
                'required' => true,
                'weight' => 30,
                'criteria' => [],
            ],
        ]);
        $standard->setIsActive(true);
        $standard->setPriority(75);
        $this->qualityStandardRepository->save($standard);

        $mockValidator = new class () implements QualityCheckValidatorInterface {
            public function validate(mixed $checkValue, array $criteria, bool $strictMode): array
            {
                return ['valid' => true, 'defects' => [], 'details' => []];
            }

            public function getSupportedCheckType(): string
            {
                return 'test_check';
            }
        };

        /** @phpstan-ignore method.notFound */
        $this->validatorRegistry->setMockValidator($mockValidator);

        $task = new QualityTask();
        $task->setTaskName('禁用项测试');
        $task->setTaskType('quality_check');
        $task->setData([]);

        $checkData = [
            'product_info' => ['category' => 'electronics'],
            'enabled_check' => 'pass',
            // disabled_check 不提供数据
        ];

        $result = $this->service->performQualityCheck($task, $checkData);

        // 应该通过检查（禁用的检查项被跳过）
        $this->assertEquals('pass', $result['overall_result']);
    }

    /**
     * 测试缺少检查数据的处理
     */
    public function testPerformQualityCheckWithMissingData(): void
    {
        // 清理现有数据以确保测试隔离
        $existingStandards = $this->qualityStandardRepository->findAll();
        foreach ($existingStandards as $existing) {
            $this->qualityStandardRepository->remove($existing);
        }

        $standard = new QualityStandard();
        $standard->setName('缺少数据测试标准');
        $standard->setProductCategory('electronics');
        $standard->setCheckItems([
            'required_data' => [
                'enabled' => true,
                'required' => true,
                'weight' => 100,
                'criteria' => [],
            ],
        ]);
        $standard->setIsActive(true);
        $standard->setPriority(80);
        $this->qualityStandardRepository->save($standard);

        $task = new QualityTask();
        $task->setTaskName('缺少数据测试');
        $task->setTaskType('quality_check');
        $task->setData([]);

        $checkData = [
            'product_info' => ['category' => 'electronics'],
            // 缺少 required_data
        ];

        $result = $this->service->performQualityCheck($task, $checkData);

        // 缺少数据应该导致失败
        $this->assertIsArray($result);
        $this->assertEquals('fail', $result['overall_result']);
        $this->assertIsArray($result['defects']);
        $this->assertNotEmpty($result['defects']);
        $this->assertIsArray($result['defects'][0]);
        $this->assertEquals('缺少检查数据', $result['defects'][0]['description']);
    }
}
