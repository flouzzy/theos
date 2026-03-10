<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\RubricCriterion;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<RubricCriterion>
 *
 * @method RubricCriterion|null find($id, $lockMode = null, $lockVersion = null)
 * @method RubricCriterion|null findOneBy(array $criteria, array $orderBy = null)
 * @method RubricCriterion[]    findAll()
 * @method RubricCriterion[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RubricCriterionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RubricCriterion::class);
    }
}
