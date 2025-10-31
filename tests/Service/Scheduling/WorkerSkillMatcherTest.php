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
use Tourze\WarehouseOperationBundle\Repository\WorkerSkillRepository;
use Tourze\WarehouseOperationBundle\Service\Scheduling\WorkerSkillMatcher;

/**
 * WorkerSkillMatcher 单元测试
 *
 * 测试作业员技能匹配服务的功能，包括技能匹配、作业员分配、得分计算等核心逻辑。
 * @internal
 */
#[CoversClass(WorkerSkillMatcher::class)]
#[RunTestsInSeparateProcesses]
class WorkerSkillMatcherTest extends AbstractIntegrationTestCase
{
    private WorkerSkillMatcher $service;

    private WorkerSkillRepository $workerSkillRepository;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&LoggerInterface
     */
    private LoggerInterface $logger;

    protected function onSetUp(): void
    {
        $this->workerSkillRepository = parent::getService(WorkerSkillRepository::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        // 直接创建服务实例，使用Mock依赖验证日志行为
        // @phpstan-ignore integrationTest.noDirectInstantiationOfCoveredClass
        $this->service = new WorkerSkillMatcher(
            $this->workerSkillRepository,
            $this->logger
        );
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Scheduling\WorkerSkillMatcher::calculateSkillMatch
     */
    public function testCalculateSkillMatchWithNoRequiredSkills(): void
    {
        $requiredSkills = [];
        $workerSkills = [
            $this->createWorkerSkill('picking', 8, 85),
        ];

        $result = $this->service->calculateSkillMatch($requiredSkills, $workerSkills);

        // 无技能要求时应该返回基础分数
        $this->assertEquals(0.8, $result);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Scheduling\WorkerSkillMatcher::calculateSkillMatch
     */
    public function testCalculateSkillMatchWithPerfectMatch(): void
    {
        $requiredSkills = ['picking'];
        $workerSkills = [
            $this->createWorkerSkill('picking', 10, 100),
        ];

        $result = $this->service->calculateSkillMatch($requiredSkills, $workerSkills);

        $this->assertIsFloat($result);
        $this->assertGreaterThan(0.0, $result);
        $this->assertLessThanOrEqual(1.0, $result);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Scheduling\WorkerSkillMatcher::calculateSkillMatch
     */
    public function testCalculateSkillMatchWithPartialMatch(): void
    {
        $requiredSkills = ['picking', 'packing'];
        $workerSkills = [
            $this->createWorkerSkill('picking', 8, 85),
        ];

        $result = $this->service->calculateSkillMatch($requiredSkills, $workerSkills);

        $this->assertIsFloat($result);
        $this->assertGreaterThan(0.0, $result);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Scheduling\WorkerSkillMatcher::calculateSkillMatch
     */
    public function testCalculateSkillMatchWithNoMatch(): void
    {
        $requiredSkills = ['quality'];
        $workerSkills = [
            $this->createWorkerSkill('picking', 8, 85),
        ];

        $result = $this->service->calculateSkillMatch($requiredSkills, $workerSkills);

        $this->assertIsFloat($result);
        $this->assertEquals(0.0, $result);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Scheduling\WorkerSkillMatcher::calculateSkillMatch
     */
    public function testCalculateSkillMatchWithMultipleSkills(): void
    {
        $requiredSkills = ['picking', 'packing'];
        $workerSkills = [
            $this->createWorkerSkill('picking', 9, 90),
            $this->createWorkerSkill('packing', 8, 85),
        ];

        $result = $this->service->calculateSkillMatch($requiredSkills, $workerSkills);

        $this->assertIsFloat($result);
        $this->assertGreaterThan(0.0, $result);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Scheduling\WorkerSkillMatcher::assignWorkerBySkill
     */
    public function testAssignWorkerBySkillWithValidMatch(): void
    {
        $task = new InboundTask();
        $task->setType(TaskType::QUALITY);
        $task->setPriority(80);

        $workerSkill = new WorkerSkill();
        $workerSkill->setWorkerId(123);
        $workerSkill->setWorkerName('Quality Expert');
        $workerSkill->setSkillCategory('quality');
        $workerSkill->setSkillLevel(9);
        $workerSkill->setSkillScore(95);
        $this->workerSkillRepository->save($workerSkill);

        $result = $this->service->assignWorkerBySkill($task);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('worker_id', $result);
        $this->assertArrayHasKey('worker_name', $result);
        $this->assertArrayHasKey('match_score', $result);
        $this->assertArrayHasKey('assignment_reason', $result);
        $this->assertArrayHasKey('estimated_completion', $result);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Scheduling\WorkerSkillMatcher::assignWorkerBySkill
     */
    public function testAssignWorkerBySkillWithNoSkillRequired(): void
    {
        $task = new InboundTask();
        $task->setType(TaskType::TRANSFER);
        $task->setPriority(70);

        $result = $this->service->assignWorkerBySkill($task);

        // TRANSFER类型在getBaseSkillsByTaskType中返回空数组
        $this->assertNull($result);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Scheduling\WorkerSkillMatcher::assignWorkerBySkill
     */
    public function testAssignWorkerBySkillWithExcludedWorkers(): void
    {
        $task = new InboundTask();
        $task->setType(TaskType::QUALITY);
        $task->setPriority(75);

        $workerSkill = new WorkerSkill();
        $workerSkill->setWorkerId(456);
        $workerSkill->setWorkerName('Excluded Worker');
        $workerSkill->setSkillCategory('quality');
        $workerSkill->setSkillLevel(8);
        $workerSkill->setSkillScore(85);
        $this->workerSkillRepository->save($workerSkill);

        $options = [
            'exclude_workers' => [456],
        ];

        $result = $this->service->assignWorkerBySkill($task, $options);

        $this->assertNull($result);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Scheduling\WorkerSkillMatcher::assignWorkerBySkill
     */
    public function testAssignWorkerBySkillWithCustomWeights(): void
    {
        $task = new InboundTask();
        $task->setType(TaskType::QUALITY);
        $task->setPriority(80);

        $workerSkill = new WorkerSkill();
        $workerSkill->setWorkerId(789);
        $workerSkill->setWorkerName('Quality Worker');
        $workerSkill->setSkillCategory('quality');
        $workerSkill->setSkillLevel(8);
        $workerSkill->setSkillScore(85);
        $this->workerSkillRepository->save($workerSkill);

        $options = [
            'skill_weight' => 0.6,
            'workload_weight' => 0.2,
            'location_weight' => 0.1,
            'performance_weight' => 0.1,
        ];

        $result = $this->service->assignWorkerBySkill($task, $options);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('match_score', $result);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Scheduling\WorkerSkillMatcher::assignWorkerBySkill
     */
    public function testAssignWorkerBySkillWithSpecialSkills(): void
    {
        $task = new InboundTask();
        $task->setType(TaskType::INBOUND);
        $task->setPriority(70);
        $task->setData([
            'hazardous' => true,
            'cold_storage' => true,
        ]);

        $hazardousWorker = new WorkerSkill();
        $hazardousWorker->setWorkerId(101);
        $hazardousWorker->setWorkerName('Hazardous Handler');
        $hazardousWorker->setSkillCategory('hazardous');
        $hazardousWorker->setSkillLevel(8);
        $hazardousWorker->setSkillScore(85);
        $this->workerSkillRepository->save($hazardousWorker);

        $result = $this->service->assignWorkerBySkill($task);

        $this->assertIsArray($result);
        $this->assertEquals(101, $result['worker_id']);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Scheduling\WorkerSkillMatcher::calculateSkillMatch
     */
    public function testCalculateSkillMatchWithDifferentLevels(): void
    {
        $requiredSkills = ['picking'];

        $highLevelSkills = [
            $this->createWorkerSkill('picking', 9, 90),
        ];

        $lowLevelSkills = [
            $this->createWorkerSkill('picking', 5, 50),
        ];

        $highScore = $this->service->calculateSkillMatch($requiredSkills, $highLevelSkills);
        $lowScore = $this->service->calculateSkillMatch($requiredSkills, $lowLevelSkills);

        // 高等级技能应该获得更高分数
        $this->assertGreaterThan($lowScore, $highScore);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Scheduling\WorkerSkillMatcher::calculateSkillMatch
     */
    public function testCalculateSkillMatchWithHighWeightSkill(): void
    {
        // quality技能权重是1.2（高于其他技能）
        $requiredSkills = ['quality'];
        $workerSkills = [
            $this->createWorkerSkill('quality', 8, 80),
        ];

        $qualityScore = $this->service->calculateSkillMatch($requiredSkills, $workerSkills);

        // picking技能权重是1.0
        $requiredSkillsPicking = ['picking'];
        $workerSkillsPicking = [
            $this->createWorkerSkill('picking', 8, 80),
        ];

        $pickingScore = $this->service->calculateSkillMatch($requiredSkillsPicking, $workerSkillsPicking);

        // 高权重技能应该获得更高分数
        $this->assertGreaterThan($pickingScore, $qualityScore);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Scheduling\WorkerSkillMatcher::assignWorkerBySkill
     */
    public function testAssignWorkerBySkillLogsDebugInformation(): void
    {
        $task = new InboundTask();
        $task->setType(TaskType::QUALITY);
        $task->setPriority(80);

        $workerSkill = new WorkerSkill();
        $workerSkill->setWorkerId(202);
        $workerSkill->setWorkerName('Test Worker');
        $workerSkill->setSkillCategory('quality');
        $workerSkill->setSkillLevel(8);
        $workerSkill->setSkillScore(85);
        $this->workerSkillRepository->save($workerSkill);

        $this->logger
            ->expects($this->atLeastOnce())
            ->method('debug')
        ;

        $this->service->assignWorkerBySkill($task);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Scheduling\WorkerSkillMatcher::assignWorkerBySkill
     */
    public function testAssignWorkerBySkillEstimatedCompletionStructure(): void
    {
        $task = new InboundTask();
        $task->setType(TaskType::QUALITY);
        $task->setPriority(80);

        $workerSkill = new WorkerSkill();
        $workerSkill->setWorkerId(303);
        $workerSkill->setWorkerName('Skilled Worker');
        $workerSkill->setSkillCategory('quality');
        $workerSkill->setSkillLevel(9);
        $workerSkill->setSkillScore(90);
        $this->workerSkillRepository->save($workerSkill);

        $result = $this->service->assignWorkerBySkill($task);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('estimated_completion', $result);
        $this->assertIsArray($result['estimated_completion']);

        $completion = $result['estimated_completion'];
        $this->assertIsArray($completion);

        $this->assertArrayHasKey('estimated_minutes', $completion);
        $this->assertArrayHasKey('completion_time', $completion);
        $this->assertArrayHasKey('confidence_level', $completion);

        $this->assertIsInt($completion['estimated_minutes']);
        $this->assertInstanceOf(\DateTimeImmutable::class, $completion['completion_time']);
        $this->assertIsFloat($completion['confidence_level']);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Scheduling\WorkerSkillMatcher::calculateSkillMatch
     */
    public function testCalculateSkillMatchWithInvalidSkillData(): void
    {
        $requiredSkills = ['picking'];
        // 这个测试不再适用于强类型的WorkerSkill对象
        // 因为WorkerSkill实体的setters需要正确的类型
        $workerSkills = [];

        $result = $this->service->calculateSkillMatch($requiredSkills, $workerSkills);

        // 空数组应该返回0.0
        $this->assertIsFloat($result);
        $this->assertEquals(0.0, $result);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Scheduling\WorkerSkillMatcher::calculateSkillMatch
     */
    public function testCalculateSkillMatchWithEmptyWorkerSkills(): void
    {
        $requiredSkills = ['picking'];
        $workerSkills = [];

        $result = $this->service->calculateSkillMatch($requiredSkills, $workerSkills);

        $this->assertEquals(0.0, $result);
    }

    public function testServiceConstructorAndBasicFunctionality(): void
    {
        // 验证服务可以正确实例化
        $this->assertInstanceOf(WorkerSkillMatcher::class, $this->service);

        // 验证基本功能工作正常
        $requiredSkills = ['picking'];
        $workerSkills = [
            $this->createWorkerSkill('picking', 8, 85),
        ];

        $score = $this->service->calculateSkillMatch($requiredSkills, $workerSkills);
        $this->assertIsFloat($score);
    }

    /**
     * 创建WorkerSkill实体对象的辅助方法
     */
    private function createWorkerSkill(string $category, int $level, int $score): WorkerSkill
    {
        $skill = new WorkerSkill();
        $skill->setSkillCategory($category);
        $skill->setSkillLevel($level);
        $skill->setSkillScore($score);
        $skill->setWorkerId(1);
        $skill->setWorkerName('Test Worker');
        return $skill;
    }
}
