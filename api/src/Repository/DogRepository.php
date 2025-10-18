<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Dog;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Dog>
 */
class DogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Dog::class);
    }

    /**
     * @return Dog[]
     */
    public function findActiveByUser(User $user): array
    {
        return $this->createQueryBuilder('d')
            ->where('d.user = :user')
            ->andWhere('d.deletedAt IS NULL')
            ->setParameter('user', $user)
            ->orderBy('d.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findOneActiveByUserAndId(User $user, string $dogId): ?Dog
    {
        return $this->createQueryBuilder('d')
            ->where('d.id = :id')
            ->andWhere('d.user = :user')
            ->andWhere('d.deletedAt IS NULL')
            ->setParameter('id', $dogId)
            ->setParameter('user', $user)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
