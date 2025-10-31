<?php

declare(strict_types=1);

namespace Tourze\WarehouseOperationBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;
use Tourze\WarehouseOperationBundle\Entity\QualityStandard;

/**
 * QualityStandard仓库类
 *
 * 提供QualityStandard实体的数据访问层功能，包括质检标准的查询、筛选和管理操作。
 * 严格遵循Repository模式，仅负责数据访问，不包含业务逻辑。
 *
 * @extends ServiceEntityRepository<QualityStandard>
 */
#[AsRepository(entityClass: QualityStandard::class)]
class QualityStandardRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, QualityStandard::class);
    }

    /**
     * 根据商品类别查找质检标准
     *
     * @param string $productCategory 商品类别
     * @return QualityStandard[] 质检标准数组
     */
    public function findByProductCategory(string $productCategory): array
    {
        /** @var array<QualityStandard> */
        return $this->createQueryBuilder('qs')
            ->andWhere('qs.productCategory = :category')
            ->andWhere('qs.isActive = :active')
            ->setParameter('category', $productCategory)
            ->setParameter('active', true)
            ->orderBy('qs.priority', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 查找启用的质检标准
     *
     * @return QualityStandard[] 启用的质检标准数组
     */
    public function findActiveStandards(): array
    {
        /** @var array<QualityStandard> */
        return $this->createQueryBuilder('qs')
            ->andWhere('qs.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('qs.priority', 'DESC')
            ->addOrderBy('qs.name', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 根据优先级范围查找质检标准
     *
     * @param int $minPriority 最小优先级
     * @param int $maxPriority 最大优先级
     * @return QualityStandard[] 质检标准数组
     */
    public function findByPriorityRange(int $minPriority, int $maxPriority): array
    {
        /** @var array<QualityStandard> */
        return $this->createQueryBuilder('qs')
            ->andWhere('qs.priority BETWEEN :min AND :max')
            ->andWhere('qs.isActive = :active')
            ->setParameter('min', $minPriority)
            ->setParameter('max', $maxPriority)
            ->setParameter('active', true)
            ->orderBy('qs.priority', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 查找包含特定检查项的质检标准
     *
     * @param string $checkItemKey 检查项键名
     * @return QualityStandard[] 质检标准数组
     */
    public function findByCheckItem(string $checkItemKey): array
    {
        /** @var array<QualityStandard> */
        return $this->createQueryBuilder('qs')
            ->andWhere('qs.checkItems LIKE :checkPath')
            ->andWhere('qs.isActive = :active')
            ->setParameter('checkPath', '%"' . $checkItemKey . '"%')
            ->setParameter('active', true)
            ->orderBy('qs.priority', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 搜索质检标准
     *
     * @param string $searchTerm 搜索关键词
     * @return QualityStandard[] 质检标准数组
     */
    public function searchStandards(string $searchTerm): array
    {
        /** @var array<QualityStandard> */
        return $this->createQueryBuilder('qs')
            ->andWhere('qs.name LIKE :term OR qs.description LIKE :term OR qs.productCategory LIKE :term')
            ->andWhere('qs.isActive = :active')
            ->setParameter('term', '%' . $searchTerm . '%')
            ->setParameter('active', true)
            ->orderBy('qs.priority', 'DESC')
            ->addOrderBy('qs.name', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 统计各商品类别的质检标准数量
     *
     * @return array<string, int> 类别统计数组
     */
    public function countByCategory(): array
    {
        /** @var array<array{productCategory: string, count: int|string}> */
        $result = $this->createQueryBuilder('qs')
            ->select('qs.productCategory, COUNT(qs.id) as count')
            ->andWhere('qs.isActive = :active')
            ->setParameter('active', true)
            ->groupBy('qs.productCategory')
            ->orderBy('count', 'DESC')
            ->getQuery()
            ->getResult()
        ;

        /** @var array<string, int> */
        return array_column($result, 'count', 'productCategory');
    }

    /**
     * 保存质检标准实体
     *
     * @param QualityStandard $entity
     * @param bool $flush
     */
    public function save(QualityStandard $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * 删除质检标准实体
     *
     * @param QualityStandard $entity
     * @param bool $flush
     */
    public function remove(QualityStandard $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
