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
        CohortSession $cohortSession,
        \App\Service\LeaderboardService $leaderboardService,
        \App\Service\CompletionCalculator $completionCalculator
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
        
        $progressData = $completionCalculator->calculateCohortProgress($user, $myCoursesEntities);

        $completedCoursesCount = $courseCompletionRepository->countCompletedCoursesForUser($user);
        $ongoingCoursesCount = count($myCoursesEntities) - $completedCoursesCount;

        // Events
        $events = $eventRepository->findUpdatedEvents($cohort);

        // Resume lesson logic
        $lastLesson = $completionRepository->findLastInteractedLesson($user);

        return $this->render('cohort/index.html.twig', [
            'cohort' => $cohort,
            'myCourses' => $progressData['coursesData'],
            'events' => $events,
            'newLessonsCount' => $progressData['newLessonsCount'],
            'lastLesson' => $lastLesson,
            'topStudent' => $leaderboardService->getStudentOfTheWeek(),
            'stats' => [
                'ongoing' => $ongoingCoursesCount,
                'completed' => $completedCoursesCount,
                'hours' => $progressData['totalHours'],
                'progress' => $progressData['globalProgress'],
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
    #[Route('/{id}/chat', name: 'chat')]
    public function chat(Cohort $cohort): Response
    {
        return $this->render('cohort/chat.html.twig', [
            'cohort' => $cohort,
        ]);
    }
}
