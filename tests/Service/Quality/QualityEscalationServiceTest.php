<?php

namespace Tourze\WarehouseOperationBundle\Tests\Service\Quality;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\WarehouseOperationBundle\Entity\QualityTask;
use Tourze\WarehouseOperationBundle\Enum\TaskStatus;
use Tourze\WarehouseOperationBundle\Enum\TaskType;
use Tourze\WarehouseOperationBundle\Repository\WarehouseTaskRepository;
use Tourze\WarehouseOperationBundle\Service\Quality\QualityEscalationService;

/**
 * QualityEscalationService 单元测试
 *
 * @internal
 */
#[CoversClass(QualityEscalationService::class)]
class QualityEscalationServiceTest extends TestCase
{
    private QualityEscalationService $service;

    private WarehouseTaskRepository $repository;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(WarehouseTaskRepository::class);
        $this->service = new QualityEscalationService($this->repository);
    }

    /**
     * 测试服务正确创建
     */
    public function testServiceCreation(): void
    {
        $this->assertInstanceOf(QualityEscalationService::class, $this->service);
    }

    /**
     * 测试处理质检异常升级 - 高严重性
     */
    public function testEscalateQualityIssueHighSeverity(): void
    {
        $task = $this->createQualityTask();
        $escalationReason = [
            'severity' => 'critical',
            'issue_type' => 'safety_issue',
            'impact_scope' => 'multiple_batches',
        ];

        $this->repository->expects($this->once())
            ->method('save')
            ->with($task)
        ;

        $result = $this->service->escalateQualityIssue($task, $escalationReason);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('escalation_level', $result);
        $this->assertArrayHasKey('assigned_personnel', $result);
        $this->assertArrayHasKey('deadline', $result);
        $this->assertArrayHasKey('notification_sent', $result);

        // 严重安全问题，多批次影响应该有最高的升级级别
        $this->assertEquals(5, $result['escalation_level']);
        $this->assertIsArray($result['assigned_personnel']);
        $this->assertContainsEquals('quality_director', $result['assigned_personnel']);
        $this->assertContainsEquals('general_manager', $result['assigned_personnel']);
        $this->assertInstanceOf(\DateTimeImmutable::class, $result['deadline']);
        $this->assertTrue($result['notification_sent']);
    }

    /**
     * 测试处理质检异常升级 - 中等严重性
     */
    public function testEscalateQualityIssueMediumSeverity(): void
    {
        $task = $this->createQualityTask();
        $escalationReason = [
            'severity' => 'medium',
            'issue_type' => 'quality_defect',
            'impact_scope' => 'single_batch',
        ];

        $this->repository->expects($this->once())
            ->method('save')
            ->with($task)
        ;

        $result = $this->service->escalateQualityIssue($task, $escalationReason);

        $this->assertIsArray($result);
        $this->assertEquals(3, $result['escalation_level']); // medium(1) + quality_defect(1) + single_batch(1) = 3
        $this->assertIsArray($result['assigned_personnel']);
        $this->assertContainsEquals('quality_supervisor', $result['assigned_personnel']);
        $this->assertContainsEquals('shift_manager', $result['assigned_personnel']);
    }

    /**
     * 测试处理质检异常升级 - 低严重性
     */
    public function testEscalateQualityIssueLowSeverity(): void
    {
        $task = $this->createQualityTask();
        $escalationReason = [
            'severity' => 'low',
            'issue_type' => 'quality_defect',
            'impact_scope' => 'single_item',
        ];

        $this->repository->expects($this->once())
            ->method('save')
            ->with($task)
        ;

        $result = $this->service->escalateQualityIssue($task, $escalationReason);

        $this->assertIsArray($result);
        $this->assertEquals(1, $result['escalation_level']); // 0 + 1 + 0 = 1
        $this->assertIsArray($result['assigned_personnel']);
        $this->assertContainsEquals('quality_inspector', $result['assigned_personnel']);
    }

    /**
     * 测试处理质检异常升级 - 污染问题
     */
    public function testEscalateQualityIssueContamination(): void
    {
        $task = $this->createQualityTask();
        $escalationReason = [
            'severity' => 'high',
            'issue_type' => 'contamination',
            'impact_scope' => 'single_batch',
        ];

        $this->repository->expects($this->once())
            ->method('save')
            ->with($task)
        ;

        $result = $this->service->escalateQualityIssue($task, $escalationReason);

        $this->assertIsArray($result);
        $this->assertEquals(5, $result['escalation_level']); // 2 + 2 + 1 = 5, min(5, 5) = 5
        $this->assertIsArray($result['assigned_personnel']);
        $this->assertContainsEquals('quality_director', $result['assigned_personnel']);
        $this->assertContainsEquals('general_manager', $result['assigned_personnel']);
    }

    /**
     * 测试处理质检异常升级 - 缺失参数
     */
    public function testEscalateQualityIssueMissingParameters(): void
    {
        $task = $this->createQualityTask();
        $escalationReason = []; // 空参数

        $this->repository->expects($this->once())
            ->method('save')
            ->with($task)
        ;

        $result = $this->service->escalateQualityIssue($task, $escalationReason);

        // 应该使用默认值
        $this->assertIsArray($result);
        $this->assertEquals(4, $result['escalation_level']); // high(2) + quality_defect(1) + single_batch(1) = 4
        $this->assertIsArray($result['assigned_personnel']);
        $this->assertContainsEquals('quality_manager', $result['assigned_personnel']);
        $this->assertContainsEquals('operations_manager', $result['assigned_personnel']);
    }

    /**
     * 测试处理质检异常升级 - 无效参数类型
     */
    public function testEscalateQualityIssueInvalidParameterTypes(): void
    {
        $task = $this->createQualityTask();
        $escalationReason = [
            'severity' => 123, // 数字而不是字符串
            'issue_type' => null, // null而不是字符串
            'impact_scope' => [], // 数组而不是字符串
        ];

        $this->repository->expects($this->once())
            ->method('save')
            ->with($task)
        ;

        $result = $this->service->escalateQualityIssue($task, $escalationReason);

        // 应该使用默认值处理无效类型
        $this->assertIsInt($result['escalation_level']);
        $this->assertGreaterThanOrEqual(1, $result['escalation_level']);
        $this->assertLessThanOrEqual(5, $result['escalation_level']);
    }

    /**
     * 测试处理质检异常升级 - 检查任务数据更新
     */
    public function testEscalateQualityIssueTaskDataUpdate(): void
    {
        $task = $this->createQualityTask();
        $originalData = $task->getData();
        $escalationReason = [
            'severity' => 'high',
            'issue_type' => 'quality_defect',
            'impact_scope' => 'single_batch',
        ];

        $this->repository->expects($this->once())
            ->method('save')
            ->with(self::callback(function ($savedTask) use ($originalData) {
                $newData = $savedTask->getData();

                return $newData !== $originalData
                       && isset($newData['escalation'])
                       && is_array($newData['escalation']);
            }))
        ;

        $result = $this->service->escalateQualityIssue($task, $escalationReason);

        // 验证任务数据是否包含升级信息
        $updatedData = $task->getData();
        $this->assertArrayHasKey('escalation', $updatedData);

        $escalationData = $updatedData['escalation'];
        $this->assertIsArray($escalationData);
        $this->assertArrayHasKey('escalated_at', $escalationData);
        $this->assertArrayHasKey('escalation_level', $escalationData);
        $this->assertArrayHasKey('severity', $escalationData);
        $this->assertArrayHasKey('issue_type', $escalationData);
        $this->assertArrayHasKey('impact_scope', $escalationData);
        $this->assertArrayHasKey('assigned_personnel', $escalationData);
        $this->assertArrayHasKey('deadline', $escalationData);

        $this->assertInstanceOf(\DateTimeImmutable::class, $escalationData['escalated_at']);
        $this->assertInstanceOf(\DateTimeImmutable::class, $escalationData['deadline']);
        $this->assertEquals('high', $escalationData['severity']);
        $this->assertEquals('quality_defect', $escalationData['issue_type']);
        $this->assertEquals('single_batch', $escalationData['impact_scope']);
    }

    /**
     * 测试不同升级级别的截止时间计算
     */
    public function testEscalationDeadlineCalculation(): void
    {
        $testCases = [
            ['level' => 5, 'expected_hours' => 2, 'severity' => 'critical', 'issue_type' => 'contamination', 'impact_scope' => 'multiple_batches'],
            ['level' => 4, 'expected_hours' => 4, 'severity' => 'high', 'issue_type' => 'contamination', 'impact_scope' => 'single_item'],
            ['level' => 4, 'expected_hours' => 4, 'severity' => 'high', 'issue_type' => 'quality_defect', 'impact_scope' => 'single_batch'],
            ['level' => 4, 'expected_hours' => 4, 'severity' => 'medium', 'issue_type' => 'contamination', 'impact_scope' => 'single_batch'],
            ['level' => 3, 'expected_hours' => 8, 'severity' => 'medium', 'issue_type' => 'quality_defect', 'impact_scope' => 'single_batch'],
            ['level' => 1, 'expected_hours' => 48, 'severity' => 'low', 'issue_type' => 'quality_defect', 'impact_scope' => 'single_item'],
        ];

        foreach ($testCases as $case) {
            $task = $this->createQualityTask();

            // 使用预定义的escalationReason
            $escalationReason = [
                'severity' => $case['severity'],
                'issue_type' => $case['issue_type'],
                'impact_scope' => $case['impact_scope'],
            ];

            // 模拟不同的升级级别
            $this->repository->expects($this->once())
                ->method('save')
                ->with($task)
            ;

            $result = $this->service->escalateQualityIssue($task, $escalationReason);

            // 验证截止时间是否正确
            $deadline = $result['deadline'];

            // 从escalation数据中获取实际开始时间
            $taskData = $task->getData();
            self::assertIsArray($taskData);
            /** @var array<string, mixed> $taskData */
            $escalationData = $taskData['escalation'] ?? [];
            self::assertIsArray($escalationData);
            /** @var array<string, mixed> $escalationData */
            $escalatedAtTime = $escalationData['escalated_at'];

            $this->assertInstanceOf(\DateTimeImmutable::class, $deadline);
            $this->assertInstanceOf(\DateTimeImmutable::class, $escalatedAtTime);

            // 验证时间差是否正确（允许少量误差）
            $actualHoursDiff = ($deadline->getTimestamp() - $escalatedAtTime->getTimestamp()) / 3600;
            $this->assertEqualsWithDelta(
                $case['expected_hours'],
                $actualHoursDiff,
                0.01, // 允许36秒的误差
                "升级级别 {$case['level']} 的截止时间计算不正确，期望 {$case['expected_hours']} 小时，实际 {$actualHoursDiff} 小时"
            );

            // 重置模拟对象以进行下一次测试
            $this->setUp();
        }
    }

    /**
     * 测试升级人员分配
     */
    public function testEscalationPersonnelAssignment(): void
    {
        $testCases = [
            ['level' => 5, 'expected' => ['quality_director', 'general_manager'], 'severity' => 'critical', 'issue_type' => 'contamination', 'impact_scope' => 'multiple_batches'],
            ['level' => 4, 'expected' => ['quality_manager', 'operations_manager'], 'severity' => 'high', 'issue_type' => 'contamination', 'impact_scope' => 'single_item'],
            ['level' => 4, 'expected' => ['quality_manager', 'operations_manager'], 'severity' => 'high', 'issue_type' => 'quality_defect', 'impact_scope' => 'single_batch'],
            ['level' => 4, 'expected' => ['quality_manager', 'operations_manager'], 'severity' => 'medium', 'issue_type' => 'contamination', 'impact_scope' => 'single_batch'],
            ['level' => 3, 'expected' => ['quality_supervisor', 'shift_manager'], 'severity' => 'medium', 'issue_type' => 'quality_defect', 'impact_scope' => 'single_batch'],
            ['level' => 1, 'expected' => ['quality_inspector'], 'severity' => 'low', 'issue_type' => 'quality_defect', 'impact_scope' => 'single_item'],
        ];

        foreach ($testCases as $case) {
            $task = $this->createQualityTask();
            $escalationReason = [
                'severity' => $case['severity'],
                'issue_type' => $case['issue_type'],
                'impact_scope' => $case['impact_scope'],
            ];

            $this->repository->expects($this->once())
                ->method('save')
                ->with($task)
            ;

            $result = $this->service->escalateQualityIssue($task, $escalationReason);

            $this->assertEquals($case['expected'], $result['assigned_personnel']);

            // 重置模拟对象以进行下一次测试
            $this->setUp();
        }
    }

    /**
     * 创建测试用的质检任务
     */
    private function createQualityTask(): QualityTask
    {
        $task = new QualityTask();
        $task->setType(TaskType::QUALITY);
        $task->setStatus(TaskStatus::PENDING);
        $task->setPriority(50);
        $task->setData([]);
        $task->setId(1);

        return $task;
    }
}
