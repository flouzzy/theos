<?php

namespace App\Repository;

use App\Entity\Completion;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Completion>
 *
 * @method Completion|null find($id, $lockMode = null, $lockVersion = null)
 * @method Completion|null findOneBy(array $criteria, array $orderBy = null)
 * @method Completion[]    findAll()
 * @method Completion[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CompletionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Completion::class);
    }

//    /**
//     * @return Completion[] Returns an array of Completion objects
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

//    public function findOneBySomeField($value): ?Completion
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
