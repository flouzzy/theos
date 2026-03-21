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
