<?php

namespace App\Service;

use App\Entity\Course;
use App\Entity\User;
use App\Repository\CompletionRepository;

class CompletionCalculator
{

    public function __construct(private CompletionRepository $completionRepository)
    {
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
            $metrics = $this->calculateCourseMetrics($course, $completedLessonMap);

            $totalMinutes += $metrics['courseMinutes'];
            $totalLessons += $metrics['courseLessonsCount'];
            $totalCompletedLessons += $metrics['courseCompletedCount'];

            $myCoursesData[] = [
                'course' => $course,
                'progress' => $metrics['progress'],
            ];
        }

        return [
            'coursesData' => $myCoursesData,
            'globalProgress' => $totalLessons > 0 ? round(($totalCompletedLessons / $totalLessons) * 100) : 0,
            'totalHours' => floor($totalMinutes / 60),
            'newLessonsCount' => $totalLessons - $totalCompletedLessons,
        ];
    }

    private function calculateCourseMetrics(Course $course, array $completedLessonMap): array
    {
        $metrics = [
            'courseLessonsCount' => 0,
            'courseCompletedCount' => 0,
            'courseMinutes' => 0,
        ];

        foreach ($course->getModules() as $module) {
            foreach ($module->getLessons() as $lesson) {
                $metrics['courseMinutes'] += $lesson->getDuration() ?? 0;
                $metrics['courseLessonsCount']++;

                if (isset($completedLessonMap[$lesson->getId()])) {
                    $metrics['courseCompletedCount']++;
                }
            }
        }

        $metrics['progress'] = $metrics['courseLessonsCount'] > 0
            ? round(($metrics['courseCompletedCount'] / $metrics['courseLessonsCount']) * 100)
            : 0;

        return $metrics;
    }
}
