<?php

namespace App\Repository;

use App\Entity\Clothe;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Clothe>
 */
class ClotheRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Clothe::class);
    }

    /**
     * Trouve tous les vêtements d'un utilisateur
     */
    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.user = :user')
            ->setParameter('user', $user)
            ->orderBy('c.title', 'ASC')
            ->getQuery()
            ->getResult();
    }



}
