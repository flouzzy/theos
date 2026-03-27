<?php

namespace App\Repository;

use App\Entity\TriviaQuestion;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class TriviaQuestionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TriviaQuestion::class);
    }

    public function findRandom(): ?TriviaQuestion
    {
        return $this->createQueryBuilder('q')
            ->orderBy('RAND()')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
