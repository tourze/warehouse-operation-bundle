<?php

declare(strict_types=1);

namespace Tourze\WarehouseOperationBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Tourze\WarehouseOperationBundle\Entity\CountTask;
use Tourze\WarehouseOperationBundle\Entity\InboundTask;
use Tourze\WarehouseOperationBundle\Entity\OutboundTask;
use Tourze\WarehouseOperationBundle\Entity\QualityTask;
use Tourze\WarehouseOperationBundle\Entity\TransferTask;
use Tourze\WarehouseOperationBundle\Entity\WarehouseTask;
use Tourze\WarehouseOperationBundle\Enum\TaskStatus;
use Tourze\WarehouseOperationBundle\Enum\TaskType;
use Tourze\WarehouseOperationBundle\Event\TaskAssignedEvent;
use Tourze\WarehouseOperationBundle\Event\TaskCompletedEvent;
use Tourze\WarehouseOperationBundle\Event\TaskCreatedEvent;
use Tourze\WarehouseOperationBundle\Exception\TaskNotFoundException;
use Tourze\WarehouseOperationBundle\Exception\TaskStatusException;
use Tourze\WarehouseOperationBundle\Repository\WarehouseTaskRepository;

/**
 * 任务管理核心服务
 *
 * 实现TaskManagerInterface，提供仓库任务的完整生命周期管理。
 */
