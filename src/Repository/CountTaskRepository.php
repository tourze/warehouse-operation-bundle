<?php

declare(strict_types=1);

namespace Tourze\WarehouseOperationBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;
use Tourze\WarehouseOperationBundle\Entity\CountTask;
use Tourze\WarehouseOperationBundle\Enum\TaskStatus;
use Tourze\WarehouseOperationBundle\Enum\TaskType;

/**
 * CountTask仓库类
 *
 * 提供CountTask实体的数据访问层功能，包括盘点任务的查询、筛选和统计操作。
 * 严格遵循Repository模式，仅负责数据访问，不包含业务逻辑。
 *
 * @extends ServiceEntityRepository<CountTask>
 */
#[AsRepository(entityClass: CountTask::class)]
class CountTaskRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CountTask::class);
    }

    /**
     * 根据盘点计划查找任务
     *
     * @param int $countPlanId 盘点计划ID
     * @return CountTask[] 盘点任务数组
     */
    public function findByCountPlan(int $countPlanId): array
    {
        /** @var array<CountTask> */
        return $this->createQueryBuilder('ct')
            ->where('ct.countPlanId = :planId')
            ->setParameter('planId', $countPlanId)
            ->orderBy('ct.taskSequence', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 根据状态和盘点计划查找任务
     *
     * @param int $countPlanId 盘点计划ID
     * @param string $status 任务状态
     * @return CountTask[] 盘点任务数组
     */
    public function findByCountPlanAndStatus(int $countPlanId, string $status): array
    {
        /** @var array<CountTask> */
        return $this->createQueryBuilder('ct')
            ->where('ct.countPlanId = :planId')
            ->andWhere('ct.status = :status')
            ->setParameter('planId', $countPlanId)
            ->setParameter('status', $status)
            ->orderBy('ct.priority', 'DESC')
            ->addOrderBy('ct.createTime', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 查找需要复盘的任务
     *
     * @return CountTask[] 需要复盘的任务数组
     */
    public function findRecountTasks(): array
    {
        /** @var array<CountTask> */
        $allCountTasks = $this->createQueryBuilder('ct')
            ->where('ct.type = :taskType')
            ->andWhere('ct.status IN (:statuses)')
            ->setParameter('taskType', TaskType::COUNT)
            ->setParameter('statuses', [TaskStatus::PENDING, TaskStatus::ASSIGNED])
            ->orderBy('ct.priority', 'DESC')
            ->addOrderBy('ct.createTime', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        // 过滤出复盘任务（通过任务名称或数据中的标识）
        $recountTasks = [];
        foreach ($allCountTasks as $task) {
            /** @var array<string, mixed> */
            $taskData = $task->getTaskData();
            $taskName = $task->getTaskName();

            if (
                (isset($taskData['is_recount']) && true === $taskData['is_recount'])
                || str_contains($taskName, '复盘')
            ) {
                $recountTasks[] = $task;
            }
        }

        return $recountTasks;
    }

    /**
     * 统计盘点计划的任务完成情况
     *
     * @param int $countPlanId 盘点计划ID
     * @return array<string, int> 状态统计
     */
    public function countTaskStatusByPlan(int $countPlanId): array
    {
        /** @var array<array{status_enum: TaskStatus, count: int|string}> */
        $result = $this->createQueryBuilder('ct')
            ->select('ct.status as status_enum, COUNT(ct.id) as count')
            ->where('ct.countPlanId = :planId')
            ->setParameter('planId', $countPlanId)
            ->groupBy('ct.status')
            ->getQuery()
            ->getResult()
        ;

        $statusCounts = [];
        foreach ($result as $row) {
            $statusCounts[$row['status_enum']->value] = (int) $row['count'];
        }

        return $statusCounts;
    }

    /**
     * 查找有差异的盘点任务
     *
     * @param int|null $countPlanId 盘点计划ID，null表示查找所有
     * @return CountTask[] 有差异的任务数组
     */
    public function findDiscrepancyTasks(?int $countPlanId = null): array
    {
        $qb = $this->createQueryBuilder('ct')
            ->where('ct.status = :status')
            ->setParameter('status', 'discrepancy_found')
            ->orderBy('ct.priority', 'DESC')
            ->addOrderBy('ct.createTime', 'DESC')
        ;

        if (null !== $countPlanId) {
            $qb->andWhere('ct.countPlanId = :planId')
                ->setParameter('planId', $countPlanId)
            ;
        }

        /** @var array<CountTask> */
        return $qb->getQuery()->getResult();
    }

    /**
     * 查找指定库位的盘点任务
     *
     * @param string $locationCode 库位编码
     * @param \DateTimeInterface|null $since 开始时间
     * @return CountTask[] 库位的盘点任务数组
     */
    public function findByLocation(string $locationCode, ?\DateTimeInterface $since = null): array
    {
        $qb = $this->createQueryBuilder('ct')
            ->where('ct.locationCode = :locationCode')
            ->setParameter('locationCode', $locationCode)
            ->orderBy('ct.createTime', 'DESC')
        ;

        if (null !== $since) {
            $qb->andWhere('ct.createTime >= :since')
                ->setParameter('since', $since)
            ;
        }

        /** @var array<CountTask> */
        return $qb->getQuery()->getResult();
    }

    /**
     * 统计各库位的盘点准确率
     *
     * @param int|null $countPlanId 盘点计划ID
     * @return array<array<string, mixed>> 库位准确率统计
     */
    public function getLocationAccuracyStats(?int $countPlanId = null): array
    {
        $qb = $this->createQueryBuilder('ct')
            ->select('
                ct.locationCode as location_code,
                COUNT(ct.id) as task_count,
                AVG(ct.accuracy) as avg_accuracy
            ')
            ->where('ct.status IN (:statuses)')
            ->andWhere('ct.accuracy IS NOT NULL')
            ->setParameter('statuses', ['completed', 'discrepancy_found'])
            ->groupBy('ct.locationCode')
            ->orderBy('avg_accuracy', 'DESC')
        ;

        if (null !== $countPlanId) {
            $qb->andWhere('ct.countPlanId = :planId')
                ->setParameter('planId', $countPlanId)
            ;
        }

        /** @var array<array<string, mixed>> */
        return $qb->getQuery()->getResult();
    }

    /**
     * 查找长时间未完成的盘点任务
     *
     * @param int $hours 超时小时数
     * @return CountTask[] 超时任务数组
     */
    public function findOverdueTasks(int $hours = 24): array
    {
        $cutoffTime = new \DateTimeImmutable("-{$hours} hours");

        /** @var array<CountTask> */
        return $this->createQueryBuilder('ct')
            ->where('ct.createTime < :cutoffTime')
            ->andWhere('ct.status IN (:statuses)')
            ->setParameter('cutoffTime', $cutoffTime)
            ->setParameter('statuses', ['pending', 'assigned', 'in_progress'])
            ->orderBy('ct.createTime', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 查找高优先级待处理任务
     *
     * @param int $minPriority 最低优先级
     * @param int|null $limit 限制数量
     * @return CountTask[] 高优先级任务数组
     */
    public function findHighPriorityPendingTasks(int $minPriority = 80, ?int $limit = null): array
    {
        $qb = $this->createQueryBuilder('ct')
            ->where('ct.priority >= :minPriority')
            ->andWhere('ct.status IN (:statuses)')
            ->setParameter('minPriority', $minPriority)
            ->setParameter('statuses', ['pending', 'assigned'])
            ->orderBy('ct.priority', 'DESC')
            ->addOrderBy('ct.createTime', 'ASC')
        ;

        if (null !== $limit) {
            $qb->setMaxResults($limit);
        }

        /** @var array<CountTask> */
        return $qb->getQuery()->getResult();
    }

    /**
     * 获取盘点任务的准确率趋势
     *
     * @param int $days 天数
     * @return array<array<string, mixed>> 趋势数据
     */
    public function getAccuracyTrend(int $days = 30): array
    {
        $startDate = new \DateTimeImmutable("-{$days} days");

        /** @var array<array<string, mixed>> */
        return $this->createQueryBuilder('ct')
            ->select('
                DATE(ct.createTime) as date,
                COUNT(ct.id) as task_count,
                AVG(ct.accuracy) as avg_accuracy,
                SUM(CASE WHEN ct.status = \'discrepancy_found\' THEN 1 ELSE 0 END) as discrepancy_count
            ')
            ->where('ct.createTime >= :startDate')
            ->andWhere('ct.status IN (:statuses)')
            ->andWhere('ct.accuracy IS NOT NULL')
            ->setParameter('startDate', $startDate)
            ->setParameter('statuses', ['completed', 'discrepancy_found'])
            ->groupBy('DATE(ct.createTime)')
            ->orderBy('date', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 保存盘点任务实体
     *
     * @param CountTask $entity
     * @param bool $flush
     */
    public function save(CountTask $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * 删除盘点任务实体
     *
     * @param CountTask $entity
     * @param bool $flush
     */
    public function remove(CountTask $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
