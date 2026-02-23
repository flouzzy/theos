<?php

namespace App\Controller;

use App\Entity\Cohort;
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
        \App\Repository\CompletionRepository $completionRepository
    ): Response {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        
        // Redirect to login if not authenticated
        if (!$user) {
            return $this->redirectToRoute('login');
        }
        
        // Récupère la première cohorte du user (ou null)
        $cohort = $user->getCohorts()->first() ?: null;
        
        // Récupère les cours et calcule la progression
        $myCoursesEntities = $courseRepository->findCoursesWithModulesAndLessonsForUser($user);
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
        $completedCoursesCount = $user->getCourseCompletions()->filter(fn($cc) => $cc->isCompleted())->count();
        $ongoingCoursesCount = count($myCoursesEntities) - $completedCoursesCount;
        $totalHours = floor($totalMinutes / 60);

        // Calculate remaining lessons to discover
        $newLessonsCount = $totalLessons - $totalCompletedLessons;

        // Events
        $events = $eventRepository->findUpdatedEvents($cohort);

        return $this->render('cohort/index.html.twig', [
            'cohort' => $cohort,
            'myCourses' => $myCoursesData, 
            'events' => $events,
            'newLessonsCount' => $newLessonsCount,
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
}
