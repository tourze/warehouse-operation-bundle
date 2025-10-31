<?php

declare(strict_types=1);

namespace Tourze\WarehouseOperationBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;
use Tourze\WarehouseOperationBundle\Entity\TaskRule;

/**
 * TaskRule仓库类
 *
 * 提供TaskRule实体的数据访问层功能，包括任务调度规则的查询、筛选和匹配操作。
 * 严格遵循Repository模式，仅负责数据访问，不包含业务逻辑。
 *
 * @extends ServiceEntityRepository<TaskRule>
 */
#[AsRepository(entityClass: TaskRule::class)]
class TaskRuleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TaskRule::class);
    }

    /**
     * 根据规则类型查找活跃规则
     *
     * @param string $ruleType 规则类型
     * @return TaskRule[] 任务规则数组
     */
    public function findActiveByType(string $ruleType): array
    {
        /** @var array<TaskRule> */
        return $this->createQueryBuilder('tr')
            ->andWhere('tr.ruleType = :type')
            ->andWhere('tr.isActive = :active')
            ->setParameter('type', $ruleType)
            ->setParameter('active', true)
            ->orderBy('tr.priority', 'DESC')
            ->addOrderBy('tr.name', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 查找所有活跃规则，按优先级排序
     *
     * @return TaskRule[] 任务规则数组
     */
    public function findAllActiveOrderedByPriority(): array
    {
        /** @var array<TaskRule> */
        return $this->createQueryBuilder('tr')
            ->andWhere('tr.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('tr.priority', 'DESC')
            ->addOrderBy('tr.name', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 查找当前有效的规则
     *
     * @param \DateTimeInterface|null $date 检查日期，默认为当前时间
     * @return TaskRule[] 当前有效的规则数组
     */
    public function findCurrentlyEffective(?\DateTimeInterface $date = null): array
    {
        $checkDate = $date ?? new \DateTimeImmutable();

        /** @var array<TaskRule> */
        return $this->createQueryBuilder('tr')
            ->andWhere('tr.isActive = :active')
            ->andWhere('tr.effectiveFrom <= :date OR tr.effectiveFrom IS NULL')
            ->andWhere('tr.effectiveTo >= :date OR tr.effectiveTo IS NULL')
            ->setParameter('active', true)
            ->setParameter('date', $checkDate)
            ->orderBy('tr.priority', 'DESC')
            ->addOrderBy('tr.name', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 根据优先级范围查找规则
     *
     * @param int $minPriority 最小优先级
     * @param int $maxPriority 最大优先级
     * @param bool $activeOnly 是否仅返回活跃规则
     * @return TaskRule[] 任务规则数组
     */
    public function findByPriorityRange(int $minPriority, int $maxPriority, bool $activeOnly = true): array
    {
        $qb = $this->createQueryBuilder('tr')
            ->andWhere('tr.priority BETWEEN :minPriority AND :maxPriority')
            ->setParameter('minPriority', $minPriority)
            ->setParameter('maxPriority', $maxPriority)
            ->orderBy('tr.priority', 'DESC')
        ;

        if ($activeOnly) {
            $qb->andWhere('tr.isActive = :active')
                ->setParameter('active', true)
            ;
        }

        /** @var array<TaskRule> */
        return $qb->getQuery()->getResult();
    }

    /**
     * 搜索规则
     *
     * @param string $searchTerm 搜索关键词
     * @param bool $activeOnly 是否仅搜索活跃规则
     * @return TaskRule[] 匹配的规则数组
     */
    public function searchRules(string $searchTerm, bool $activeOnly = true): array
    {
        $qb = $this->createQueryBuilder('tr')
            ->andWhere('tr.name LIKE :term OR tr.description LIKE :term OR tr.notes LIKE :term')
            ->setParameter('term', '%' . $searchTerm . '%')
            ->orderBy('tr.priority', 'DESC')
            ->addOrderBy('tr.name', 'ASC')
        ;

        if ($activeOnly) {
            $qb->andWhere('tr.isActive = :active')
                ->setParameter('active', true)
            ;
        }

        /** @var array<TaskRule> */
        return $qb->getQuery()->getResult();
    }

    /**
     * 查找包含特定条件的规则
     *
     * @param string $conditionKey 条件键名
     * @param mixed $conditionValue 条件值
     * @return TaskRule[] 匹配的规则数组
     */
    public function findByCondition(string $conditionKey, mixed $conditionValue = null): array
    {
        $qb = $this->createQueryBuilder('tr')
            ->andWhere('tr.conditions LIKE :conditionPath')
            ->andWhere('tr.isActive = :active')
            ->setParameter('conditionPath', '%"' . $conditionKey . '"%')
            ->setParameter('active', true)
            ->orderBy('tr.priority', 'DESC')
        ;

        if (null !== $conditionValue) {
            $qb->andWhere('tr.conditions LIKE :conditionValue')
                ->setParameter('conditionValue', '%' . json_encode($conditionValue) . '%')
            ;
        }

        /** @var array<TaskRule> */
        return $qb->getQuery()->getResult();
    }

    /**
     * 查找包含特定动作的规则
     *
     * @param string $actionKey 动作键名
     * @return TaskRule[] 匹配的规则数组
     */
    public function findByAction(string $actionKey): array
    {
        /** @var array<TaskRule> */
        return $this->createQueryBuilder('tr')
            ->andWhere('tr.actions LIKE :actionPath')
            ->andWhere('tr.isActive = :active')
            ->setParameter('actionPath', '%"' . $actionKey . '"%')
            ->setParameter('active', true)
            ->orderBy('tr.priority', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 查找即将失效的规则
     *
     * @param int $days 提前天数
     * @return TaskRule[] 即将失效的规则数组
     */
    public function findExpiringRules(int $days = 30): array
    {
        $futureDate = new \DateTimeImmutable("+{$days} days");

        /** @var array<TaskRule> */
        return $this->createQueryBuilder('tr')
            ->andWhere('tr.effectiveTo <= :futureDate')
            ->andWhere('tr.effectiveTo IS NOT NULL')
            ->andWhere('tr.isActive = :active')
            ->setParameter('futureDate', $futureDate)
            ->setParameter('active', true)
            ->orderBy('tr.effectiveTo', 'ASC')
            ->addOrderBy('tr.priority', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 统计各规则类型的数量
     *
     * @param bool $activeOnly 是否仅统计活跃规则
     * @return array<string, int> 规则类型统计数组
     */
    public function countByRuleType(bool $activeOnly = true): array
    {
        $qb = $this->createQueryBuilder('tr')
            ->select('tr.ruleType, COUNT(tr.id) as count')
            ->groupBy('tr.ruleType')
            ->orderBy('count', 'DESC')
        ;

        if ($activeOnly) {
            $qb->andWhere('tr.isActive = :active')
                ->setParameter('active', true)
            ;
        }

        /** @var array<array{ruleType: string, count: int|string}> */
        $result = $qb->getQuery()->getResult();

        /** @var array<string, int> */
        return array_column($result, 'count', 'ruleType');
    }

    /**
     * 查找冲突的规则
     *
     * @param string $ruleType 规则类型
     * @param int $priority 优先级
     * @param \DateTimeInterface|null $effectiveFrom 生效开始时间
     * @param \DateTimeInterface|null $effectiveTo 生效结束时间
     * @param int|null $excludeRuleId 排除的规则ID
     * @return TaskRule[] 可能冲突的规则数组
     */
    public function findConflictingRules(
        string $ruleType,
        int $priority,
        ?\DateTimeInterface $effectiveFrom = null,
        ?\DateTimeInterface $effectiveTo = null,
        ?int $excludeRuleId = null,
    ): array {
        $qb = $this->createQueryBuilder('tr')
            ->andWhere('tr.ruleType = :ruleType')
            ->andWhere('tr.priority = :priority')
            ->andWhere('tr.isActive = :active')
            ->setParameter('ruleType', $ruleType)
            ->setParameter('priority', $priority)
            ->setParameter('active', true)
        ;

        if (null !== $excludeRuleId) {
            $qb->andWhere('tr.id != :excludeId')
                ->setParameter('excludeId', $excludeRuleId)
            ;
        }

        // 检查时间段重叠
        if (null !== $effectiveFrom || null !== $effectiveTo) {
            $qb->andWhere('
                (tr.effectiveFrom IS NULL OR tr.effectiveTo IS NULL) OR
                (tr.effectiveFrom <= :checkTo AND tr.effectiveTo >= :checkFrom)
            ')
                ->setParameter('checkFrom', $effectiveFrom ?? new \DateTimeImmutable('1900-01-01'))
                ->setParameter('checkTo', $effectiveTo ?? new \DateTimeImmutable('2100-12-31'))
            ;
        }

        /** @var array<TaskRule> */
        return $qb->orderBy('tr.name', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 获取最高优先级
     *
     * @param string|null $ruleType 规则类型
     * @return int 最高优先级值
     */
    public function getMaxPriority(?string $ruleType = null): int
    {
        $qb = $this->createQueryBuilder('tr')
            ->select('MAX(tr.priority) as maxPriority')
            ->andWhere('tr.isActive = :active')
            ->setParameter('active', true)
        ;

        if (null !== $ruleType) {
            $qb->andWhere('tr.ruleType = :ruleType')
                ->setParameter('ruleType', $ruleType)
            ;
        }

        $result = $qb->getQuery()->getSingleScalarResult();

        return (int) ($result ?? 0);
    }

    /**
     * 批量启用/禁用规则
     *
     * @param array<int> $ruleIds 规则ID数组
     * @param bool $isActive 是否启用
     * @return int 受影响的行数
     */
    public function bulkToggleActive(array $ruleIds, bool $isActive): int
    {
        if (0 === count($ruleIds)) {
            return 0;
        }

        $affectedRows = $this->createQueryBuilder('tr')
            ->update()
            ->set('tr.isActive', ':isActive')
            ->where('tr.id IN (:ruleIds)')
            ->setParameter('isActive', $isActive)
            ->setParameter('ruleIds', $ruleIds)
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
     * 保存任务规则实体
     *
     * @param TaskRule $entity
     * @param bool $flush
     */
    public function save(TaskRule $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * 删除任务规则实体
     *
     * @param TaskRule $entity
     * @param bool $flush
     */
    public function remove(TaskRule $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
