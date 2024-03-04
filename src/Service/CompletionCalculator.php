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
            $lessons = $module->getLessons();
            foreach ($lessons as $lesson) {
                $totalLessons++;
                $completion = $this->completionRepository->findOneBy(['user' => $user, 'lesson' => $lesson]);
                if ($completion && $completion->isCompleted()) {
                    $completedLessons++;
                }
            }
        }

        if ($totalLessons === 0) {
            return 0; // Pour éviter la division par zéro
        }

        return round(($completedLessons / $totalLessons) * 100, 2);
    }
}