#[WithMonologChannel(channel: 'warehouse_operation')]
class TaskManager implements TaskManagerInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private EventDispatcherInterface $eventDispatcher,
        private ConfigService $configService,
        private WarehouseTaskRepository $taskRepository,
        private ?TaskSchedulingServiceInterface $schedulingService = null,
        private ?LoggerInterface $logger = null,
    ) {
    }

    public function createTask(TaskType $type, array $data): WarehouseTask
    {
        $task = match ($type) {
            TaskType::INBOUND => new InboundTask(),
            TaskType::OUTBOUND => new OutboundTask(),
            TaskType::QUALITY => new QualityTask(),
            TaskType::COUNT => new CountTask(),
            TaskType::TRANSFER => new TransferTask(),
        };

        $task->setType($type);
        $task->setStatus(TaskStatus::PENDING);
        $task->setData($data);

        $this->entityManager->persist($task);
        $this->entityManager->flush();

        $this->eventDispatcher->dispatch(new TaskCreatedEvent($task, 'system', 'task_manager'));

        return $task;
    }

    public function assignTask(int $taskId, int $workerId): bool
    {
        $task = $this->findTaskOrThrow($taskId);

        $this->validateTaskStatusForAssignment($task);

        $task->setAssignedWorker($workerId);
        $task->setStatus(TaskStatus::ASSIGNED);
        $task->setAssignedAt(new \DateTimeImmutable());

        $this->entityManager->flush();

        $this->eventDispatcher->dispatch(new TaskAssignedEvent($task, $workerId, 'system', 'manual'));

        return true;
    }

    /**
     * 智能批量任务分配
     *
     * 使用调度服务为多个待处理任务进行智能分配
     *
     * @param array<string, mixed> $constraints 调度约束条件
     * @param int|null $limit 最大处理任务数量
     * @return array<string, mixed> 批量分配结果
     */
    public function assignTasksIntelligently(array $constraints = [], ?int $limit = null): array
    {
        if (null === $this->schedulingService) {
            $this->logger?->warning('任务调度服务未配置，回退到基础分配模式');

            return $this->assignTasksBasic($limit);
        }

        // 获取待分配任务
        $pendingTasks = $this->findTasksByStatus(TaskStatus::PENDING, $limit);

        if (0 === count($pendingTasks)) {
            return [
                'assignments' => [],
                'unassigned' => [],
                'statistics' => [
                    'total_tasks' => 0,
                    'assigned_count' => 0,
                    'assignment_rate' => 0.0,
                    'processing_time_ms' => 0,
                ],
                'recommendations' => ['message' => '无待分配任务'],
            ];
        }

        try {
            // 使用调度服务进行智能分配
            $result = $this->schedulingService->scheduleTaskBatch($pendingTasks, $constraints);

            // 应用分配结果到数据库
            /** @var array<array<string, mixed>> $assignments */
            $assignments = is_array($result['assignments'] ?? null) ? $result['assignments'] : [];
            $assignmentCount = $this->applySchedulingResults($assignments);

            $statistics = is_array($result['statistics'] ?? null) ? $result['statistics'] : [];
            $assignmentRateRaw = $statistics['assignment_rate'] ?? null;
            $assignmentRate = is_float($assignmentRateRaw) ? $assignmentRateRaw : 0.0;

            $this->logger?->info('智能任务分配完成', [
                'total_tasks' => count($pendingTasks),
                'assigned_count' => $assignmentCount,
                'assignment_rate' => $assignmentRate,
            ]);

            return $result;
        } catch (\Exception $e) {
            $this->logger?->error('智能任务分配失败，回退到基础模式', [
                'error' => $e->getMessage(),
                'task_count' => count($pendingTasks),
            ]);

            return $this->assignTasksBasic($limit);
        }
    }

    /**
     * 使用智能调度重新计算任务优先级
     *
     * @param array<string, mixed> $context 优先级重算上下文
     * @return array<string, mixed> 优先级更新结果
     */
    public function recalculateTaskPriorities(array $context = []): array
    {
        if (null === $this->schedulingService) {
            $this->logger?->warning('任务调度服务未配置，跳过优先级重算');

            return ['updated_count' => 0, 'priority_changes' => []];
        }

        try {
            $result = $this->schedulingService->recalculatePriorities($context);

            $this->logger?->info('任务优先级重算完成', [
                'updated_count' => $result['updated_count'],
                'trigger_reason' => $context['trigger_reason'] ?? 'manual',
            ]);

            return $result;
        } catch (\Exception $e) {
            $this->logger?->error('任务优先级重算失败', [
                'error' => $e->getMessage(),
                'context' => $context,
            ]);

            return ['updated_count' => 0, 'priority_changes' => [], 'error' => $e->getMessage()];
        }
    }

    /**
     * 基于技能的智能作业员分配
     *
     * @param int $taskId 任务ID
     * @return array<string, mixed>|null 分配结果，null表示无法分配
     */
    public function assignWorkerBySkill(int $taskId): ?array
    {
        if (null === $this->schedulingService) {
            $this->logger?->warning('任务调度服务未配置，无法进行技能匹配');

            return null;
        }

        $task = $this->findTaskOrThrow($taskId);
        $this->validateTaskStatusForAssignment($task);

        try {
            assert(null !== $this->schedulingService); // PHPStan type assertion
            $result = $this->schedulingService->assignWorkerBySkill($task);

            if (null === $result) {
                $this->logger?->info('未找到合适的技能匹配作业员', ['task_id' => $taskId]);

                return null;
            }

            // 应用分配结果
            $workerIdRaw = $result['worker_id'] ?? null;
            $workerId = is_int($workerIdRaw) ? $workerIdRaw : null;
            if (null === $workerId) {
                return null;
            }

            $task->setAssignedWorker($workerId);
            $task->setStatus(TaskStatus::ASSIGNED);
            $task->setAssignedAt(new \DateTimeImmutable());

            $this->entityManager->flush();
            $this->eventDispatcher->dispatch(new TaskAssignedEvent(
                $task,
                $workerId,
                'system',
                'skill_based'
            ));

            $this->logger?->info('技能匹配分配成功', [
                'task_id' => $taskId,
                'worker_id' => $workerId,
                'match_score' => $result['match_score'],
                'assignment_reason' => $result['assignment_reason'],
            ]);

            return $result;
        } catch (\Exception $e) {
            $this->logger?->error('技能匹配分配失败', [
                'task_id' => $taskId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * 获取调度队列状态
     *
     * @return array<string, mixed> 调度队列状态信息
     */
    public function getSchedulingQueueStatus(): array
    {
        if (null === $this->schedulingService) {
            // 返回基础统计信息
            $pendingCount = $this->taskRepository->count(['status' => TaskStatus::PENDING]);
            $assignedCount = $this->taskRepository->count(['status' => TaskStatus::ASSIGNED]);
            $inProgressCount = $this->taskRepository->count(['status' => TaskStatus::IN_PROGRESS]);

            return [
                'pending_count' => $pendingCount,
                'active_count' => $assignedCount + $inProgressCount,
                'worker_utilization' => [],
                'queue_health' => $pendingCount > 100 ? 'warning' : 'healthy',
                'message' => '调度服务未配置，仅提供基础统计',
            ];
        }

        try {
            return $this->schedulingService->getSchedulingQueueStatus();
        } catch (\Exception $e) {
            $this->logger?->error('获取调度队列状态失败', ['error' => $e->getMessage()]);

            return [
                'pending_count' => 0,
                'active_count' => 0,
                'worker_utilization' => [],
                'queue_health' => 'error',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * 紧急任务插入处理
     *
     * @param int $taskId 紧急任务ID
     * @param array<string, mixed> $urgencyLevel 紧急级别配置
     * @return array<string, mixed> 插入处理结果
     */
    public function handleUrgentTask(int $taskId, array $urgencyLevel = []): array
    {
        $task = $this->findTaskOrThrow($taskId);

        if (null === $this->schedulingService) {
            // 基础紧急处理：提高优先级
            $task->setPriority(max(90, $task->getPriority()));
            $this->entityManager->flush();

            $this->logger?->info('基础紧急任务处理完成', ['task_id' => $taskId]);

            return [
                'assigned' => false,
                'priority_updated' => true,
                'impact_analysis' => ['message' => '调度服务未配置，仅提高优先级'],
            ];
        }

        try {
            $result = $this->schedulingService->handleUrgentTaskInsertion($task, $urgencyLevel);

            $this->logger?->info('紧急任务处理完成', [
                'task_id' => $taskId,
                'assigned' => $result['assigned'],
            ]);

            return $result;
        } catch (\Exception $e) {
            $this->logger?->error('紧急任务处理失败', [
                'task_id' => $taskId,
                'error' => $e->getMessage(),
            ]);

            return ['assigned' => false, 'error' => $e->getMessage()];
        }
    }

    public function completeTask(int $taskId, array $result): bool
    {
        $task = $this->findTaskOrThrow($taskId);

        $this->validateTaskStatusForCompletion($task);

        $task->setStatus(TaskStatus::COMPLETED);
        $task->setCompletedAt(new \DateTimeImmutable());
        $task->setData($result);

        $this->entityManager->flush();

        $this->eventDispatcher->dispatch(new TaskCompletedEvent(
            $task,
            $task->getAssignedWorker() ?? 0,
            new \DateTimeImmutable(),
            $result
        ));

        return true;
    }

    public function pauseTask(int $taskId, string $reason): bool
    {
        $task = $this->findTaskOrThrow($taskId);

        $this->validateTaskStatusForPause($task);

        $data = $task->getData();
        $data['previous_status'] = $task->getStatus()->value;
        $task->setData($data);

        $task->setStatus(TaskStatus::PAUSED);
        $task->setNotes($reason);

        $this->entityManager->flush();

        return true;
    }

    public function resumeTask(int $taskId): bool
    {
        $task = $this->findTaskOrThrow($taskId);

        if (TaskStatus::PAUSED !== $task->getStatus()) {
            throw new TaskStatusException('任务状态不允许恢复: ' . strtoupper($task->getStatus()->value));
        }

        $data = $task->getData();
        $previousStatusValue = $data['previous_status'] ?? TaskStatus::PENDING->value;
        $statusValue = is_string($previousStatusValue) || is_int($previousStatusValue) ? $previousStatusValue : TaskStatus::PENDING->value;
        $previousStatus = TaskStatus::from($statusValue);

        $task->setStatus($previousStatus);
        $task->setNotes(null);

        $this->entityManager->flush();

        return true;
    }

    public function cancelTask(int $taskId, string $reason): bool
    {
        $task = $this->findTaskOrThrow($taskId);

        $this->validateTaskStatusForCancellation($task);

        $task->setStatus(TaskStatus::CANCELLED);
        $task->setNotes($reason);

        $this->entityManager->flush();

        return true;
    }

    /**
     * @return array<WarehouseTask>
     */
    public function findTasksByStatus(TaskStatus $status, ?int $limit = null): array
    {
        $criteria = ['status' => $status];
        $orderBy = ['priority' => 'DESC', 'createdAt' => 'ASC'];

        /** @var array<WarehouseTask> */
        return $this->taskRepository->findBy($criteria, $orderBy, $limit);
    }

    /**
     * @return array<WarehouseTask>
     */
    public function findTimeoutTasks(?int $limit = null): array
    {
        $timeoutMinutes = $this->configService->getTaskTimeout();
        $timeoutBefore = new \DateTime("-{$timeoutMinutes} minutes");

        /** @var array<WarehouseTask> */
        return $this->taskRepository->findTimeoutTasks($timeoutBefore, null, $limit);
    }

    public function getMaxConcurrentTasks(): int
    {
        return $this->configService->getMaxConcurrentTasks();
    }

    /**
     * @return array<array<string, mixed>>
     */
    public function getTaskTrace(int $taskId): array
    {
        $task = $this->findTaskOrThrow($taskId);

        /** @var array<array<string, mixed>> */
        return $this->taskRepository->getTaskTrace($taskId);
    }

    /**
     * 批量任务重新分配（当作业员不可用时）
     *
     * @param array<int> $affectedTaskIds 受影响的任务ID列表
     * @param string $reason 重新分配原因
     * @return array<string, mixed> 重新分配结果
     */
    public function batchReassignTasks(array $affectedTaskIds, string $reason): array
    {
        if (null === $this->schedulingService) {
            $this->logger?->warning('任务调度服务未配置，无法进行批量重分配');

            // 基础处理：将任务状态重置为PENDING
            /** @var WarehouseTask[] $tasks */
            $tasks = $this->taskRepository->findBy(['id' => $affectedTaskIds]);
            $successCount = 0;

            foreach ($tasks as $task) {
                if (TaskStatus::ASSIGNED === $task->getStatus()) {
                    $task->setStatus(TaskStatus::PENDING);
                    $task->setAssignedWorker(null);
                    $task->setAssignedAt(null);
                    ++$successCount;
                }
            }

            $this->entityManager->flush();

            return [
                'successful_reassignments' => $successCount,
                'failed_reassignments' => [],
                'new_assignments' => [],
                'estimated_delay' => 0,
                'message' => '基础重分配完成，任务已重置为待分配状态',
            ];
        }

        try {
            $result = $this->schedulingService->batchReassignTasks($affectedTaskIds, $reason);

            $this->logger?->info('批量任务重分配完成', [
                'affected_count' => count($affectedTaskIds),
                'successful_count' => $result['successful_reassignments'],
                'reason' => $reason,
            ]);

            return $result;
        } catch (\Exception $e) {
            $this->logger?->error('批量任务重分配失败', [
                'affected_task_ids' => $affectedTaskIds,
                'reason' => $reason,
                'error' => $e->getMessage(),
            ]);

            return [
                'successful_reassignments' => 0,
                'failed_reassignments' => $affectedTaskIds,
                'new_assignments' => [],
                'estimated_delay' => 0,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * 应用调度结果到数据库
     *
     * @param array<array<string, mixed>> $assignments 调度分配结果
     * @return int 成功分配的任务数量
     */
    private function applySchedulingResults(array $assignments): int
    {
        $assignmentCount = 0;

        foreach ($assignments as $assignment) {
            try {
                $taskId = is_int($assignment['task_id'] ?? null) ? $assignment['task_id'] : null;
                $workerId = is_int($assignment['worker_id'] ?? null) ? $assignment['worker_id'] : null;

                if (null === $taskId || null === $workerId) {
                    continue;
                }

                /** @var WarehouseTask|null $task */
                $task = $this->taskRepository->find($taskId);
                if (null === $task || TaskStatus::PENDING !== $task->getStatus()) {
                    continue;
                }

                $task->setAssignedWorker($workerId);
                $task->setStatus(TaskStatus::ASSIGNED);
                $task->setAssignedAt(new \DateTimeImmutable());

                $this->eventDispatcher->dispatch(new TaskAssignedEvent(
                    $task,
                    $workerId,
                    'system',
                    'intelligent_assignment'
                ));
                ++$assignmentCount;
            } catch (\Exception $e) {
                $this->logger?->warning('应用单个任务分配失败', [
                    'assignment' => $assignment,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->entityManager->flush();

        return $assignmentCount;
    }

    /**
     * 基础任务分配模式（不使用智能调度）
     *
     * @param int|null $limit 最大处理任务数量
     * @return array<string, mixed> 基础分配结果
     */
    private function assignTasksBasic(?int $limit = null): array
    {
        $pendingTasks = $this->findTasksByStatus(TaskStatus::PENDING, $limit ?? 50);

        if (0 === count($pendingTasks)) {
            return [
                'assignments' => [],
                'unassigned' => [],
                'statistics' => [
                    'total_tasks' => 0,
                    'assigned_count' => 0,
                    'assignment_rate' => 0.0,
                    'processing_time_ms' => 0,
                ],
                'recommendations' => ['message' => '无待分配任务'],
            ];
        }

        // 基础分配逻辑：按优先级排序，但不进行实际分配
        usort($pendingTasks, fn ($a, $b) => $b->getPriority() <=> $a->getPriority());

        return [
            'assignments' => [], // 基础模式不进行自动分配
            'unassigned' => $pendingTasks,
            'statistics' => [
                'total_tasks' => count($pendingTasks),
                'assigned_count' => 0,
                'assignment_rate' => 0.0,
                'processing_time_ms' => 0,
            ],
            'recommendations' => [
                'message' => '基础模式：任务已按优先级排序，请手动分配',
                'highest_priority_task' => $pendingTasks[0]->getId(),
            ],
        ];
    }

    private function findTaskOrThrow(int $taskId): WarehouseTask
    {
        /** @var WarehouseTask|null $task */
        $task = $this->taskRepository->find($taskId);

        if (null === $task) {
            throw new TaskNotFoundException('任务未找到: ' . $taskId);
        }

        return $task;
    }

    private function validateTaskStatusForAssignment(WarehouseTask $task): void
    {
        $allowedStatuses = [TaskStatus::PENDING];
        if (!in_array($task->getStatus(), $allowedStatuses, true)) {
            throw new TaskStatusException('任务状态不允许分配: ' . strtoupper($task->getStatus()->value));
        }
    }

    private function validateTaskStatusForCompletion(WarehouseTask $task): void
    {
        $allowedStatuses = [TaskStatus::ASSIGNED, TaskStatus::IN_PROGRESS];
        if (!in_array($task->getStatus(), $allowedStatuses, true)) {
            throw new TaskStatusException('任务状态不允许完成: ' . strtoupper($task->getStatus()->value));
        }
    }

    private function validateTaskStatusForPause(WarehouseTask $task): void
    {
        $allowedStatuses = [TaskStatus::ASSIGNED, TaskStatus::IN_PROGRESS];
        if (!in_array($task->getStatus(), $allowedStatuses, true)) {
            throw new TaskStatusException('任务状态不允许暂停: ' . strtoupper($task->getStatus()->value));
        }
    }

    private function validateTaskStatusForCancellation(WarehouseTask $task): void
    {
        $disallowedStatuses = [TaskStatus::COMPLETED, TaskStatus::CANCELLED];
        if (in_array($task->getStatus(), $disallowedStatuses, true)) {
            throw new TaskStatusException('任务状态不允许取消: ' . strtoupper($task->getStatus()->value));
        }
    }
}
