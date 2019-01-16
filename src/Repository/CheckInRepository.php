<?php

namespace App\Repository;

use App\Entity\CheckIn;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method CheckIn|null find($id, $lockMode = null, $lockVersion = null)
 * @method CheckIn|null findOneBy(array $criteria, array $orderBy = null)
 * @method CheckIn[]    findAll()
 * @method CheckIn[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CheckInRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, CheckIn::class);
    }

    public function findByEmptyLeaving($date)
    {

        return $this->createQueryBuilder('c')
            ->andWhere('c.leaving IS NULL')
            ->andWhere('c.arrivalDate != :date')
            ->setParameter('date', $date)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param $date
     * @return mixed
     */
    public function findLikeDate($date, $id)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.arrivalDate LIKE :date')
            ->andWhere('c.customer = :id')
            ->setParameter('id', $id)
            ->setParameter('date', '%'.$date.'%')
            ->getQuery()
            ->getResult()
        ;
    }

    /*
    public function findOneBySomeField($value): ?CheckIn
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
