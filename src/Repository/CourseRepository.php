<?php

namespace App\Repository;

use App\Entity\Course;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Course>
 *
 * @method Course|null find($id, $lockMode = null, $lockVersion = null)
 * @method Course|null findOneBy(array $criteria, array $orderBy = null)
 * @method Course[]    findAll()
 * @method Course[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CourseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Course::class);
    }

    /**
     * @return Course[] Returns an array of Course objects with modules eager loaded
     */
    public function findAllPublished(): array
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.modules', 'm')
            ->addSelect('m')
            ->where('c.status IN (:statuses)')
            ->setParameter('statuses', ['published', 'progress'])
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Course[] Returns an array of Course objects with modules and lessons eager loaded
     */
    public function findCoursesWithModulesAndLessonsForUser(\App\Entity\User $user): array
    {
        return $this->createQueryBuilder('c', 'c.id')
            ->join('c.users', 'u')
            ->leftJoin('c.modules', 'm')
            ->leftJoin('m.lessons', 'l')
            ->addSelect('m', 'l')
            ->where('u = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();
    }

}
