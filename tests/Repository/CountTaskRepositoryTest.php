<?php

namespace Tourze\WarehouseOperationBundle\Tests\Repository;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;
use Tourze\WarehouseOperationBundle\Entity\CountTask;
use Tourze\WarehouseOperationBundle\Enum\TaskStatus;
use Tourze\WarehouseOperationBundle\Repository\CountTaskRepository;

/**
 * CountTaskRepository 单元测试
 *
 * @internal
 */
#[CoversClass(CountTaskRepository::class)]
#[RunTestsInSeparateProcesses]
class CountTaskRepositoryTest extends AbstractRepositoryTestCase
{
    public function testFindByCountPlanShouldReturnCorrectResults(): void
    {
        $repository = $this->getRepository();

        // 创建测试数据
        $task1 = new CountTask();
        $task1->setTaskType('count');

        $task1->setStatus(TaskStatus::PENDING);

        $task1->setPriority(10);

        $task1->setCountPlanId(1);

        $task1->setTaskSequence(1);

        $task2 = new CountTask();
        $task2->setTaskType('count');

        $task2->setStatus(TaskStatus::COMPLETED);

        $task2->setPriority(20);

        $task2->setCountPlanId(1);

        $task2->setTaskSequence(2);

        $task3 = new CountTask();
        $task3->setTaskType('count');

        $task3->setStatus(TaskStatus::PENDING);

        $task3->setPriority(30);

        $task3->setCountPlanId(2);

        $task3->setTaskSequence(1);

        $repository->save($task1);
        $repository->save($task2);
        $repository->save($task3);

        // 测试按盘点计划查找
        $results = $repository->findByCountPlan(1);

        self::assertCount(2, $results);
        self::assertArrayHasKey(0, $results, 'Results array should have element at index 0');
        self::assertArrayHasKey(1, $results, 'Results array should have element at index 1');
        self::assertSame(1, $results[0]->getTaskSequence());
        self::assertSame(2, $results[1]->getTaskSequence());
    }

    public function testFindByCountPlanAndStatusShouldReturnCorrectResults(): void
    {
        $repository = $this->getRepository();

        // 创建测试数据
        $task1 = new CountTask();
        $task1->setTaskType('count');

        $task1->setStatus(TaskStatus::PENDING);

        $task1->setPriority(10);

        $task1->setCountPlanId(1);

        $task2 = new CountTask();
        $task2->setTaskType('count');

        $task2->setStatus(TaskStatus::COMPLETED);

        $task2->setPriority(20);

        $task2->setCountPlanId(1);

        $repository->save($task1);
        $repository->save($task2);

        // 测试按盘点计划和状态查找
        $results = $repository->findByCountPlanAndStatus(1, 'pending');

        self::assertCount(1, $results);
        self::assertArrayHasKey(0, $results, 'Results array should have element at index 0');
        self::assertSame('pending', $results[0]->getStatus()->value);
    }

    public function testCountTaskStatusByPlanShouldReturnCorrectStats(): void
    {
        $repository = $this->getRepository();

        // 创建测试数据
        $task1 = new CountTask();
        $task1->setTaskType('count');

        $task1->setStatus(TaskStatus::PENDING);

        $task1->setPriority(10);

        $task1->setCountPlanId(1);

        $task2 = new CountTask();
        $task2->setTaskType('count');

        $task2->setStatus(TaskStatus::PENDING);

        $task2->setPriority(20);

        $task2->setCountPlanId(1);

        $task3 = new CountTask();
        $task3->setTaskType('count');

        $task3->setStatus(TaskStatus::COMPLETED);

        $task3->setPriority(30);

        $task3->setCountPlanId(1);

        $repository->save($task1);
        $repository->save($task2);
        $repository->save($task3);

        // 测试统计任务状态
        $results = $repository->countTaskStatusByPlan(1);

        self::assertArrayHasKey('pending', $results);
        self::assertArrayHasKey('completed', $results);
        self::assertSame(2, $results['pending']);
        self::assertSame(1, $results['completed']);
    }

    public function testFindByLocationShouldReturnCorrectResults(): void
    {
        $repository = $this->getRepository();

        $now = new \DateTimeImmutable();
        $oneHourAgo = $now->modify('-1 hour');
        $twoDaysAgo = $now->modify('-2 days');

        // 创建测试数据
        $task1 = new CountTask();
        $task1->setTaskType('count');

        $task1->setStatus(TaskStatus::PENDING);

        $task1->setPriority(10);

        $task1->setLocationCode('A001');

        $task1->setCreateTime($oneHourAgo);

        $task2 = new CountTask();
        $task2->setTaskType('count');

        $task2->setStatus(TaskStatus::COMPLETED);

        $task2->setPriority(20);

        $task2->setLocationCode('A001');

        $task2->setCreateTime($twoDaysAgo);

        $task3 = new CountTask();
        $task3->setTaskType('count');

        $task3->setStatus(TaskStatus::PENDING);

        $task3->setPriority(30);

        $task3->setLocationCode('B002');

        $task3->setCreateTime($oneHourAgo);

        $repository->save($task1);
        $repository->save($task2);
        $repository->save($task3);

        // 测试不带时间过滤的查询
        $results = $repository->findByLocation('A001');
        self::assertCount(2, $results);
        // 应该按创建时间DESC排序
        self::assertArrayHasKey(0, $results, 'Results array should have element at index 0');
        self::assertArrayHasKey(1, $results, 'Results array should have element at index 1');
        self::assertSame('A001', $results[0]->getLocationCode());
        self::assertSame('A001', $results[1]->getLocationCode());

        // 测试带时间过滤的查询
        $resultsWithSince = $repository->findByLocation('A001', $twoDaysAgo->modify('-1 hour'));
        self::assertCount(2, $resultsWithSince);

        // 测试时间过滤效果
        $recentResults = $repository->findByLocation('A001', $oneHourAgo->modify('+30 minutes'));
        self::assertCount(0, $recentResults); // 应该过滤掉所有结果

        // 测试不存在的库位
        $noResults = $repository->findByLocation('NONEXISTENT');
        self::assertCount(0, $noResults);
    }

