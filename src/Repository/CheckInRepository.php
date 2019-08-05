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
            // ->andWhere('c.arrivalDate != :date')
            // ->setParameter('date', $date)
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

    /**
     * @param $selected_month
     * @return mixed
     * @desc return a array that feets with this example : 
     *       
     *      $all_halfdays_counts = $this->checkInRepository->findByHalfDayCount('2019-07');
     *  
     *  then, we have : 
     * 
     *      $user_halfdays_counts = $all_halfdays_counts[$customer_id];
     *      $user_halfdays_counts[$day (within the the selected_month)] = 4 (if the user has a total of 4 halfdays during this $day)
     *  
     *  this function is used in the CheckinController to limit by 2 the total number of halfdays of a customer in a day
     */
    public function findByHalfDayCount($selected_month)
    {
        return $this->createQueryBuilder('c')
            ->select('IDENTITY(c.customer)', 'c.arrivalDate', 'SUM(c.halfDay)')
            ->andWhere('c.arrival_month LIKE :selected_month')
            ->groupBy('c.customer')
            ->addGroupBy('c.arrivalDate')
            ->setParameter('selected_month', $selected_month.'%')
            ->getQuery()
            ->getResult();
        ;
    }

    /**
     * @param $selected_month
     * @return integer
     * @desc return SUM(halfDays) from the checkIn table, 
     *       where the customer is given by his Id and the day is given with the 'Y-m-d' format.
     */
    public function findByHalfDayCountForCustomer($selected_day,$customer_id)
    {
        $halfday_count = $this->createQueryBuilder('c')
            ->select('SUM(c.halfDay)')
            ->andWhere('c.arrivalDate LIKE :selected_day')
            ->andWhere('c.customer = :customer_id')
            ->setParameter('selected_day', $selected_day.'%')
            ->setParameter('customer_id', $customer_id)
            ->getQuery()
            ->getSingleScalarResult();
        return (int)$halfday_count;
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
