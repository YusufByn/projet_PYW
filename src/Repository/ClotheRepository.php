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

    public function findAvailableClothesWithRelations(): array
    {
        return $this->createQueryBuilder('c')
        ->leftJoin('c.user', 'u')
        ->leftJoin('c.state', 's')
        ->leftJoin('c.category', 'cat')
        ->addSelect('c', 'u', 's', 'cat')
        ->andWhere('c.currentBorrower IS NULL')
        ->orderBy('c.title', 'ASC')
        ->getQuery()
        ->getResult();
    }

    /**
     * Trouve tous les vêtements d'un utilisateur avec leurs relations
     */
    public function findByUserWithRelations(User $user): array
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.user', 'u')
            ->leftJoin('c.state', 's')
            ->leftJoin('c.category', 'cat')
            ->leftJoin('c.rents', 'r')
            ->addSelect('c', 'u', 's', 'cat', 'r')
            ->where('c.user = :user')
            ->setParameter('user', $user)
            ->orderBy('c.title', 'ASC')
            ->getQuery()
            ->getResult();
    }

    //    /**
    //     * @return Clothe[] Returns an array of Clothe objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('c.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Clothe
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
