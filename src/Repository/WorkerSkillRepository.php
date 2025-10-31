<?php

declare(strict_types=1);

namespace Tourze\WarehouseOperationBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;
use Tourze\WarehouseOperationBundle\Entity\WorkerSkill;

/**
 * WorkerSkill仓库类
 *
 * 提供WorkerSkill实体的数据访问层功能，包括作业员技能的查询、筛选和匹配操作。
 * 严格遵循Repository模式，仅负责数据访问，不包含业务逻辑。
 *
 * @extends ServiceEntityRepository<WorkerSkill>
 */
#[AsRepository(entityClass: WorkerSkill::class)]
class WorkerSkillRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WorkerSkill::class);
    }

    /**
     * 根据作业员ID查找技能
     *
     * @param int $workerId 作业员ID
     * @return WorkerSkill[] 作业员技能数组
     */
    public function findByWorkerId(int $workerId): array
    {
        /** @var array<WorkerSkill> */
        return $this->createQueryBuilder('ws')
            ->andWhere('ws.workerId = :workerId')
            ->andWhere('ws.isActive = :active')
            ->setParameter('workerId', $workerId)
            ->setParameter('active', true)
            ->orderBy('ws.skillLevel', 'DESC')
            ->addOrderBy('ws.skillCategory', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 根据技能类别查找作业员
     *
     * @param string $skillCategory 技能类别
     * @param int|null $minLevel 最低技能等级
     * @return WorkerSkill[] 技能记录数组
     */
    public function findBySkillCategory(string $skillCategory, ?int $minLevel = null): array
    {
        $qb = $this->createQueryBuilder('ws')
            ->andWhere('ws.skillCategory = :category')
            ->andWhere('ws.isActive = :active')
            ->setParameter('category', $skillCategory)
            ->setParameter('active', true)
        ;

        if (null !== $minLevel) {
            $qb->andWhere('ws.skillLevel >= :minLevel')
                ->setParameter('minLevel', $minLevel)
            ;
        }

        /** @var array<WorkerSkill> */
        return $qb
            ->orderBy('ws.skillLevel', 'DESC')
            ->addOrderBy('ws.certifiedAt', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 查找即将过期的认证
     *
     * @param int $days 提前天数
     * @return WorkerSkill[] 即将过期的技能认证数组
     */
    public function findExpiringCertifications(int $days = 30): array
    {
        $futureDate = new \DateTimeImmutable("+{$days} days");

        /** @var array<WorkerSkill> */
        return $this->createQueryBuilder('ws')
            ->andWhere('ws.expiresAt <= :futureDate')
            ->andWhere('ws.expiresAt IS NOT NULL')
            ->andWhere('ws.isActive = :active')
            ->setParameter('futureDate', $futureDate)
            ->setParameter('active', true)
            ->orderBy('ws.expiresAt', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 根据技能等级范围查找
     *
     * @param int $minLevel 最低等级
     * @param int $maxLevel 最高等级
     * @param string|null $skillCategory 技能类别
     * @return WorkerSkill[] 技能记录数组
     */
    public function findBySkillLevelRange(int $minLevel, int $maxLevel, ?string $skillCategory = null): array
    {
        $qb = $this->createQueryBuilder('ws')
            ->andWhere('ws.skillLevel BETWEEN :minLevel AND :maxLevel')
            ->andWhere('ws.isActive = :active')
            ->setParameter('minLevel', $minLevel)
            ->setParameter('maxLevel', $maxLevel)
            ->setParameter('active', true)
        ;

        if (null !== $skillCategory) {
            $qb->andWhere('ws.skillCategory = :category')
                ->setParameter('category', $skillCategory)
            ;
        }

        /** @var array<WorkerSkill> */
        return $qb
            ->orderBy('ws.skillLevel', 'DESC')
            ->addOrderBy('ws.skillCategory', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 查找有效认证的技能
     *
     * @param string|null $skillCategory 技能类别
     * @return WorkerSkill[] 有效认证的技能数组
     */
    public function findValidCertifications(?string $skillCategory = null): array
    {
        $now = new \DateTimeImmutable();

        $qb = $this->createQueryBuilder('ws')
            ->andWhere('ws.certifiedAt IS NOT NULL')
            ->andWhere('ws.isActive = :active')
            ->andWhere('ws.expiresAt > :now OR ws.expiresAt IS NULL')
            ->setParameter('active', true)
            ->setParameter('now', $now)
        ;

        if (null !== $skillCategory) {
            $qb->andWhere('ws.skillCategory = :category')
                ->setParameter('category', $skillCategory)
            ;
        }

        /** @var array<WorkerSkill> */
        return $qb
            ->orderBy('ws.skillLevel', 'DESC')
            ->addOrderBy('ws.certifiedAt', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 统计各技能类别的人数
     *
     * @return array<string, int> 技能类别统计数组
     */
    public function countBySkillCategory(): array
    {
        /** @var array<array{skillCategory: string, count: int|string}> */
        $result = $this->createQueryBuilder('ws')
            ->select('ws.skillCategory, COUNT(DISTINCT ws.workerId) as count')
            ->andWhere('ws.isActive = :active')
            ->setParameter('active', true)
            ->groupBy('ws.skillCategory')
            ->orderBy('count', 'DESC')
            ->getQuery()
            ->getResult()
        ;

        /** @var array<string, int> */
        return array_column($result, 'count', 'skillCategory');
    }

    /**
     * 查找多技能作业员
     *
     * @param array<string> $skillCategories 技能类别数组
     * @param int $minLevel 最低技能等级
     * @return array<int> 作业员ID数组
     */
    public function findMultiSkillWorkers(array $skillCategories, int $minLevel = 1): array
    {
        if (0 === count($skillCategories)) {
            return [];
        }

        /** @var array<array{workerId: int}> */
        $result = $this->createQueryBuilder('ws')
            ->select('ws.workerId')
            ->andWhere('ws.skillCategory IN (:categories)')
            ->andWhere('ws.skillLevel >= :minLevel')
            ->andWhere('ws.isActive = :active')
            ->setParameter('categories', $skillCategories)
            ->setParameter('minLevel', $minLevel)
            ->setParameter('active', true)
            ->groupBy('ws.workerId')
            ->having('COUNT(DISTINCT ws.skillCategory) = :requiredCount')
            ->setParameter('requiredCount', count($skillCategories))
            ->getQuery()
            ->getResult()
        ;

        /** @var array<int> */
        return array_column($result, 'workerId');
    }

    /**
     * 获取作业员的最高技能等级
     *
     * @param int $workerId 作业员ID
     * @return int 最高技能等级
     */
    public function getWorkerMaxSkillLevel(int $workerId): int
    {
        $result = $this->createQueryBuilder('ws')
            ->select('MAX(ws.skillLevel) as maxLevel')
            ->andWhere('ws.workerId = :workerId')
            ->andWhere('ws.isActive = :active')
            ->setParameter('workerId', $workerId)
            ->setParameter('active', true)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        return (int) ($result ?? 0);
    }

    /**
     * 查找需要技能升级的作业员
     *
     * @param string $skillCategory 技能类别
     * @param int $targetLevel 目标等级
     * @return WorkerSkill[] 需要升级的技能记录
     */
    public function findSkillUpgradeCandidates(string $skillCategory, int $targetLevel): array
    {
        /** @var array<WorkerSkill> */
        return $this->createQueryBuilder('ws')
            ->andWhere('ws.skillCategory = :category')
            ->andWhere('ws.skillLevel < :targetLevel')
            ->andWhere('ws.isActive = :active')
            ->setParameter('category', $skillCategory)
            ->setParameter('targetLevel', $targetLevel)
            ->setParameter('active', true)
            ->orderBy('ws.skillLevel', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 根据技能列表查找匹配的作业员
     *
     * @param array<string> $requiredSkills 所需技能列表
     * @param array<int> $excludeWorkers 要排除的作业员ID列表
     * @return WorkerSkill[] 匹配的作业员技能记录
     */
    public function findWorkersBySkills(array $requiredSkills, array $excludeWorkers = []): array
    {
        if (0 === count($requiredSkills)) {
            return [];
        }

        $qb = $this->createQueryBuilder('ws')
            ->andWhere('ws.skillCategory IN (:skills)')
            ->andWhere('ws.isActive = :active')
            ->setParameter('skills', $requiredSkills)
            ->setParameter('active', true)
        ;

        if (count($excludeWorkers) > 0) {
            $qb->andWhere('ws.workerId NOT IN (:excludeWorkers)')
                ->setParameter('excludeWorkers', $excludeWorkers)
            ;
        }

        /** @var array<WorkerSkill> */
        return $qb
            ->orderBy('ws.skillScore', 'DESC')
            ->addOrderBy('ws.skillLevel', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 保存作业员技能实体
     *
     * @param WorkerSkill $entity
     * @param bool $flush
     */
    public function save(WorkerSkill $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * 删除作业员技能实体
     *
     * @param WorkerSkill $entity
     * @param bool $flush
     */
    public function remove(WorkerSkill $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
