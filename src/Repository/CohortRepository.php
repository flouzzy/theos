<?php

namespace App\Repository;

use App\Entity\Cohort;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Cohort>
 *
 * @method Cohort|null find($id, $lockMode = null, $lockVersion = null)
 * @method Cohort|null findOneBy(array $criteria, array $orderBy = null)
 * @method Cohort[]    findAll()
 * @method Cohort[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CohortRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Cohort::class);
    }

}
