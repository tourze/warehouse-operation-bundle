<?php

declare(strict_types=1);

namespace Tourze\WarehouseOperationBundle\Tests\Service\Scheduling;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Psr\Log\LoggerInterface;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\WarehouseOperationBundle\Entity\InboundTask;
use Tourze\WarehouseOperationBundle\Entity\WorkerSkill;
use Tourze\WarehouseOperationBundle\Enum\TaskType;
use Tourze\WarehouseOperationBundle\Repository\WarehouseTaskRepository;
use Tourze\WarehouseOperationBundle\Repository\WorkerSkillRepository;
use Tourze\WarehouseOperationBundle\Service\Scheduling\WorkerAssignmentService;

/**
 * WorkerAssignmentService 单元测试
 *
 * 测试作业员分配服务的功能，包括技能匹配、工作负载均衡、最优分配等核心逻辑。
 * 验证服务的正确性、匹配算法和边界条件处理。
 * @internal
 */
#[CoversClass(WorkerAssignmentService::class)]
#[RunTestsInSeparateProcesses]
class WorkerAssignmentServiceTest extends AbstractIntegrationTestCase
{
    private WorkerAssignmentService $service;

    private WarehouseTaskRepository $taskRepository;

    private WorkerSkillRepository $workerSkillRepository;

    private LoggerInterface $logger;

