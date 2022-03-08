<?php

namespace App\Repository;

use App\Entity\Customer;
use DateTimeInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Security\Core\User\User;
use Symfony\Component\Validator\Constraints\Date;

/**
 * @method Customer|null find($id, $lockMode = null, $lockVersion = null)
 * @method Customer|null findOneBy(array $criteria, array $orderBy = null)
 * @method Customer[]    findAll()
 * @method Customer[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CustomerRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Customer::class);
    }

    public function findAllWithCheckinWithinPeriod(DateTimeInterface $beginDate, DateTimeInterface $endDate): array {
        return $this->createQueryBuilder('user')
            ->leftJoin('user.checkIns',
                'checkIn',
                Expr\Join::WITH,
                'checkIn.arrivalDate >= :beginDate AND checkIn.arrivalDate <= :endDate')
            ->addSelect('checkIn')
            ->setParameter('beginDate', $beginDate->format('Y-m-d'))
            ->setParameter('endDate', $endDate->format('Y-m-d'))
            ->getQuery()
            ->getResult();
    }
}
