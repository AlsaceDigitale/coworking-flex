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
}
