<?php

declare(strict_types=1);

namespace Tourze\WarehouseOperationBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;
use Tourze\WarehouseOperationBundle\Entity\CountPlan;

/**
 * CountPlan仓库类
 *
 * 提供CountPlan实体的数据访问层功能，包括盘点计划的查询、筛选和调度操作。
 * 严格遵循Repository模式，仅负责数据访问，不包含业务逻辑。
 *
 * @extends ServiceEntityRepository<CountPlan>
 */
#[AsRepository(entityClass: CountPlan::class)]
class CountPlanRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CountPlan::class);
    }

    /**
     * 根据盘点类型查找计划
     *
     * @param string $countType 盘点类型
     * @return array<CountPlan> 盘点计划数组
     */
    public function findByCountType(string $countType): array
    {
        /** @var array<CountPlan> */
        return $this->createQueryBuilder('cp')
            ->andWhere('cp.countType = :type')
            ->andWhere('cp.isActive = :active')
            ->setParameter('type', $countType)
            ->setParameter('active', true)
            ->orderBy('cp.priority', 'DESC')
            ->addOrderBy('cp.startDate', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 查找启用的盘点计划
     *
     * @return array<CountPlan> 启用的盘点计划数组
     */
    public function findActivePlans(): array
    {
        /** @var array<CountPlan> */
        return $this->createQueryBuilder('cp')
            ->andWhere('cp.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('cp.priority', 'DESC')
            ->addOrderBy('cp.name', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 根据状态查找盘点计划
     *
     * @param string $status 计划状态
     * @return array<CountPlan> 盘点计划数组
     */
    public function findByStatus(string $status): array
    {
        /** @var array<CountPlan> */
        return $this->createQueryBuilder('cp')
            ->andWhere('cp.status = :status')
            ->setParameter('status', $status)
            ->orderBy('cp.priority', 'DESC')
            ->addOrderBy('cp.startDate', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 查找指定日期范围内的盘点计划
     *
     * @param \DateTimeImmutable $startDate 开始日期
     * @param \DateTimeImmutable $endDate 结束日期
     * @return array<CountPlan> 盘点计划数组
     */
    public function findByDateRange(\DateTimeImmutable $startDate, \DateTimeImmutable $endDate): array
    {
        /** @var array<CountPlan> */
        return $this->createQueryBuilder('cp')
            ->andWhere('cp.startDate BETWEEN :start AND :end')
            ->andWhere('cp.isActive = :active')
            ->setParameter('start', $startDate)
            ->setParameter('end', $endDate)
            ->setParameter('active', true)
            ->orderBy('cp.startDate', 'ASC')
            ->addOrderBy('cp.priority', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 查找即将执行的盘点计划
     *
     * @param int $days 未来天数
     * @return array<CountPlan> 即将执行的盘点计划数组
     */
    public function findUpcomingPlans(int $days = 7): array
    {
        $now = new \DateTimeImmutable();
        $future = $now->modify("+{$days} days");

        /** @var array<CountPlan> */
        return $this->createQueryBuilder('cp')
            ->andWhere('cp.startDate BETWEEN :now AND :future')
            ->andWhere('cp.status IN (:statuses)')
            ->andWhere('cp.isActive = :active')
            ->setParameter('now', $now)
            ->setParameter('future', $future)
            ->setParameter('statuses', ['draft', 'scheduled'])
            ->setParameter('active', true)
            ->orderBy('cp.startDate', 'ASC')
            ->addOrderBy('cp.priority', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 统计各盘点类型的计划数量
     *
     * @return array<string, int> 类型统计数组
     */
    public function countByType(): array
    {
        /** @var array<array{countType: string, count: int|string}> */
        $result = $this->createQueryBuilder('cp')
            ->select('cp.countType, COUNT(cp.id) as count')
            ->andWhere('cp.isActive = :active')
            ->setParameter('active', true)
            ->groupBy('cp.countType')
            ->orderBy('count', 'DESC')
            ->getQuery()
            ->getResult()
        ;

        /** @var array<string, int> */
        return array_column($result, 'count', 'countType');
    }

    /**
     * 保存盘点计划实体
     *
     * @param CountPlan $entity
     * @param bool $flush
     */
    public function save(CountPlan $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * 删除盘点计划实体
     *
     * @param CountPlan $entity
     * @param bool $flush
     */
    public function remove(CountPlan $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
