<?php

namespace App\Repository;

use App\Entity\Cohort;
use App\Entity\Course;
use App\Entity\Enum\CourseVisibilityEnum;
use App\Entity\User;
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
     * @return Course|null Returns a Course object with modules and lessons eager loaded
     */
    public function findCourseWithModulesAndLessonsBySlug(string $slug): ?Course
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.modules', 'm')
            ->leftJoin('m.lessons', 'l')
            ->addSelect('m', 'l')
            ->where('c.slug = :slug')
            ->setParameter('slug', $slug)
            ->addOrderBy('m.itemOrder', 'ASC')
            ->addOrderBy('m.id', 'ASC')
            ->getQuery()
            ->getOneOrNullResult();
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
            ->addOrderBy('c.itemOrder', 'ASC')
            ->addOrderBy('c.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Course[] Returns an array of Course objects with modules and lessons eager loaded
     */
    public function findCoursesWithModulesAndLessonsForUser(User $user): array
    {
        return $this->createQueryBuilder('c', 'c.id')
            ->join('c.users', 'u')
            ->leftJoin('c.modules', 'm')
            ->leftJoin('m.lessons', 'l')
            ->addSelect('m', 'l')
            ->where('u = :user')
            ->setParameter('user', $user)
            ->addOrderBy('c.itemOrder', 'ASC')
            ->addOrderBy('c.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Course[] Returns courses based on visibility and cohort
     */
    public function findCoursesByVisibilityAndCohort(?Cohort $cohort = null): array
    {
        $qb = $this->createQueryBuilder('c', 'c.id')
            ->leftJoin('c.modules', 'm')
            ->leftJoin('m.lessons', 'l')
            ->leftJoin('c.cohorts', 'co')
            ->addSelect('m', 'l')
            ->where('c.status IN (:statuses)')
            ->setParameter('statuses', ['published', 'progress']);

        if ($cohort) {
            $qb->andWhere('c.visibility = :public OR co = :cohort')
                ->setParameter('public', CourseVisibilityEnum::PUBLIC)
                ->setParameter('cohort', $cohort);
        } else {
            $qb->andWhere('c.visibility = :public')
                ->setParameter('public', CourseVisibilityEnum::PUBLIC);
        }

        $qb->addOrderBy('c.itemOrder', 'ASC')
           ->addOrderBy('c.id', 'ASC');

        return $qb->getQuery()->getResult();
    }

    /**
     * @return Course[] Returns courses for the catalog filtering by cohorts and text search
     */
    public function findCatalogCourses(array $cohorts = [], bool $isAdmin = false, ?string $search = null): array
    {
        $qb = $this->createQueryBuilder('c', 'c.id')
            ->distinct()
            ->leftJoin('c.modules', 'm')
            ->leftJoin('m.lessons', 'l')
            ->leftJoin('c.cohorts', 'co')
            ->addSelect('m', 'l')
            ->where('c.status IN (:statuses)')
            ->setParameter('statuses', ['published', 'progress']);

        if (!$isAdmin) {
            if (!empty($cohorts)) {
                $qb->andWhere('(c.visibility = :public OR co IN (:cohorts))')
                    ->setParameter('public', CourseVisibilityEnum::PUBLIC)
                    ->setParameter('cohorts', $cohorts);
            } else {
                $qb->andWhere('c.visibility = :public')
                    ->setParameter('public', CourseVisibilityEnum::PUBLIC);
            }
        } elseif (!empty($cohorts)) {
            // Admin filtrant par promo spécifique
            $qb->andWhere('co IN (:cohorts)')
               ->setParameter('cohorts', $cohorts);
        }

        if ($search) {
            $qb->andWhere('(c.title LIKE :search OR c.description LIKE :search)')
               ->setParameter('search', '%' . $search . '%');
        }

        $qb->addOrderBy('c.itemOrder', 'ASC')
           ->addOrderBy('c.id', 'ASC');

        return $qb->getQuery()->getResult();
    }
}
