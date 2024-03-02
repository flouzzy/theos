<?php

namespace App\Repository;

use App\Entity\CourseCompletion;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CourseCompletion>
 *
 * @method CourseCompletion|null find($id, $lockMode = null, $lockVersion = null)
 * @method CourseCompletion|null findOneBy(array $criteria, array $orderBy = null)
 * @method CourseCompletion[]    findAll()
 * @method CourseCompletion[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CourseCompletionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CourseCompletion::class);
    }

//    /**
//     * @return CourseCompletion[] Returns an array of CourseCompletion objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('c.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?CourseCompletion
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
