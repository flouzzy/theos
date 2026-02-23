<?php

namespace App\Controller;

use App\Repository\CompletionRepository;
use App\Repository\ModuleCompletionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/evaluation', name: 'evaluation_')]
class EvaluationController extends AbstractController
{
    #[Route('/', name: 'index')]
    public function index(
        ModuleCompletionRepository $moduleCompletionRepository,
        CompletionRepository $completionRepository
    ): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // Fetch completions with scores
        /** @var \App\Entity\User $user */
        $moduleCompletions = $moduleCompletionRepository->findByUserWithModuleAndCourses($user);
        $lessonCompletions = $completionRepository->findByUserWithLessonAndModule($user);

        $evaluations = [];
        $scores = [];

        // Process Module Completions
        foreach ($moduleCompletions as $mc) {
            if ($mc->getScore() !== null) {
                $scores[] = $mc->getScore();
                $evaluations[] = [
                    'title' => $mc->getModule()->getTitle(),
                    'course' => $mc->getModule()->getCourses()->first() ? $mc->getModule()->getCourses()->first()->getTitle() : 'Module',
                    'score' => $mc->getScore(),
                    'total' => 20, // Assumed default
                    'grade' => $this->calculateGrade($mc->getScore()),
                    'date' => $mc->getUpdatedAt() ?? $mc->getCreatedAt(),
                    'duration' => '30 min', // Placeholder or add duration to Completion
                    'type' => 'module'
                ];
            }
        }

        // Process Lesson Completions (Quizzes)
        foreach ($lessonCompletions as $lc) {
             if ($lc->getScore() !== null) {
                $scores[] = $lc->getScore();
                $evaluations[] = [
                    'title' => $lc->getLesson()->getTitle(),
                    'course' => $lc->getLesson()->getModule() ? $lc->getLesson()->getModule()->getTitle() : 'Lesson',
                    'score' => $lc->getScore(),
                    'total' => 20,
                    'grade' => $this->calculateGrade($lc->getScore()),
                    'date' => $lc->getUpdatedAt() ?? $lc->getCreatedAt(),
                    'duration' => $lc->getLesson()->getDuration() ? $lc->getLesson()->getDuration() . ' min' : '10 min',
                    'type' => 'lesson'
                ];
            }
        }

        // Sort by date desc
        usort($evaluations, fn($a, $b) => $b['date'] <=> $a['date']);

        // Calculate Stats
        $count = count($scores);
        $average = $count > 0 ? array_sum($scores) / $count : 0;
        $bestScore = $count > 0 ? max($scores) : 0;
        $bestGrade = $count > 0 ? $this->calculateGrade($bestScore) : '-';

        $stats = [
            'average' => round($average, 1),
            'completed' => $count,
            'best_grade' => $bestGrade,
        ];

        return $this->render('evaluation/index.html.twig', [
            'stats' => $stats,
            'evaluations' => $evaluations
        ]);
    }

    private function calculateGrade(float $score): string
    {
        if ($score >= 18) return 'A+';
        if ($score >= 16) return 'A';
        if ($score >= 14) return 'B';
        if ($score >= 12) return 'C';
        if ($score >= 10) return 'D';
        return 'E';
    }
}
