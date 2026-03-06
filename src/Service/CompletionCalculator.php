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
}