    public function testFindDiscrepancyTasksShouldReturnOnlyDiscrepancies(): void
    {
        $repository = $this->getRepository();

        // 创建测试数据
        $discrepancyTask1 = new CountTask();
        $discrepancyTask1->setTaskType('count');

        $discrepancyTask1->setStatus(TaskStatus::DISCREPANCY_FOUND);

        $discrepancyTask1->setPriority(90);

        $discrepancyTask1->setCountPlanId(1);

        $discrepancyTask2 = new CountTask();
        $discrepancyTask2->setTaskType('count');

        $discrepancyTask2->setStatus(TaskStatus::DISCREPANCY_FOUND);

        $discrepancyTask2->setPriority(80);

        $discrepancyTask2->setCountPlanId(2);

        $normalTask = new CountTask();
        $normalTask->setTaskType('count');

        $normalTask->setStatus(TaskStatus::COMPLETED);

        $normalTask->setPriority(70);

        $normalTask->setCountPlanId(1);

        $repository->save($discrepancyTask1);
        $repository->save($discrepancyTask2);
        $repository->save($normalTask);

        // 测试查找所有差异任务
        $allDiscrepancies = $repository->findDiscrepancyTasks();
        self::assertCount(2, $allDiscrepancies);
        // 应该按优先级DESC排序
        self::assertSame(90, $allDiscrepancies[0]->getPriority());
        self::assertSame(80, $allDiscrepancies[1]->getPriority());

        // 测试按计划ID查找差异任务
        $plan1Discrepancies = $repository->findDiscrepancyTasks(1);
        self::assertCount(1, $plan1Discrepancies);
        self::assertSame(1, $plan1Discrepancies[0]->getCountPlanId());

        // 测试不存在的计划ID
        $noDiscrepancies = $repository->findDiscrepancyTasks(999);
        self::assertCount(0, $noDiscrepancies);
    }

    public function testFindHighPriorityPendingTasksShouldReturnCorrectResults(): void
    {
        $repository = $this->getRepository();

        $oldTime = new \DateTimeImmutable('-1 hour');
        $newTime = new \DateTimeImmutable();

        // 创建测试数据
        $highPriorityPending = new CountTask();
        $highPriorityPending->setTaskType('count');

        $highPriorityPending->setStatus(TaskStatus::PENDING);

        $highPriorityPending->setPriority(85);

        $highPriorityPending->setCreateTime($oldTime);

        $highPriorityAssigned = new CountTask();
        $highPriorityAssigned->setTaskType('count');

        $highPriorityAssigned->setStatus(TaskStatus::ASSIGNED);

        $highPriorityAssigned->setPriority(90);

        $highPriorityAssigned->setCreateTime($newTime);

        $lowPriorityPending = new CountTask();
        $lowPriorityPending->setTaskType('count');

        $lowPriorityPending->setStatus(TaskStatus::PENDING);

        $lowPriorityPending->setPriority(50);

        $highPriorityCompleted = new CountTask();
        $highPriorityCompleted->setTaskType('count');

        $highPriorityCompleted->setStatus(TaskStatus::COMPLETED);

        $highPriorityCompleted->setPriority(95);

        $repository->save($highPriorityPending);
        $repository->save($highPriorityAssigned);
        $repository->save($lowPriorityPending);
        $repository->save($highPriorityCompleted);

        // 测试默认参数（优先级>=80）
        $results = $repository->findHighPriorityPendingTasks();
        self::assertCount(2, $results); // pending 和 assigned 状态的高优先级任务

        // 应该按优先级DESC，创建时间ASC排序
        self::assertSame(90, $results[0]->getPriority());
        self::assertSame(85, $results[1]->getPriority());

        // 测试自定义最低优先级
        $customResults = $repository->findHighPriorityPendingTasks(60);
        self::assertCount(2, $customResults); // 不应该包含已完成的任务

        // 测试限制数量
        $limitedResults = $repository->findHighPriorityPendingTasks(80, 1);
        self::assertCount(1, $limitedResults);
        self::assertSame(90, $limitedResults[0]->getPriority());
    }

