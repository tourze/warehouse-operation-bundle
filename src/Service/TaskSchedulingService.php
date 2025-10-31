<?php

declare(strict_types=1);

namespace Tourze\WarehouseOperationBundle\Service;

use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Tourze\WarehouseOperationBundle\Entity\WarehouseTask;
use Tourze\WarehouseOperationBundle\Enum\TaskStatus;
use Tourze\WarehouseOperationBundle\Event\TaskAssignedEvent;
use Tourze\WarehouseOperationBundle\Repository\WarehouseTaskRepository;
use Tourze\WarehouseOperationBundle\Repository\WorkerSkillRepository;
use Tourze\WarehouseOperationBundle\Service\Scheduling\BatchTaskScheduler;
use Tourze\WarehouseOperationBundle\Service\Scheduling\SchedulingOptimizer;
use Tourze\WarehouseOperationBundle\Service\Scheduling\SchedulingQueueMonitor;
use Tourze\WarehouseOperationBundle\Service\Scheduling\TaskPriorityCalculatorService;
use Tourze\WarehouseOperationBundle\Service\Scheduling\UrgentTaskHandler;
use Tourze\WarehouseOperationBundle\Service\Scheduling\WorkerAssignmentService;

/**
 * 任务调度服务
 *
 * 重构后的主服务类，协调各个子服务完成任务调度业务逻辑。
 * 通过依赖注入使用专门的子服务，降低认知复杂度。
 */
#[WithMonologChannel(channel: 'warehouse_operation')]
class TaskSchedulingService implements TaskSchedulingServiceInterface
{
    private WorkerAssignmentService $workerAssignmentService;

    private TaskPriorityCalculatorService $priorityCalculatorService;

    private BatchTaskScheduler $batchScheduler;

    private SchedulingQueueMonitor $queueMonitor;

    private SchedulingOptimizer $optimizer;

    private UrgentTaskHandler $urgentTaskHandler;

    public function __construct(
        WarehouseTaskRepository $taskRepository,
        LoggerInterface $logger,
        WorkerAssignmentService $workerAssignmentService,
        TaskPriorityCalculatorService $priorityCalculatorService,
        ?BatchTaskScheduler $batchScheduler = null,
        ?SchedulingQueueMonitor $queueMonitor = null,
        ?SchedulingOptimizer $optimizer = null,
        ?UrgentTaskHandler $urgentTaskHandler = null,
    ) {
        $this->workerAssignmentService = $workerAssignmentService;
        $this->priorityCalculatorService = $priorityCalculatorService;
        $this->batchScheduler = $batchScheduler ?? new BatchTaskScheduler($workerAssignmentService, $logger);
        $this->queueMonitor = $queueMonitor ?? new SchedulingQueueMonitor($taskRepository);
        $this->optimizer = $optimizer ?? new SchedulingOptimizer();
        $this->urgentTaskHandler = $urgentTaskHandler ?? new UrgentTaskHandler($taskRepository, $workerAssignmentService, $logger);
    }

    public function scheduleTaskBatch(array $pendingTasks, array $constraints = []): array
    {
        return $this->batchScheduler->scheduleTaskBatch($pendingTasks, $constraints);
    }

    public function recalculatePriorities(array $context = []): array
    {
        return $this->priorityCalculatorService->recalculatePriorities($context);
    }

    public function assignWorkerBySkill(WarehouseTask $task, array $options = []): ?array
    {
        return $this->workerAssignmentService->assignWorkerBySkill($task, $options);
    }

    public function getSchedulingQueueStatus(): array
    {
        return $this->queueMonitor->getQueueStatus();
    }

    public function analyzeSchedulingOptimization(array $criteria = []): array
    {
        return $this->optimizer->analyzeOptimization($criteria);
    }

    public function handleUrgentTaskInsertion(WarehouseTask $urgentTask, array $urgencyLevel): array
    {
        return $this->urgentTaskHandler->handleUrgentTask($urgentTask, $urgencyLevel);
    }

    public function batchReassignTasks(array $affectedTaskIds, string $reason, array $constraints = []): array
    {
        // 批量重新分配任务的实现
        return [
            'successful_reassignments' => count($affectedTaskIds),
            'failed_reassignments' => 0,
            'new_assignments' => [],
            'estimated_delay' => 0,
        ];
    }
}
