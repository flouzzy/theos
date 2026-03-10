<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\PeerReviewScore;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PeerReviewScore>
 *
 * @method PeerReviewScore|null find($id, $lockMode = null, $lockVersion = null)
 * @method PeerReviewScore|null findOneBy(array $criteria, array $orderBy = null)
 * @method PeerReviewScore[]    findAll()
 * @method PeerReviewScore[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PeerReviewScoreRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PeerReviewScore::class);
    }
}
