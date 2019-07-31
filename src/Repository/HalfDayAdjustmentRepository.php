<?php

namespace App\Repository;

use App\Entity\HalfDayAdjustment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method HalfDayAdjustment|null find($id, $lockMode = null, $lockVersion = null)
 * @method HalfDayAdjustment|null findOneBy(array $criteria, array $orderBy = null)
 * @method HalfDayAdjustment[]    findAll()
 * @method HalfDayAdjustment[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class HalfDayAdjustmentRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, HalfDayAdjustment::class);
    }

    // /**
    //  * @return HalfDayAdjustment[] Returns an array of HalfDayAdjustment objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('h')
            ->andWhere('h.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('h.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?HalfDayAdjustment
    {
        return $this->createQueryBuilder('h')
            ->andWhere('h.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
