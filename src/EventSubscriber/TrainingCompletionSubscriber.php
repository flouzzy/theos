<?php

namespace App\EventSubscriber;

use App\Event\TrainingCompletionEvent;
use App\Repository\CourseCompletionRepository;
use App\Service\BrevoApi;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class TrainingCompletionSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private BrevoApi $brevoApi,
        private CourseCompletionRepository $courseCompletionRepository
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            TrainingCompletionEvent::class => 'onTrainingCompleted',
        ];
    }

    public function onTrainingCompleted(TrainingCompletionEvent $event): void
    {
        $user = $event->getUser();
        
        // We consider training completed if the user has completed all courses in at least one of their cohorts
        $cohorts = $user->getCohorts();
        if ($cohorts->count() === 0) {
            return;
        }

        $completedCourseIds = $this->courseCompletionRepository->findCompletedCourseIdsForUser($user);
        
        $isAlumni = false;
        foreach ($cohorts as $cohort) {
            $cohortCourses = $cohort->getCourses();
            $totalCoursesInCohort = $cohortCourses->count();
            
            if ($totalCoursesInCohort > 0) {
                $completedInCohort = 0;
                foreach ($cohortCourses as $course) {
                    if (in_array($course->getId(), $completedCourseIds, true)) {
                        $completedInCohort++;
                    }
                }

                if ($completedInCohort >= $totalCoursesInCohort) {
                    $isAlumni = true;
                    break;
                }
            }
        }

        if ($isAlumni) {
            $this->brevoApi->moveToAlumniList($user);
        }
    }
}