    public function testFindOverdueTasksShouldReturnOnlyOverdue(): void
    {
        $repository = $this->getRepository();

        $now = new \DateTimeImmutable();
        $overdueTime = $now->modify('-25 hours'); // 超过24小时
        $recentTime = $now->modify('-1 hour'); // 未超时

        // 创建测试数据
        $overdueTask1 = new CountTask();
        $overdueTask1->setTaskType('count');

        $overdueTask1->setStatus(TaskStatus::PENDING);

        $overdueTask1->setPriority(10);

        $overdueTask1->setCreateTime($overdueTime);

        $overdueTask2 = new CountTask();
        $overdueTask2->setTaskType('count');

        $overdueTask2->setStatus(TaskStatus::IN_PROGRESS);

        $overdueTask2->setPriority(20);

        $overdueTask2->setCreateTime($overdueTime->modify('-1 hour'));

        $recentTask = new CountTask();
        $recentTask->setTaskType('count');

        $recentTask->setStatus(TaskStatus::ASSIGNED);

        $recentTask->setPriority(30);

        $recentTask->setCreateTime($recentTime);

        $completedTask = new CountTask();
        $completedTask->setTaskType('count');

        $completedTask->setStatus(TaskStatus::COMPLETED);

        $completedTask->setPriority(40);

        $completedTask->setCreateTime($overdueTime);

        $repository->save($overdueTask1);
        $repository->save($overdueTask2);
        $repository->save($recentTask);
        $repository->save($completedTask);

        // 测试默认超时时间（24小时）
        $results = $repository->findOverdueTasks();
        self::assertCount(2, $results);

        // 应该按创建时间ASC排序（最早的在前面）
        self::assertTrue($results[0]->getCreateTime() <= $results[1]->getCreateTime());

        // 测试自定义超时时间
        $shortTimeoutResults = $repository->findOverdueTasks(1); // 1小时
        self::assertCount(2, $shortTimeoutResults); // 只有超过1小时且状态为待处理的任务

        // 测试长超时时间
        $longTimeoutResults = $repository->findOverdueTasks(48); // 48小时
        self::assertCount(0, $longTimeoutResults); // 没有超过48小时的待处理任务
    }

    public function testFindRecountTasksShouldReturnOnlyRecountTasks(): void
    {
        $repository = $this->getRepository();

        // 创建测试数据
        $recountPending = new CountTask();
        $recountPending->setTaskType('recount');

        $recountPending->setStatus(TaskStatus::PENDING);

        $recountPending->setPriority(90);

        $recountPending->setTaskName('复盘任务1');

        $recountAssigned = new CountTask();
        $recountAssigned->setTaskType('recount');

        $recountAssigned->setStatus(TaskStatus::ASSIGNED);

        $recountAssigned->setPriority(80);

        $recountAssigned->setTaskName('复盘任务2');

        $recountCompleted = new CountTask();
        $recountCompleted->setTaskType('recount');

        $recountCompleted->setStatus(TaskStatus::COMPLETED);

        $recountCompleted->setPriority(95);

        $recountCompleted->setTaskName('复盘任务3');

        $normalCount = new CountTask();
        $normalCount->setTaskType('count');

        $normalCount->setStatus(TaskStatus::PENDING);

        $normalCount->setPriority(85);

        $repository->save($recountPending);
        $repository->save($recountAssigned);
        $repository->save($recountCompleted);
        $repository->save($normalCount);

        // 测试查找复盘任务
        $results = $repository->findRecountTasks();
        self::assertCount(2, $results); // 只返回pending和assigned状态的复盘任务

        // 应该按优先级DESC排序
        self::assertSame(90, $results[0]->getPriority());
        self::assertSame(80, $results[1]->getPriority());

        // 验证返回的都是复盘任务
        foreach ($results as $task) {
            self::assertStringContainsString('复盘', $task->getTaskName());
            self::assertTrue(in_array($task->getStatus()->value, ['pending', 'assigned'], true));
        }
    }

    public function testSaveAndRemoveShouldWork(): void
    {
        $repository = $this->getRepository();

        $task = new CountTask();
        $task->setTaskType('count');

        $task->setStatus(TaskStatus::PENDING);

        $task->setPriority(10);

        $task->setLocationCode('A001');

        // 测试保存
        $repository->save($task);
        self::assertNotNull($task->getId());

        $savedId = $task->getId();

        // 测试删除
        $repository->remove($task);

        $deletedTask = $repository->find($savedId);
        self::assertNull($deletedTask);
    }

    protected function getRepository(): CountTaskRepository
    {
        return self::getService(CountTaskRepository::class);
    }

    protected function onSetUp(): void
    {
        // Repository tests don't require additional setup
    }

    protected function createNewEntity(): object
    {
        $task = new CountTask();
        $task->setTaskType('count');

        $task->setStatus(TaskStatus::PENDING);

        $task->setPriority(10);

        $task->setLocationCode('TEST001');

        return $task;
    }

    protected function getRepositoryClass(): string
    {
        return CountTaskRepository::class;
    }

    protected function getEntityClass(): string
    {
        return CountTask::class;
    }
}
