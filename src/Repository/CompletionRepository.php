<?php

namespace App\Repository;

use App\Entity\Completion;
use App\Entity\Course;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Completion>
 *
 * @method Completion|null find($id, $lockMode = null, $lockVersion = null)
 * @method Completion|null findOneBy(array<string, mixed> $criteria, array<string, string>|null $orderBy = null)
 * @method Completion[]    findAll()
 * @method Completion[]    findBy(array<string, mixed> $criteria, array<string, string>|null $orderBy = null, int|null $limit = null, int|null $offset = null)
 */
class CompletionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Completion::class);
    }

    /**
     * Retourne le total des leçons completétés (completion à true) par les utilisateurs
     * @return int|string|float|bool|null
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
     * @param User[] $users
     * @return array<int, int>
     */
    public function countByUsersAndCohort(array $users, \App\Entity\Cohort $cohort): array
    {
        if (empty($users)) {
            return [];
        }

        $qb = $this->createQueryBuilder('c')
            ->select('IDENTITY(c.user) as userId', 'COUNT(c.id) as completionsCount')
            ->join('c.lesson', 'l')
            ->join('l.module', 'm')
            ->join('m.courses', 'co')
            ->where('c.user IN (:users)')
            ->andWhere('c.completed = true')
            ->andWhere('co IN (:courses)')
            ->setParameter('users', $users)
            ->setParameter('courses', $cohort->getCourses())
            ->groupBy('c.user');

        $result = $qb->getQuery()->getResult();

        $counts = [];
        foreach ($result as $row) {
            $counts[(int) $row['userId']] = (int) $row['completionsCount'];
        }

        return $counts;
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
     * @return Completion[]
     */
    public function findWithScoreByUser(User $user): array
    {
        return $this->createQueryBuilder('c')
            ->select('c', 'l', 'm')
            ->join('c.lesson', 'l')
            ->leftJoin('l.module', 'm')
            ->andWhere('c.user = :user')
            ->andWhere('c.completed = true')
            ->andWhere('c.score IS NOT NULL')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();
    }

    /**
     * Calcule la durée totale des leçons complétées par un utilisateur (en minutes)
     */
    public function countTotalDurationByUser(User $user): int
    {
        return (int) $this->createQueryBuilder('c')
            ->select('SUM(l.duration)')
            ->join('c.lesson', 'l')
            ->where('c.user = :user')
            ->andWhere('c.completed = true')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
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

    /**
     * @return Paginator<Completion>
     */
    public function findPaginated(int $page, int $limit = 50): Paginator
    {
        $query = $this->createQueryBuilder('c')
            ->orderBy('c.id', 'DESC')
            ->getQuery();
        $paginator = new Paginator($query);
        $paginator->getQuery()
            ->setFirstResult($limit * ($page - 1))
            ->setMaxResults($limit);
        return $paginator;
    }

    public function findLastInteractedLesson(User $user): ?\App\Entity\Lesson
    {
        $completion = $this->createQueryBuilder('c')
            ->where('c.user = :user')
            ->orderBy('c.updatedAt', 'DESC')
            ->setMaxResults(1)
            ->setParameter('user', $user)
            ->getQuery()
            ->getOneOrNullResult();

        return $completion?->getLesson();
    }

    public function countByUserAndCohort(User $user, \App\Entity\Cohort $cohort): int
    {
        return (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->join('c.lesson', 'l')
            ->join('l.module', 'm')
            ->join('m.courses', 'co')
            ->where('c.user = :user')
            ->andWhere('c.completed = true')
            ->andWhere('co IN (:courses)')
            ->setParameter('user', $user)
            ->setParameter('courses', $cohort->getCourses())
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countCompletionsForCourseBetween(Course $course, \DateTimeImmutable $start, \DateTimeImmutable $end): int
    {
        return (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->join('c.lesson', 'l')
            ->join('l.module', 'm')
            ->join('m.courses', 'co')
            ->where('co = :course')
            ->andWhere('c.completed = true')
            ->andWhere('c.updatedAt BETWEEN :start AND :end')
            ->setParameter('course', $course)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countByUserBetween(User $user, \DateTimeImmutable $start, \DateTimeImmutable $end): int
    {
        return (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->where('c.user = :user')
            ->andWhere('c.completed = true')
            ->andWhere('c.updatedAt BETWEEN :start AND :end')
            ->setParameter('user', $user)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @param int[] $lessonIds
     * @return array<int, int> [lessonId => count]
     */
    public function countCompletionsForLessons(array $lessonIds): array
    {
        if (empty($lessonIds)) {
            return [];
        }

        $results = $this->createQueryBuilder('c')
            ->select('IDENTITY(c.lesson) as lessonId', 'COUNT(c.id) as completionsCount')
            ->where('c.lesson IN (:lessonIds)')
            ->andWhere('c.completed = true')
            ->setParameter('lessonIds', $lessonIds)
            ->groupBy('c.lesson')
            ->getQuery()
            ->getArrayResult();

        $counts = [];
        foreach ($results as $row) {
            $counts[(int) $row['lessonId']] = (int) $row['completionsCount'];
        }

        return $counts;
    }

    /**
     * Fetches up to $limit latest completions for all given users.
     * Returns an array grouped by user ID, containing an array of createdAt DateTimeImmutable objects.
     * @param User[] $users
     * @return array<int, \DateTimeImmutable[]> [userId => [createdAt, ...]]
     */
    public function findLatestCompletionsGroupedByUser(array $users, int $limit = 20): array
    {
        if (empty($users)) {
            return [];
        }

        // Fetch all completions for the given users, ordered by user and createdAt DESC
        $results = $this->createQueryBuilder('c')
            ->select('IDENTITY(c.user) as userId', 'c.createdAt')
            ->where('c.user IN (:users)')
            ->setParameter('users', $users)
            ->orderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        $grouped = [];
        foreach ($results as $row) {
            $userId = (int) $row['userId'];
            if (!isset($grouped[$userId])) {
                $grouped[$userId] = [];
            }
            if (count($grouped[$userId]) < $limit) {
                // Keep only the first $limit records per user, which are already sorted DESC
                $grouped[$userId][] = $row['createdAt'];
            }
        }

        return $grouped;
    }

    /**
     * @return array<int, array{completionCount: int, avgScore: float}> [lessonId => stats]
     */
    public function getEfficacyDataForLessons(array $lessonIds): array
    {
        if (empty($lessonIds)) {
            return [];
        }

        $results = $this->createQueryBuilder('c')
            ->select('IDENTITY(c.lesson) as lessonId', 'COUNT(c.id) as completionCount', 'AVG(c.score) as avgScore')
            ->where('c.lesson IN (:lessonIds)')
            ->setParameter('lessonIds', $lessonIds)
            ->groupBy('c.lesson')
            ->getQuery()
            ->getArrayResult();

        $stats = [];
        foreach ($results as $row) {
            $stats[(int) $row['lessonId']] = [
                'completionCount' => (int) $row['completionCount'],
                'avgScore' => (float) $row['avgScore'],
            ];
        }

        return $stats;
    }
}
