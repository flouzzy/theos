<?php

namespace App\Repository;

use App\Entity\Completion;
use App\Entity\Course;
use App\Entity\User;
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

    /**
     * Retourne le total des leçons completétés (completion à true) par les utilisateurs
     */
    public function countLessonsCompletions()
    {
        return $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->andWhere('c.completed = true')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return int[]
     */
    public function findCompletedLessonIdsByCourse(User $user, Course $course): array
    {
        return $this->createQueryBuilder('c')
            ->select('l.id')
            ->join('c.lesson', 'l')
            ->join('l.module', 'm')
            ->join('m.courses', 'co')
            ->where('c.user = :user')
            ->andWhere('c.completed = true')
            ->andWhere('co = :course')
            ->setParameter('user', $user)
            ->setParameter('course', $course)
            ->getQuery()
            ->getSingleColumnResult();
    }

    /**
     * @return int[]
     */
    public function findCompletedLessonIdsByUser(User $user): array
    {
        return $this->createQueryBuilder('c')
            ->select('IDENTITY(c.lesson)')
            ->where('c.user = :user')
            ->andWhere('c.completed = true')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleColumnResult();
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
