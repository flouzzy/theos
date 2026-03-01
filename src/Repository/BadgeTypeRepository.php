<?php

namespace App\Repository;

use App\Entity\BadgeType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<BadgeType>
 *
 * @method BadgeType|null find($id, $lockMode = null, $lockVersion = null)
 * @method BadgeType|null findOneBy(array $criteria, array $orderBy = null)
 * @method BadgeType[]    findAll()
 * @method BadgeType[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BadgeTypeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BadgeType::class);
    }


}
