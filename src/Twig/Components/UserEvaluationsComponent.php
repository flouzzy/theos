<?php

namespace App\Twig\Components;

use App\Entity\User;
use App\Repository\CompletionRepository;
use App\Repository\EvaluationRepository;
use App\Repository\ModuleCompletionRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\Attribute\LiveProp;

#[AsLiveComponent]
class UserEvaluationsComponent
{
    use DefaultActionTrait;

    public function __construct(
        private Security $security,
        private EvaluationRepository $evaluationRepository,
        private ModuleCompletionRepository $moduleCompletionRepository,
        private CompletionRepository $completionRepository
    ) {
    }

    public function getEvaluations(): array
    {
        /** @var User $user */
        $user = $this->security->getUser();
        if (!$user) {
            return [];
        }

        $evaluations = [];

        // 1. Fetch new Evaluation entities (filtered by current cohort if any)
        $cohort = $user->getCurrentCohort();
        $dbEvaluations = [];
        if ($cohort) {
            $dbEvaluations = $this->evaluationRepository->findBy(['user' => $user, 'cohort' => $cohort], ['createdAt' => 'DESC']);
        } else {
            $dbEvaluations = $this->evaluationRepository->findBy(['user' => $user], ['createdAt' => 'DESC']);
        }

        foreach ($dbEvaluations as $eval) {
            $evaluations[] = [
                'title' => $eval->getTitle(),
                'course' => $eval->getCohort() ? $eval->getCohort()->getName() : 'Évaluation',
                'score' => $eval->getScore(),
                'total' => $eval->getMaxScore(),
                'grade' => $this->calculateGrade(($eval->getScore() / ($eval->getMaxScore() ?: 1)) * 20),
                'date' => $eval->getCreatedAt(),
                'duration' => null,
                'feedback' => $eval->getFeedback(),
                'type' => 'evaluation'
            ];
        }

        // 2. Process Module Completions
        $moduleCompletions = $this->moduleCompletionRepository->findWithScoreByUser($user);
        foreach ($moduleCompletions as $mc) {
            if ($mc->getScore() !== null) {
                // If the user has a current cohort, you could try to filter, but we include them generally
                $evaluations[] = [
                    'title' => $mc->getModule() ? $mc->getModule()->getTitle() : 'Module',
                    'course' => $mc->getModule() && $mc->getModule()->getCourses()->first() ? $mc->getModule()->getCourses()->first()->getTitle() : 'Module',
                    'score' => $mc->getScore(),
                    'total' => 20, // Assumed default
                    'grade' => $this->calculateGrade($mc->getScore()),
                    'date' => $mc->getUpdatedAt() ?? $mc->getCreatedAt(),
                    'duration' => '30 min',
                    'feedback' => null,
                    'type' => 'module'
                ];
            }
        }

        // 3. Process Lesson Completions (Quizzes)
        $lessonCompletions = $this->completionRepository->findWithScoreByUser($user);
        foreach ($lessonCompletions as $lc) {
            if ($lc->getScore() !== null) {
                $evaluations[] = [
                    'title' => $lc->getLesson() ? $lc->getLesson()->getTitle() : 'Lesson',
                    'course' => $lc->getLesson() && $lc->getLesson()->getModule() ? $lc->getLesson()->getModule()->getTitle() : 'Lesson',
                    'score' => $lc->getScore(),
                    'total' => 20,
                    'grade' => $this->calculateGrade($lc->getScore()),
                    'date' => $lc->getUpdatedAt() ?? $lc->getCreatedAt(),
                    'duration' => $lc->getLesson() && $lc->getLesson()->getDuration() ? $lc->getLesson()->getDuration() . ' min' : '10 min',
                    'feedback' => null,
                    'type' => 'lesson'
                ];
            }
        }

        // Sort by date desc
        usort($evaluations, fn($a, $b) => ($b['date'] ?? new \DateTimeImmutable('@0')) <=> ($a['date'] ?? new \DateTimeImmutable('@0')));

        return $evaluations;
    }

    public function getStats(): array
    {
        $evaluations = $this->getEvaluations();

        $scores = [];
        foreach ($evaluations as $eval) {
            if (isset($eval['total']) && $eval['total'] > 0) {
                $scores[] = ($eval['score'] / $eval['total']) * 20;
            }
        }

        $count = count($scores);
        $average = $count > 0 ? array_sum($scores) / $count : 0;
        $bestScore = $count > 0 ? (float) max($scores) : 0.0;

        return [
            'average' => round($average, 1),
            'completed' => count($evaluations),
            'best_grade' => $this->calculateGrade($bestScore),
        ];
    }

    public function calculateGrade(float $score): string
    {
        if ($score >= 18) return 'A+';
        if ($score >= 16) return 'A';
        if ($score >= 14) return 'B';
        if ($score >= 12) return 'C';
        if ($score >= 10) return 'D';
        return 'E';
    }
}
