<?php

declare(strict_types=1);

namespace Tourze\WarehouseOperationBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;
use Tourze\WarehouseOperationBundle\Entity\WarehouseTask;
use Tourze\WarehouseOperationBundle\Enum\TaskStatus;

/**
 * WarehouseTask仓库类
 *
 * 提供WarehouseTask实体的数据访问层功能，包括复杂查询、批量操作和性能优化。
 * 严格遵循Repository模式，仅负责数据访问，不包含业务逻辑。
 *
 * @extends ServiceEntityRepository<WarehouseTask>
 */
#[AsRepository(entityClass: WarehouseTask::class)]
class WarehouseTaskRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WarehouseTask::class);
    }

    /**
     * 根据状态查找任务
     *
     * @param TaskStatus $status
     * @param int|null $limit
     * @return array<WarehouseTask>
     */
    public function findByStatus(TaskStatus $status, ?int $limit = null): array
    {
        $qb = $this->createQueryBuilder('t')
            ->where('t.status = :status')
            ->setParameter('status', $status)
            ->orderBy('t.createTime', 'DESC')
        ;

        if (null !== $limit) {
            $qb->setMaxResults($limit);
        }

        /** @var array<WarehouseTask> */
        return $qb->getQuery()->getResult();
    }

    /**
     * 获取任务轨迹
     *
     * @param int $taskId
     * @return array<array<string, mixed>>
     */
    public function getTaskTrace(int $taskId): array
    {
        // 这里只是占位实现，实际轨迹功能会在TaskTraceService中实现
        return [
            ['action' => 'created', 'timestamp' => '2025-08-27 10:00:00'],
        ];
    }

    /**
     * 根据优先级范围查找任务
     *
     * @param int $minPriority
     * @param int $maxPriority
     * @param int|null $limit
     * @return array<WarehouseTask>
     */
    public function findByPriorityRange(int $minPriority, int $maxPriority, ?int $limit = null): array
    {
        $qb = $this->createQueryBuilder('t')
            ->where('t.priority BETWEEN :minPriority AND :maxPriority')
            ->setParameter('minPriority', $minPriority)
            ->setParameter('maxPriority', $maxPriority)
            ->orderBy('t.priority', 'DESC')
            ->addOrderBy('t.createTime', 'DESC')
        ;

        if (null !== $limit) {
            $qb->setMaxResults($limit);
        }

        /** @var array<WarehouseTask> */
        return $qb->getQuery()->getResult();
    }

    /**
     * 根据作业员查找任务
     *
     * @param int $workerId
     * @param TaskStatus|null $status
     * @param int|null $limit
     * @return array<WarehouseTask>
     */
    public function findByWorker(int $workerId, ?TaskStatus $status = null, ?int $limit = null): array
    {
        $qb = $this->createQueryBuilder('t')
            ->where('t.assignedWorker = :workerId')
            ->setParameter('workerId', $workerId)
            ->orderBy('t.createTime', 'DESC')
        ;

        if (null !== $status) {
            $qb->andWhere('t.status = :status')
                ->setParameter('status', $status)
            ;
        }

        if (null !== $limit) {
            $qb->setMaxResults($limit);
        }

        /** @var array<WarehouseTask> */
        return $qb->getQuery()->getResult();
    }

    /**
     * 批量更新任务状态
     *
     * @param array<int> $taskIds
     * @param TaskStatus $newStatus
     * @return int 受影响的行数
     */
    public function bulkUpdateStatus(array $taskIds, TaskStatus $newStatus): int
    {
        if (0 === count($taskIds)) {
            return 0;
        }

        $affectedRows = $this->createQueryBuilder('t')
            ->update()
            ->set('t.status', ':newStatus')
            ->where('t.id IN (:taskIds)')
            ->setParameter('newStatus', $newStatus)
            ->setParameter('taskIds', $taskIds)
            ->getQuery()
            ->execute()
        ;

        // Doctrine execute() 可能返回 int|string|null，需要安全转换
        if (is_numeric($affectedRows)) {
            return (int) $affectedRows;
        }

        return 0;
    }

    /**
     * 根据状态统计任务数量
     *
     * @param TaskStatus $status
     * @return int
     */
    public function countByStatus(TaskStatus $status): int
    {
        $result = $this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->where('t.status = :status')
            ->setParameter('status', $status)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        return (int) $result;
    }

    /**
     * 查找超时的任务
     *
     * @param \DateTimeInterface $timeoutBefore
     * @param TaskStatus|null $status
     * @param int|null $limit
     * @return array<WarehouseTask>
     */
    public function findTimeoutTasks(\DateTimeInterface $timeoutBefore, ?TaskStatus $status = null, ?int $limit = null): array
    {
        $qb = $this->createQueryBuilder('t')
            ->where('t.createTime < :timeoutBefore')
            ->setParameter('timeoutBefore', $timeoutBefore)
            ->orderBy('t.createTime', 'ASC')
        ;

        if (null !== $status) {
            $qb->andWhere('t.status = :status')
                ->setParameter('status', $status)
            ;
        }

        if (null !== $limit) {
            $qb->setMaxResults($limit);
        }

        /** @var array<WarehouseTask> */
        return $qb->getQuery()->getResult();
    }

    /**
     * 获取任务统计信息
     *
     * @return array<string, int> 状态 => 数量的映射
     */
    public function getTaskStatistics(): array
    {
        /** @var array<array{status: TaskStatus, count: int|string}> */
        $result = $this->createQueryBuilder('t')
            ->select('t.status, COUNT(t.id) as count')
            ->groupBy('t.status')
            ->getQuery()
            ->getResult()
        ;

        $statistics = [];
        foreach ($result as $row) {
            $statistics[$row['status']->value] = (int) $row['count'];
        }

        return $statistics;
    }

    /**
     * 保存任务实体
     *
     * @param WarehouseTask $entity
     * @param bool $flush
     */
    public function save(WarehouseTask $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * 删除任务实体
     *
     * @param WarehouseTask $entity
     * @param bool $flush
     */
    public function remove(WarehouseTask $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
