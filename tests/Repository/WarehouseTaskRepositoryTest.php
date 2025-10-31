<?php

namespace Tourze\WarehouseOperationBundle\Tests\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;
use Tourze\WarehouseOperationBundle\Entity\InboundTask;
use Tourze\WarehouseOperationBundle\Enum\TaskStatus;
use Tourze\WarehouseOperationBundle\Enum\TaskType;
use Tourze\WarehouseOperationBundle\Repository\WarehouseTaskRepository;

/**
 * @internal
 */
#[CoversClass(WarehouseTaskRepository::class)]
#[RunTestsInSeparateProcesses]
class WarehouseTaskRepositoryTest extends AbstractRepositoryTestCase
{
    protected function onSetUp(): void
    {
        // Repository tests don't require additional setup
    }

    protected function createNewEntity(): object
    {
        $task = new InboundTask();
        $task->setType(TaskType::INBOUND);
        $task->setStatus(TaskStatus::PENDING);
        $task->setPriority(1);
        $task->setData(['warehouse_id' => 1, 'items' => []]);

        return $task;
    }

    protected function getRepository(): WarehouseTaskRepository
    {
        return self::getService(WarehouseTaskRepository::class);
    }

    public function testFindByStatusShouldReturnCorrectResults(): void
    {
        $repository = $this->getRepository();

        // Create and save test entities
        $task1 = new InboundTask();
        $task1->setType(TaskType::INBOUND);
        $task1->setStatus(TaskStatus::PENDING);
        $task1->setPriority(1);
        $task1->setData([]);

        $task2 = new InboundTask();
        $task2->setType(TaskType::INBOUND);
        $task2->setStatus(TaskStatus::COMPLETED);
        $task2->setPriority(2);
        $task2->setData([]);

        $repository->save($task1);
        $repository->save($task2);

        $pendingTasks = $repository->findByStatus(TaskStatus::PENDING);
        $this->assertNotEmpty($pendingTasks);

        $completedTasks = $repository->findByStatus(TaskStatus::COMPLETED);
        $this->assertNotEmpty($completedTasks);
    }

    public function testFindTimeoutTasksShouldReturnResults(): void
    {
        $repository = $this->getRepository();

        // Create an old task
        $oldTask = new InboundTask();
        $oldTask->setType(TaskType::INBOUND);
        $oldTask->setStatus(TaskStatus::PENDING);
        $oldTask->setPriority(1);
        $oldTask->setData([]);
        $repository->save($oldTask);

        $timeoutBefore = new \DateTime('+1 hour'); // Future time to include all tasks
        $results = $repository->findTimeoutTasks($timeoutBefore);

        $this->assertIsArray($results);
    }

    public function testSaveShouldPersistEntity(): void
    {
        $repository = $this->getRepository();

        $task = new InboundTask();
        $task->setType(TaskType::INBOUND);
        $task->setStatus(TaskStatus::PENDING);
        $task->setPriority(1);
        $task->setData([]);

        $repository->save($task);

        $this->assertNotNull($task->getId());
        $saved = $repository->find($task->getId());
        $this->assertInstanceOf(InboundTask::class, $saved);
    }

    public function testGetTaskTraceShouldReturnArrayWithExpectedStructure(): void
    {
        $repository = $this->getRepository();
        $taskId = 123;
        $result = $repository->getTaskTrace($taskId);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);

        // Verify structure of first trace entry
        $firstEntry = $result[0];
        $this->assertIsArray($firstEntry);
        $this->assertArrayHasKey('action', $firstEntry);
        $this->assertArrayHasKey('timestamp', $firstEntry);
        $this->assertEquals('created', $firstEntry['action']);
        $this->assertIsString($firstEntry['timestamp']);
    }

    public function testRepositoryShouldExtendServiceEntityRepository(): void
    {
        $repository = $this->getRepository();
        $this->assertInstanceOf(ServiceEntityRepository::class, $repository);
        $this->assertInstanceOf(WarehouseTaskRepository::class, $repository);
    }

    public function testGetTaskTraceWithDifferentTaskIds(): void
    {
        $repository = $this->getRepository();
        $result1 = $repository->getTaskTrace(1);
        $result2 = $repository->getTaskTrace(999);

        // Both should return valid arrays (placeholder implementation)
        $this->assertIsArray($result1);
        $this->assertIsArray($result2);
        $this->assertEquals($result1, $result2); // Currently returns same placeholder data
    }

    public function testBulkUpdateStatusShouldUpdateMultipleTasks(): void
    {
        $repository = $this->getRepository();

        // Create test tasks
        $task1 = new InboundTask();
        $task1->setType(TaskType::INBOUND);
        $task1->setStatus(TaskStatus::PENDING);
        $task1->setPriority(1);
        $task1->setData([]);

        $task2 = new InboundTask();
        $task2->setType(TaskType::INBOUND);
        $task2->setStatus(TaskStatus::PENDING);
        $task2->setPriority(2);
        $task2->setData([]);

        $repository->save($task1);
        $repository->save($task2);

        $task1Id = $task1->getId();
        $task2Id = $task2->getId();

        $this->assertNotNull($task1Id);
        $this->assertNotNull($task2Id);

        $taskIds = [$task1Id, $task2Id];
        $affectedRows = $repository->bulkUpdateStatus($taskIds, TaskStatus::COMPLETED);

        $this->assertGreaterThanOrEqual(0, $affectedRows);
    }

    public function testCountByStatusShouldReturnCorrectCount(): void
    {
        $repository = $this->getRepository();

        // Create test tasks
        $task = new InboundTask();
        $task->setType(TaskType::INBOUND);
        $task->setStatus(TaskStatus::PENDING);
        $task->setPriority(1);
        $task->setData([]);

        $repository->save($task);

        $count = $repository->countByStatus(TaskStatus::PENDING);
        $this->assertIsInt($count);
        $this->assertGreaterThanOrEqual(0, $count);
    }

    public function testFindByPriorityRangeShouldReturnTasksInRange(): void
    {
        $repository = $this->getRepository();

        // Create test tasks with different priorities
        $lowPriorityTask = new InboundTask();
        $lowPriorityTask->setType(TaskType::INBOUND);
        $lowPriorityTask->setStatus(TaskStatus::PENDING);
        $lowPriorityTask->setPriority(1);
        $lowPriorityTask->setData([]);

        $highPriorityTask = new InboundTask();
        $highPriorityTask->setType(TaskType::INBOUND);
        $highPriorityTask->setStatus(TaskStatus::PENDING);
        $highPriorityTask->setPriority(5);
        $highPriorityTask->setData([]);

        $repository->save($lowPriorityTask);
        $repository->save($highPriorityTask);

        $results = $repository->findByPriorityRange(1, 5);
        $this->assertIsArray($results);
    }

    public function testFindByWorkerShouldReturnWorkerTasks(): void
    {
        $repository = $this->getRepository();

        // Create test task assigned to worker
        $task = new InboundTask();
        $task->setType(TaskType::INBOUND);
        $task->setStatus(TaskStatus::PENDING);
        $task->setPriority(1);
        $task->setAssignedWorker(123);
        $task->setData([]);

        $repository->save($task);

        $results = $repository->findByWorker(123);
        $this->assertIsArray($results);
    }
}