    protected function onSetUp(): void
    {
        $this->taskRepository = parent::getService(WarehouseTaskRepository::class);
        $this->workerSkillRepository = parent::getService(WorkerSkillRepository::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        // 设置Mock的Logger到容器中
        parent::getContainer()->set(LoggerInterface::class, $this->logger);
        $this->service = parent::getService(WorkerAssignmentService::class);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Scheduling\WorkerAssignmentService::assignTaskToOptimalWorker
     */
    public function testAssignTaskToOptimalWorkerWithNoEligibleWorkers(): void
    {
        $task = new InboundTask();
        $task->setType(TaskType::INBOUND);

        $availableWorkers = [
            ['worker_id' => 1, 'current_workload' => 15, 'availability' => 'busy'],
            ['worker_id' => 2, 'current_workload' => 12, 'availability' => 'available'],
        ];

        $constraints = ['max_tasks_per_worker' => 10];

        $result = $this->service->assignTaskToOptimalWorker($task, $availableWorkers, $constraints);

        $this->assertNull($result);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Scheduling\WorkerAssignmentService::assignTaskToOptimalWorker
     */
    public function testAssignTaskToOptimalWorkerWithEligibleWorkers(): void
    {
        $task = new InboundTask();
        $task->setType(TaskType::INBOUND);
        $task->setPriority(75);
        $this->taskRepository->save($task);

        $availableWorkers = [
            [
                'worker_id' => 1,
                'name' => 'Worker A',
                'current_workload' => 5,
                'availability' => 'available',
                'skills' => [
                    ['category' => 'receiving', 'level' => 8, 'score' => 85],
                ],
            ],
            [
                'worker_id' => 2,
                'name' => 'Worker B',
                'current_workload' => 8,
                'availability' => 'available',
                'skills' => [
                    ['category' => 'receiving', 'level' => 6, 'score' => 70],
                ],
            ],
        ];

        $constraints = ['max_tasks_per_worker' => 10];

        $result = $this->service->assignTaskToOptimalWorker($task, $availableWorkers, $constraints);

        $this->assertNotNull($result);
        $this->assertArrayHasKey('task_id', $result);
        $this->assertArrayHasKey('worker_id', $result);
        $this->assertArrayHasKey('worker_name', $result);
        $this->assertArrayHasKey('match_score', $result);
        $this->assertArrayHasKey('assignment_time', $result);
        $this->assertArrayHasKey('estimated_completion', $result);
        $this->assertArrayHasKey('assignment_factors', $result);

        $this->assertEquals($task->getId(), $result['task_id']);
        $this->assertContainsEquals($result['worker_id'], [1, 2]);
        $this->assertInstanceOf(\DateTimeImmutable::class, $result['assignment_time']);
        $this->assertIsFloat($result['match_score']);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Scheduling\WorkerAssignmentService::assignWorkerBySkill
     */
    public function testAssignWorkerBySkillWithNoRequiredSkills(): void
    {
        $task = new InboundTask();
        $task->setType(TaskType::TRANSFER); // 这个类型在getBaseSkillsByTaskType中会返回空数组
        $this->taskRepository->save($task);

        $result = $this->service->assignWorkerBySkill($task);

        $this->assertNull($result);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Scheduling\WorkerAssignmentService::assignWorkerBySkill
     */
    public function testAssignWorkerBySkillWithValidSkills(): void
    {
        $task = new InboundTask();
        $task->setType(TaskType::QUALITY);
        $task->setPriority(80);
        $this->taskRepository->save($task);

        // 创建模拟的WorkerSkill实体
        $workerSkill = new WorkerSkill();
        $workerSkill->setWorkerId(123);
        $workerSkill->setWorkerName('Quality Expert');
        $workerSkill->setSkillCategory('quality');
        $workerSkill->setSkillLevel(9);
        $workerSkill->setSkillScore(95);
        $workerSkill->setCertifications(['quality' => 'advanced']);
        $this->workerSkillRepository->save($workerSkill);

        $options = [
            'skill_weight' => 0.6,
            'workload_weight' => 0.2,
            'location_weight' => 0.1,
            'performance_weight' => 0.1,
        ];

        $result = $this->service->assignWorkerBySkill($task, $options);

        $this->assertNotNull($result);
        $this->assertArrayHasKey('worker_id', $result);
        $this->assertArrayHasKey('worker_name', $result);
        $this->assertArrayHasKey('match_score', $result);
        $this->assertArrayHasKey('assignment_reason', $result);
        $this->assertArrayHasKey('estimated_completion', $result);
        $this->assertArrayHasKey('skill_analysis', $result);

        $this->assertEquals(123, $result['worker_id']);
        $this->assertEquals('Quality Expert', $result['worker_name']);
        $this->assertIsFloat($result['match_score']);

        // 验证技能分析结构
        /** @var array<mixed> $skillAnalysis */
        $skillAnalysis = $result['skill_analysis'];
        $this->assertArrayHasKey('required_skills', $skillAnalysis);
        $this->assertArrayHasKey('worker_skills', $skillAnalysis);
        $this->assertArrayHasKey('match_details', $skillAnalysis);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Scheduling\WorkerAssignmentService::assignWorkerBySkill
     */
    public function testAssignWorkerBySkillWithExcludedWorkers(): void
    {
        $task = new InboundTask();
        $task->setType(TaskType::QUALITY);
        $this->taskRepository->save($task);

        $workerSkill = new WorkerSkill();
        $workerSkill->setWorkerId(456);
        $workerSkill->setWorkerName('Excluded Worker');
        $workerSkill->setSkillCategory('quality');
        $workerSkill->setSkillLevel(8);
        $workerSkill->setSkillScore(80);
        $this->workerSkillRepository->save($workerSkill);

        $options = [
            'exclude_workers' => [456], // 排除这个作业员
        ];

        $result = $this->service->assignWorkerBySkill($task, $options);

        // 由于作业员被排除，应该找不到匹配
        $this->assertNull($result);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Scheduling\WorkerAssignmentService::calculateTaskWorkerMatch
     */
    public function testCalculateTaskWorkerMatchWithGoodMatch(): void
    {
        $task = new InboundTask();
        $task->setType(TaskType::OUTBOUND);
        $task->setPriority(70);

        $worker = [
            'worker_id' => 1,
            'current_workload' => 3,
            'skills' => [
                ['category' => 'picking', 'level' => 8, 'score' => 85],
                ['category' => 'packing', 'level' => 7, 'score' => 80],
            ],
        ];

        $matchScore = $this->service->calculateTaskWorkerMatch($task, $worker);

        $this->assertIsFloat($matchScore);
        $this->assertGreaterThan(0, $matchScore);
        $this->assertLessThanOrEqual(1, $matchScore);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Scheduling\WorkerAssignmentService::calculateTaskWorkerMatch
     */
    public function testCalculateTaskWorkerMatchWithHighWorkload(): void
    {
        $task = new InboundTask();
        $task->setType(TaskType::INBOUND);
        $task->setPriority(50);

        $highWorkloadWorker = [
            'worker_id' => 1,
            'current_workload' => 9, // 接近最大值10
            'skills' => [
                ['category' => 'receiving', 'level' => 9, 'score' => 90],
            ],
        ];

        $lowWorkloadWorker = [
            'worker_id' => 2,
            'current_workload' => 2,
            'skills' => [
                ['category' => 'receiving', 'level' => 9, 'score' => 90],
            ],
        ];

        $highWorkloadScore = $this->service->calculateTaskWorkerMatch($task, $highWorkloadWorker);
        $lowWorkloadScore = $this->service->calculateTaskWorkerMatch($task, $lowWorkloadWorker);

        // 低工作负载的作业员应该获得更高分数
        $this->assertGreaterThan($highWorkloadScore, $lowWorkloadScore);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Scheduling\WorkerAssignmentService::calculateTaskWorkerMatch
     */
    public function testCalculateTaskWorkerMatchWithDifferentPriorities(): void
    {
        $highPriorityTask = new InboundTask();
        $highPriorityTask->setType(TaskType::QUALITY);
        $highPriorityTask->setPriority(95);

        $lowPriorityTask = new InboundTask();
        $lowPriorityTask->setType(TaskType::QUALITY);
        $lowPriorityTask->setPriority(30);

        $worker = [
            'worker_id' => 1,
            'current_workload' => 5,
            'skills' => [
                ['category' => 'quality', 'level' => 8, 'score' => 85],
            ],
        ];

        $highPriorityScore = $this->service->calculateTaskWorkerMatch($highPriorityTask, $worker);
        $lowPriorityScore = $this->service->calculateTaskWorkerMatch($lowPriorityTask, $worker);

        // 高优先级任务应该获得更高的匹配分数
        $this->assertGreaterThan($lowPriorityScore, $highPriorityScore);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Scheduling\WorkerAssignmentService::assignWorkerBySkill
     */
    public function testAssignWorkerBySkillWithSpecialRequirements(): void
    {
        $task = new InboundTask();
        $task->setType(TaskType::INBOUND);
        $task->setData([
            'hazardous' => true,
            'cold_storage' => true,
        ]);
        $this->taskRepository->save($task);

        // 创建具有特殊技能的作业员
        $hazardousWorker = new WorkerSkill();
        $hazardousWorker->setWorkerId(789);
        $hazardousWorker->setWorkerName('Hazardous Expert');
        $hazardousWorker->setSkillCategory('hazardous');
        $hazardousWorker->setSkillLevel(8);
        $hazardousWorker->setSkillScore(85);
        $this->workerSkillRepository->save($hazardousWorker);

        $coldStorageWorker = new WorkerSkill();
        $coldStorageWorker->setWorkerId(790);
        $coldStorageWorker->setWorkerName('Cold Storage Expert');
        $coldStorageWorker->setSkillCategory('cold_storage');
        $coldStorageWorker->setSkillLevel(9);
        $coldStorageWorker->setSkillScore(90);
        $this->workerSkillRepository->save($coldStorageWorker);

        $result = $this->service->assignWorkerBySkill($task);

        $this->assertNotNull($result);
        $this->assertContainsEquals($result['worker_id'], [789, 790]);

        // 验证分配原因反映了特殊技能要求
        /** @var array<mixed> $skillAnalysis */
        $skillAnalysis = $result['skill_analysis'];
        /** @var array<string> $requiredSkills */
        $requiredSkills = $skillAnalysis['required_skills'];
        $this->assertContainsEquals('hazardous', $requiredSkills);
        $this->assertContainsEquals('cold_storage', $requiredSkills);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Scheduling\WorkerAssignmentService::assignWorkerBySkill
     */
    public function testAssignWorkerBySkillWithMultipleSkillMatch(): void
    {
        $task = new InboundTask();
        $task->setType(TaskType::OUTBOUND); // 需要picking和packing技能
        $this->taskRepository->save($task);

        // 只有picking技能的作业员
        $pickingWorker = new WorkerSkill();
        $pickingWorker->setWorkerId(101);
        $pickingWorker->setWorkerName('Picker');
        $pickingWorker->setSkillCategory('picking');
        $pickingWorker->setSkillLevel(8);
        $pickingWorker->setSkillScore(85);
        $this->workerSkillRepository->save($pickingWorker);

        // 只有packing技能的作业员
        $packingWorker = new WorkerSkill();
        $packingWorker->setWorkerId(102);
        $packingWorker->setWorkerName('Packer');
        $packingWorker->setSkillCategory('packing');
        $packingWorker->setSkillLevel(7);
        $packingWorker->setSkillScore(80);
        $this->workerSkillRepository->save($packingWorker);

        $result = $this->service->assignWorkerBySkill($task);

        $this->assertNotNull($result);
        $this->assertContainsEquals($result['worker_id'], [101, 102]);

        // 验证技能分析显示了多个必需技能
        /** @var array<mixed> $skillAnalysis */
        $skillAnalysis = $result['skill_analysis'];
        /** @var array<string> $requiredSkills */
        $requiredSkills = $skillAnalysis['required_skills'];
        $this->assertContainsEquals('picking', $requiredSkills);
        $this->assertContainsEquals('packing', $requiredSkills);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Scheduling\WorkerAssignmentService::calculateTaskWorkerMatch
     */
    public function testCalculateTaskWorkerMatchWithNoSkills(): void
    {
        $task = new InboundTask();
        $task->setType(TaskType::COUNT);
        $task->setPriority(50);

        $worker = [
            'worker_id' => 1,
            'current_workload' => 5,
            'skills' => [], // 无技能
        ];

        $matchScore = $this->service->calculateTaskWorkerMatch($task, $worker);

        // 无技能时应该给予基础分数
        $this->assertIsFloat($matchScore);
        $this->assertGreaterThan(0, $matchScore);
    }

    public function testServiceConstructorAndBasicFunctionality(): void
    {
        // 验证服务可以正确实例化
        $this->assertInstanceOf(WorkerAssignmentService::class, $this->service);

        // 验证基本功能工作正常
        $task = new InboundTask();
        $task->setType(TaskType::INBOUND);
        $task->setPriority(50);

        $worker = [
            'worker_id' => 1,
            'current_workload' => 3,
            'skills' => [['category' => 'receiving', 'level' => 7, 'score' => 75]],
        ];

        $score = $this->service->calculateTaskWorkerMatch($task, $worker);
        $this->assertIsFloat($score);
        $this->assertGreaterThanOrEqual(0, $score);
    }

    /**
     * 测试作业员可用性过滤
     */
    public function testWorkerAvailabilityFiltering(): void
    {
        $task = new InboundTask();
        $task->setType(TaskType::INBOUND);

        $availableWorkers = [
            [
                'worker_id' => 1,
                'name' => 'Available Worker',
                'current_workload' => 5,
                'availability' => 'available',
                'skills' => [['category' => 'receiving', 'level' => 8, 'score' => 85]],
            ],
            [
                'worker_id' => 2,
                'name' => 'Busy Worker',
                'current_workload' => 3,
                'availability' => 'busy', // 不可用
                'skills' => [['category' => 'receiving', 'level' => 9, 'score' => 90]],
            ],
            [
                'worker_id' => 3,
                'name' => 'Overloaded Worker',
                'current_workload' => 15, // 超载
                'availability' => 'available',
                'skills' => [['category' => 'receiving', 'level' => 8, 'score' => 85]],
            ],
        ];

        $constraints = ['max_tasks_per_worker' => 10];

        $result = $this->service->assignTaskToOptimalWorker($task, $availableWorkers, $constraints);

        $this->assertNotNull($result);
        $this->assertEquals(1, $result['worker_id']); // 只有worker 1符合条件
        $this->assertEquals('Available Worker', $result['worker_name']);
    }

    /**
     * 测试技能权重的影响
     */
    public function testSkillWeightInfluence(): void
    {
        $task = new InboundTask();
        $task->setType(TaskType::QUALITY);
        $this->taskRepository->save($task);

        // Quality技能权重是1.2，应该比其他技能获得更高分数
        $qualityWorker = new WorkerSkill();
        $qualityWorker->setWorkerId(201);
        $qualityWorker->setWorkerName('Quality Specialist');
        $qualityWorker->setSkillCategory('quality'); // 高权重技能
        $qualityWorker->setSkillLevel(7);
        $qualityWorker->setSkillScore(80);
        $this->workerSkillRepository->save($qualityWorker);

        $countingWorker = new WorkerSkill();
        $countingWorker->setWorkerId(202);
        $countingWorker->setWorkerName('Counter');
        $countingWorker->setSkillCategory('counting'); // 低权重技能(0.8)
        $countingWorker->setSkillLevel(7);
        $countingWorker->setSkillScore(80);
        $this->workerSkillRepository->save($countingWorker);

        $result = $this->service->assignWorkerBySkill($task);

        $this->assertNotNull($result);
        // 由于quality技能权重更高，应该选择质检专员
        $this->assertEquals(201, $result['worker_id']);
    }

    /**
     * 测试估计完成时间计算
     */
    public function testEstimatedCompletionTime(): void
    {
        $task = new InboundTask();
        $task->setType(TaskType::QUALITY);
        $this->taskRepository->save($task);

        $highSkillWorker = new WorkerSkill();
        $highSkillWorker->setWorkerId(301);
        $highSkillWorker->setWorkerName('Expert');
        $highSkillWorker->setSkillCategory('quality');
        $highSkillWorker->setSkillLevel(9); // 高技能等级
        $highSkillWorker->setSkillScore(95);
        $this->workerSkillRepository->save($highSkillWorker);

        $result = $this->service->assignWorkerBySkill($task);

        $this->assertNotNull($result);
        $this->assertArrayHasKey('estimated_completion', $result);

        self::assertIsArray($result['estimated_completion']);
        /** @var array<string, mixed> $completion */
        $completion = $result['estimated_completion'];
        $this->assertArrayHasKey('estimated_minutes', $completion);
        $this->assertArrayHasKey('completion_time', $completion);
        $this->assertArrayHasKey('confidence_level', $completion);

        $this->assertIsInt($completion['estimated_minutes']);
        $this->assertInstanceOf(\DateTimeImmutable::class, $completion['completion_time']);
        $this->assertIsFloat($completion['confidence_level']);
    }

    /**
     * 测试认证奖励计算
     */
    public function testCertificationBonus(): void
    {
        $task = new InboundTask();
        $task->setType(TaskType::QUALITY);
        $this->taskRepository->save($task);

        $certifiedWorker = new WorkerSkill();
        $certifiedWorker->setWorkerId(401);
        $certifiedWorker->setWorkerName('Certified Expert');
        $certifiedWorker->setSkillCategory('quality');
        $certifiedWorker->setSkillLevel(7);
        $certifiedWorker->setSkillScore(80);
        $certifiedWorker->setCertifications(['quality' => 'advanced']); // 有认证
        $this->workerSkillRepository->save($certifiedWorker);

        $uncertifiedWorker = new WorkerSkill();
        $uncertifiedWorker->setWorkerId(402);
        $uncertifiedWorker->setWorkerName('Regular Worker');
        $uncertifiedWorker->setSkillCategory('quality');
        $uncertifiedWorker->setSkillLevel(7);
        $uncertifiedWorker->setSkillScore(80);
        $uncertifiedWorker->setCertifications([]); // 无认证
        $this->workerSkillRepository->save($uncertifiedWorker);

        $result = $this->service->assignWorkerBySkill($task);

        $this->assertNotNull($result);
        // 有认证的作业员应该被优先选择
        $this->assertEquals(401, $result['worker_id']);

        // 验证技能分析包含认证信息
        self::assertIsArray($result['skill_analysis']);
        /** @var array<string, mixed> $skillAnalysis */
        $skillAnalysis = $result['skill_analysis'];
        $this->assertArrayHasKey('match_details', $skillAnalysis);
        self::assertIsArray($skillAnalysis['match_details']);
        /** @var array<string, mixed> $matchDetails */
        $matchDetails = $skillAnalysis['match_details'];
        $this->assertArrayHasKey('certification_bonus', $matchDetails);
        $this->assertGreaterThan(0, $matchDetails['certification_bonus']);
    }
}
