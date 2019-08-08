<?php

namespace App\Repository;

use App\Entity\Options;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method Options|null find($id, $lockMode = null, $lockVersion = null)
 * @method Options|null findOneBy(array $criteria, array $orderBy = null)
 * @method Options[]    findAll()
 * @method Options[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OptionsRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Options::class);
    }
}
