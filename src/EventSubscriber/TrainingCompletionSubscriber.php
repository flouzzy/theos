<?php

namespace App\EventSubscriber;

use App\Entity\Cohort;
use App\Event\TrainingCompletionEvent;
use App\Repository\CourseCompletionRepository;
use App\Service\BrevoApi;
use Doctrine\Common\Collections\Collection;
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

        if ($this->hasCompletedAnyCohort($cohorts, $completedCourseIds)) {
            $this->brevoApi->moveToAlumniList($user);
        }
    }

    private function hasCompletedAnyCohort(Collection $cohorts, array $completedCourseIds): bool
    {
        foreach ($cohorts as $cohort) {
            if ($this->isCohortCompleted($cohort, $completedCourseIds)) {
                return true;
            }
        }

        return false;
    }

    private function isCohortCompleted(Cohort $cohort, array $completedCourseIds): bool
    {
        $cohortCourses = $cohort->getCourses();
        $totalCoursesInCohort = $cohortCourses->count();

        if ($totalCoursesInCohort === 0) {
            return false;
        }

        $completedInCohort = 0;
        foreach ($cohortCourses as $course) {
            if (in_array($course->getId(), $completedCourseIds, true)) {
                $completedInCohort++;
            }
        }

        return $completedInCohort >= $totalCoursesInCohort;
    }
}
