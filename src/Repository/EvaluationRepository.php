<?php

namespace App\Repository;

use App\Entity\Evaluation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Evaluation>
 */
class EvaluationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Evaluation::class);
    }

    //    /**
    //     * @return Evaluation[] Returns an array of Evaluation objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('e')
    //            ->andWhere('e.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('e.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Evaluation
    //    {
    //        return $this->createQueryBuilder('e')
    //            ->andWhere('e.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    /**
     * @param \App\Entity\User[] $users
     * @return array<int, Evaluation[]> User ID => array of latest evaluations
     */
    public function findLatestByUsersAndCohort(array $users, \App\Entity\Cohort $cohort, int $limit = 5): array
    {
        if (empty($users)) {
            return [];
        }

        $qb = $this->createQueryBuilder('e')
            ->where('e.user IN (:users)')
            ->andWhere('e.cohort = :cohort')
            ->setParameter('users', $users)
            ->setParameter('cohort', $cohort)
            ->orderBy('e.createdAt', 'DESC');

        $evaluations = $qb->getQuery()->getResult();

        $grouped = [];
        foreach ($evaluations as $eval) {
            $userId = $eval->getUser()->getId();
            if (!isset($grouped[$userId])) {
                $grouped[$userId] = [];
            }
            if (count($grouped[$userId]) < $limit) {
                $grouped[$userId][] = $eval;
            }
        }

        return $grouped;
    }
}
