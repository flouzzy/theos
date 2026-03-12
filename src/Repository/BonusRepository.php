<?php

namespace App\Repository;

use App\Entity\Bonus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Bonus>
 *
 * @method Bonus|null find($id, $lockMode = null, $lockVersion = null)
 * @method Bonus|null findOneBy(array $criteria, array $orderBy = null)
 * @method Bonus[]    findAll()
 * @method Bonus[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BonusRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Bonus::class);
    }

    public function findRandomBonus(): ?Bonus
    {
        $all = $this->findAll();
        if (empty($all)) return null;
        return $all[array_rand($all)];
    }
}
