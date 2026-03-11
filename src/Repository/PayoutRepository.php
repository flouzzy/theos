<?php

namespace App\Repository;

use App\Entity\Payout;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Payout>
 *
 * @method Payout|null find($id, $lockMode = null, $lockVersion = null)
 * @method Payout|null findOneBy(array $criteria, array $orderBy = null)
 * @method Payout[]    findAll()
 * @method Payout[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PayoutRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Payout::class);
    }
}
