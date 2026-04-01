<?php

namespace App\Twig\Components;

use App\Entity\User;
use App\Repository\CompletionRepository;
use App\Repository\EvaluationRepository;
use App\Repository\ModuleCompletionRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
class UserEvaluationsComponent
{
    use DefaultActionTrait;

    private ?array $evaluationsCache = null;
    private ?array $statsCache = null;

    public function __construct(
        private Security $security,
        private EvaluationRepository $evaluationRepository,
        private ModuleCompletionRepository $moduleCompletionRepository,
        private CompletionRepository $completionRepository
    ) {
    }

    public function getEvaluations(): array
    {
        if ($this->evaluationsCache !== null) {
            return $this->evaluationsCache;
        }

        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return [];
        }

        $evaluations = [];
        $cohort = $user->getCurrentCohort();

        // 1. Fetch new Evaluation entities
        if ($cohort) {
            $dbEvaluations = $this->evaluationRepository->findBy(['user' => $user, 'cohort' => $cohort], ['createdAt' => 'DESC']);
        } else {
            $dbEvaluations = $this->evaluationRepository->findBy(['user' => $user], ['createdAt' => 'DESC']);
        }

        foreach ($dbEvaluations as $eval) {
            $maxScore = $eval->getMaxScore();
            if ($maxScore === null || $maxScore == 0.0) {
                $maxScore = 20.0;
            }
            $evalScore = $eval->getScore() ?? 0.0;
            $scaleScore = ((float)$evalScore / (float)$maxScore) * 20.0;

            $evaluations[] = [
                'title' => $eval->getTitle() ?? 'Évaluation',
                'course' => $eval->getCohort() ? ($eval->getCohort()->getTitle() ?? 'Évaluation') : 'Évaluation',
                'score' => (float)$evalScore,
                'total' => (float)$maxScore,
                'grade' => $this->calculateGrade($scaleScore),
                'date' => $eval->getCreatedAt(),
                'duration' => null,
                'feedback' => $eval->getFeedback(),
                'type' => 'evaluation'
            ];
        }

        // 2. Process Module Completions
        $moduleCompletions = $this->moduleCompletionRepository->findWithScoreByUser($user);
        foreach ($moduleCompletions as $mc) {
            $mcScore = $mc->getScore();
            if ($mcScore !== null) {
                $module = $mc->getModule();
                // Filter by cohort if set
                if ($cohort) {
                    $courses = $module ? $module->getCourses() : null;
                    $mcCourse = $courses ? $courses->first() : null;
                    if (!$mcCourse || !$cohort->getCourses()->contains($mcCourse)) {
                        continue;
                    }
                }

                $courses = $module ? $module->getCourses() : null;
                $firstCourse = $courses ? $courses->first() : null;

                $evaluations[] = [
                    'title' => $module ? ($module->getTitle() ?? 'Module') : 'Module',
                    'course' => $firstCourse ? ($firstCourse->getTitle() ?? 'Module') : 'Module',
                    'score' => $mcScore,
                    'total' => 20.0, // Assumed default
                    'grade' => $this->calculateGrade((float)$mcScore),
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
            $lcScore = $lc->getScore();
            if ($lcScore !== null) {
                $lesson = $lc->getLesson();
                $module = $lesson ? $lesson->getModule() : null;

                // Filter by cohort if set
                if ($cohort) {
                    $courses = $module ? $module->getCourses() : null;
                    $lcCourse = $courses ? $courses->first() : null;
                    if (!$lcCourse || !$cohort->getCourses()->contains($lcCourse)) {
                        continue;
                    }
                }

                $evaluations[] = [
                    'title' => $lesson ? ($lesson->getTitle() ?? 'Lesson') : 'Lesson',
                    'course' => $module ? ($module->getTitle() ?? 'Lesson') : 'Lesson',
                    'score' => $lcScore,
                    'total' => 20.0,
                    'grade' => $this->calculateGrade((float)$lcScore),
                    'date' => $lc->getUpdatedAt() ?? $lc->getCreatedAt(),
                    'duration' => $lesson && $lesson->getDuration() ? $lesson->getDuration() . ' min' : '10 min',
                    'feedback' => null,
                    'type' => 'lesson'
                ];
            }
        }

        // Sort by date desc
        usort($evaluations, function (array $a, array $b): int {
            $dateA = (isset($a['date']) && $a['date'] instanceof \DateTimeInterface) ? $a['date'] : new \DateTimeImmutable('@0');
            $dateB = (isset($b['date']) && $b['date'] instanceof \DateTimeInterface) ? $b['date'] : new \DateTimeImmutable('@0');
            return $dateB <=> $dateA;
        });

        $this->evaluationsCache = $evaluations;
        return $evaluations;
    }

    public function getStats(): array
    {
        if ($this->statsCache !== null) {
            return $this->statsCache;
        }

        $evaluations = $this->getEvaluations();

        $scores = [];
        foreach ($evaluations as $eval) {
            if (isset($eval['total']) && (float)$eval['total'] > 0) {
                $scores[] = ((float)$eval['score'] / (float)$eval['total']) * 20.0;
            }
        }

        $count = count($scores);
        $average = $count > 0 ? array_sum($scores) / $count : 0.0;
        $bestScore = $count > 0 ? (float) max($scores) : 0.0;

        $this->statsCache = [
            'average' => round($average, 1),
            'completed' => count($evaluations),
            'best_grade' => $this->calculateGrade($bestScore),
        ];

        return $this->statsCache;
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
