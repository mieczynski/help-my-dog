<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\AdviceCard;
use App\Entity\Dog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AdviceCard>
 */
class AdviceCardRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AdviceCard::class);
    }

    /**
     * @return AdviceCard[]
     */
    public function findActiveByDog(Dog $dog): array
    {
        return $this->createQueryBuilder('ac')
            ->where('ac.dog = :dog')
            ->andWhere('ac.deletedAt IS NULL')
            ->setParameter('dog', $dog)
            ->orderBy('ac.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return AdviceCard[]
     */
    public function findByDogAndType(Dog $dog, string $adviceType): array
    {
        return $this->createQueryBuilder('ac')
            ->where('ac.dog = :dog')
            ->andWhere('ac.adviceType = :type')
            ->andWhere('ac.deletedAt IS NULL')
            ->setParameter('dog', $dog)
            ->setParameter('type', $adviceType)
            ->orderBy('ac.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return AdviceCard[]
     */
    public function findRatedByDog(Dog $dog): array
    {
        return $this->createQueryBuilder('ac')
            ->where('ac.dog = :dog')
            ->andWhere('ac.rating IS NOT NULL')
            ->andWhere('ac.deletedAt IS NULL')
            ->setParameter('dog', $dog)
            ->orderBy('ac.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
