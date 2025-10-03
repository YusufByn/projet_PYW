<?php

namespace App\Repository;

use App\Entity\Rent;
use App\Entity\User;
use App\Entity\Clothe;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Rent>
 */
class RentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Rent::class);
    }

    /**
     * Trouve les emprunts d'un utilisateur avec toutes les relations
     */
    public function findUserRentsWithRelations(User $user, string $status = 'en_cours'): array
    {
        return $this->createQueryBuilder('r')
            ->leftJoin('r.clothes', 'c')
            ->leftJoin('c.user', 'u')
            ->leftJoin('c.state', 's')
            ->leftJoin('c.category', 'cat')
            ->addSelect('r', 'c', 'u', 's', 'cat')
            ->where('r.user = :user')
            ->andWhere('r.statut = :status')
            ->setParameter('user', $user)
            ->setParameter('status', $status)
            ->orderBy('r.dateDebut', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findUserHistoryWithRelations(User $user): array
    {
        return $this->createQueryBuilder('r')
            ->leftJoin('r.clothes', 'c')
            ->leftJoin('c.user', 'cu')  // ← CORRECTION : Alias différent
            ->leftJoin('c.state', 's')
            ->leftJoin('c.category', 'cat')
            ->addSelect('r', 'c', 'cu', 's', 'cat')
            ->where('r.user = :user')
            ->setParameter('user', $user)
            ->orderBy('r.dateDebut', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findUserRequestsWithRelations(User $user): array
    {
        return $this->createQueryBuilder('r')
            ->leftJoin('r.clothes', 'c')
            ->leftJoin('c.user', 'cu')  // ← CORRECTION : Alias différent
            ->leftJoin('c.state', 's')
            ->leftJoin('c.category', 'cat')
            ->addSelect('r', 'c', 'cu', 's', 'cat')
            ->where('r.user = :user')
            ->andWhere('r.statut = :status')
            ->setParameter('user', $user)
            ->setParameter('status', 'pending')
            ->orderBy('r.dateDebut', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve tous les emprunts d'un vêtement avec les relations
     */
    public function findClothesRentsWithRelations(Clothe $clothe): array
    {
        return $this->createQueryBuilder('r')
            ->leftJoin('r.user', 'u')
            ->leftJoin('r.clothes', 'c')
            ->addSelect('r', 'u', 'c')
            ->where('r.clothes = :clothe')
            ->setParameter('clothe', $clothe)
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve un emprunt spécifique utilisateur-vêtement avec les relations
     */
    public function findUserClotheRentWithRelations(User $user, Clothe $clothe, string $status = 'en_cours'): ?Rent
    {
        return $this->createQueryBuilder('r')
            ->leftJoin('r.user', 'u')
            ->leftJoin('r.clothes', 'c')
            ->addSelect('r', 'u', 'c')
            ->where('r.user = :user')
            ->andWhere('r.clothes = :clothe')
            ->andWhere('r.statut = :status')
            ->setParameter('user', $user)
            ->setParameter('clothe', $clothe)
            ->setParameter('status', $status)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Trouve les demandes reçues par un propriétaire avec toutes les relations
     */
    public function findReceivedRequestsWithRelations(User $owner): array
    {
        return $this->createQueryBuilder('r')
            ->leftJoin('r.clothes', 'c')
            ->leftJoin('r.user', 'ru')  // ← CORRECTION : Alias différent
            ->leftJoin('c.state', 's')
            ->leftJoin('c.category', 'cat')
            ->addSelect('r', 'c', 'ru', 's', 'cat')
            ->where('c.user = :owner')
            ->andWhere('r.statut = :status')
            ->setParameter('owner', $owner)
            ->setParameter('status', 'pending')
            ->orderBy('r.dateDebut', 'DESC')
            ->getQuery()
            ->getResult();
    }

    //    /**
    //     * @return Rent[] Returns an array of Rent objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('r')
    //            ->andWhere('r.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('r.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Rent
    //    {
    //        return $this->createQueryBuilder('r')
    //            ->andWhere('r.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
