<?php

namespace App\Service;

use App\Entity\Course;
use App\Entity\User;
use App\Repository\CompletionRepository;

class CompletionCalculator
{

    public function __construct(
        private CompletionRepository $completionRepository,
        private \Doctrine\ORM\EntityManagerInterface $entityManager
    ) {
    }



    /**
     * Fetch user courses grouped by userId.
     */
    private function fetchUserCourses(array $users, array &$userCourses, array &$courseIds): void
    {
        $qb = $this->entityManager->createQueryBuilder()
            ->select('u.id AS userId, c.id AS courseId')
            ->from(\App\Entity\User::class, 'u')
            ->join('u.courses', 'c')
            ->where('u IN (:users)')
            ->setParameter('users', $users);

        $result = $qb->getQuery()->getArrayResult();
        foreach ($result as $row) {
            $userId = $row['userId'];
            $courseId = $row['courseId'];
            if (!isset($userCourses[$userId])) {
                $userCourses[$userId] = [];
            }
            $userCourses[$userId][] = $courseId;
            $courseIds[$courseId] = $courseId;
        }
    }

    /**
     * Fetch total lessons per course.
     */
    private function fetchCourseTotalLessons(array $courseIds): array
    {
        $courseLessons = [];
        $qb2 = $this->entityManager->createQueryBuilder()
            ->select('c.id AS courseId, COUNT(DISTINCT l.id) AS totalLessons')
            ->from(\App\Entity\Course::class, 'c')
            ->join('c.modules', 'm')
            ->join('m.lessons', 'l')
            ->where('c IN (:courses)')
            ->setParameter('courses', $courseIds)
            ->groupBy('c.id');

        $result2 = $qb2->getQuery()->getArrayResult();
        foreach ($result2 as $row) {
            $courseLessons[$row['courseId']] = (int) $row['totalLessons'];
        }
        return $courseLessons;
    }

    /**
     * Fetch completed lessons count per user and course.
     */
    private function fetchUserCompletedLessons(array $users, array $courseIds): array
    {
        $completedLessons = [];
        $qb3 = $this->entityManager->createQueryBuilder()
            ->select('IDENTITY(comp.user) AS userId, c.id AS courseId, COUNT(DISTINCT l.id) AS completedCount')
            ->from(\App\Entity\Completion::class, 'comp')
            ->join('comp.lesson', 'l')
            ->join('l.module', 'm')
            ->join('m.courses', 'c')
            ->where('comp.user IN (:users)')
            ->andWhere('comp.completed = true')
            ->andWhere('c IN (:courses)')
            ->setParameter('users', $users)
            ->setParameter('courses', $courseIds)
            ->groupBy('comp.user', 'c.id');

        $result3 = $qb3->getQuery()->getArrayResult();
        foreach ($result3 as $row) {
            if (!isset($completedLessons[$row['userId']])) {
                $completedLessons[$row['userId']] = [];
            }
            $completedLessons[$row['userId']][$row['courseId']] = (int) $row['completedCount'];
        }
        return $completedLessons;
    }

    /**
     * Compute and calculate progresses.
     */
    private function computeProgresses(array $userCourses, array $courseLessons, array $completedLessons, array &$progresses): void
    {
        foreach ($userCourses as $userId => $cIds) {
            $totalProgress = 0;
            $courseCount = count($cIds);
            if ($courseCount === 0) continue;

            foreach ($cIds as $courseId) {
                $totalLessonCount = $courseLessons[$courseId] ?? 0;
                if ($totalLessonCount > 0) {
                    $completedCount = $completedLessons[$userId][$courseId] ?? 0;
                    $courseProgress = round(($completedCount / $totalLessonCount) * 100, 2);
                    $totalProgress += $courseProgress;
                }
            }

            $progresses[$userId] = round($totalProgress / $courseCount, 2);
        }
    }

    public function calculateGlobalProgressForUsers(array $users): array
    {
        $progresses = [];
        foreach ($users as $user) {
            $progresses[$user->getId()] = 0.0;
        }

        if (empty($users)) {
            return $progresses;
        }

        $userCourses = [];
        $courseIds = [];

        $this->fetchUserCourses($users, $userCourses, $courseIds);

        if (!empty($courseIds)) {
            $courseLessons = $this->fetchCourseTotalLessons($courseIds);
            $completedLessons = $this->fetchUserCompletedLessons($users, $courseIds);

            $this->computeProgresses($userCourses, $courseLessons, $completedLessons, $progresses);
        }

        return $progresses;
    }

    public function calculateCompletionPercentage(Course $course, User $user): float
    {
        $totalLessons = 0;
        $completedLessons = 0;

        $modules = $course->getModules();
        foreach ($modules as $module) {
            $totalLessons += $module->getLessons()->count();
        }

        if ($totalLessons === 0) {
            return 0; // Pour éviter la division par zéro
        }

        $completedLessonIds = $this->completionRepository->findCompletedLessonIdsByCourse($user, $course);

        foreach ($modules as $module) {
            $lessons = $module->getLessons();
            foreach ($lessons as $lesson) {
                if (in_array($lesson->getId(), $completedLessonIds, true)) {
                    $completedLessons++;
                }
            }
        }

        return round(($completedLessons / $totalLessons) * 100, 2);
    }

    public function calculateGlobalProgress(User $user): float
    {
        $courses = $user->getCourses();
        if ($courses->isEmpty()) {
            return 0;
        }

        $totalProgress = 0;
        foreach ($courses as $course) {
            $totalProgress += $this->calculateCompletionPercentage($course, $user);
        }

        return round($totalProgress / $courses->count(), 2);
    }

    public function calculateCohortProgress(User $user, array $coursesEntities): array
    {
        $myCoursesData = [];
        $totalMinutes = 0;
        $totalLessons = 0;
        $totalCompletedLessons = 0;

        $completedLessonIds = $this->completionRepository->findCompletedLessonIdsByUser($user);
        $completedLessonMap = array_flip($completedLessonIds);

        foreach ($coursesEntities as $course) {
            $courseLessonsCount = 0;
            $courseCompletedCount = 0;

            foreach ($course->getModules() as $module) {
                foreach ($module->getLessons() as $lesson) {
                    $totalMinutes += $lesson->getDuration() ?? 0;
                    $courseLessonsCount++;
                    $totalLessons++;

                    if (isset($completedLessonMap[$lesson->getId()])) {
                        $courseCompletedCount++;
                        $totalCompletedLessons++;
                    }
                }
            }

            $progress = $courseLessonsCount > 0 ? round(($courseCompletedCount / $courseLessonsCount) * 100) : 0;

            $myCoursesData[] = [
                'course' => $course,
                'progress' => $progress,
            ];
        }

        return [
            'coursesData' => $myCoursesData,
            'globalProgress' => $totalLessons > 0 ? round(($totalCompletedLessons / $totalLessons) * 100) : 0,
            'totalHours' => floor($totalMinutes / 60),
            'newLessonsCount' => $totalLessons - $totalCompletedLessons,
        ];
    }
}
