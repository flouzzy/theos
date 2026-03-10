<?php

namespace App\Controller;

use App\Entity\Cohort;
use App\Service\CohortSession;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/cohort', name: 'cohort_')]
class CohortController extends AbstractController
{
    #[Route('/', name: 'index', priority: 3)]
    public function index(
        \App\Repository\EventRepository $eventRepository,
        \App\Repository\CourseRepository $courseRepository,
        \App\Repository\CompletionRepository $completionRepository,
        \App\Repository\CourseCompletionRepository $courseCompletionRepository,
        CohortSession $cohortSession
    ): Response {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        
        // Redirect to login if not authenticated
        if (!$user) {
            return $this->redirectToRoute('login');
        }
        
        // Récupère la cohorte active via le service
        $cohort = $cohortSession->getSelectedCohort();
        
        // Récupère les cours disponibles pour cette cohorte (Public + Restreint à cette cohorte)
        $myCoursesEntities = $courseRepository->findCoursesByVisibilityAndCohort($cohort);
        $myCoursesData = [];
        
        $totalMinutes = 0;
        $totalLessons = 0;
        $totalCompletedLessons = 0;
        
        // Optimisation: récupérer tous les IDs des leçons complétées par le user
        $completedLessonIds = $completionRepository->findCompletedLessonIdsByUser($user);
        $completedLessonMap = array_flip($completedLessonIds);

        foreach ($myCoursesEntities as $course) {
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

        // Stats Globales
        $globalProgress = $totalLessons > 0 ? round(($totalCompletedLessons / $totalLessons) * 100) : 0;
        $completedCoursesCount = $courseCompletionRepository->countCompletedCoursesForUser($user);
        $ongoingCoursesCount = count($myCoursesEntities) - $completedCoursesCount;
        $totalHours = floor($totalMinutes / 60);

        // Calculate remaining lessons to discover
        $newLessonsCount = $totalLessons - $totalCompletedLessons;

        // Events
        $events = $eventRepository->findUpdatedEvents($cohort);

        // Resume lesson logic
        $lastLesson = $completionRepository->findLastInteractedLesson($user);

        return $this->render('cohort/index.html.twig', [
            'cohort' => $cohort,
            'myCourses' => $myCoursesData, 
            'events' => $events,
            'newLessonsCount' => $newLessonsCount,
            'lastLesson' => $lastLesson,
            'stats' => [
                'ongoing' => $ongoingCoursesCount,
                'completed' => $completedCoursesCount,
                'hours' => $totalHours,
                'progress' => $globalProgress,
            ]
        ]);
    }

    #[Route('/{slug}', name: 'show')]
    public function show(Cohort $cohort): Response
    {
        return $this->render('cohort/show.html.twig', [
            'cohort' => [$cohort],
        ]);
    }

    #[Route('/switch/{id}', name: 'switch')]
    public function switch(Cohort $cohort, CohortSession $cohortSession): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        // Vérifier que l'utilisateur appartient bien à cette cohorte
        if ($user && $user->getCohorts()->contains($cohort)) {
            $cohortSession->setSelectedCohort($cohort);
        }

        return $this->redirectToRoute('cohort_index');
    }
}
