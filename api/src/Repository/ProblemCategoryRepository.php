<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\ProblemCategory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ProblemCategory>
 */
class ProblemCategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProblemCategory::class);
    }

    public function findByCode(string $code): ?ProblemCategory
    {
        return $this->findOneBy(['code' => $code]);
    }

    /**
     * @return ProblemCategory[]
     */
    public function findActiveCategories(): array
    {
        return $this->createQueryBuilder('pc')
            ->where('pc.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('pc.priority', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
