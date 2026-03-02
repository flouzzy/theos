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

    /**
     * Retourne le total des modules completétés par les utilisateurs
     */
    public function countCoursesCompletions()
    {
        return $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->andWhere('c.completed = true')
            ->getQuery()
            ->getSingleScalarResult();
    }

}
