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

        $evaluations = array_merge(
            $this->getEvaluationRecords($user, $cohort),
            $this->getModuleRecords($user, $cohort),
            $this->getLessonRecords($user, $cohort)
        );

        // Sort by date desc
        usort($evaluations, function (array $a, array $b): int {
            $dateA = isset($a['date']) ? $a['date'] : new \DateTimeImmutable('@0');
            $dateB = isset($b['date']) ? $b['date'] : new \DateTimeImmutable('@0');
            return $dateB <=> $dateA;
        });

        $this->evaluationsCache = $evaluations;
        return $evaluations;
    }

    private function getLessonRecords(User $user, ?\App\Entity\Cohort $cohort): array
    {
        $evaluations = [];
        $lessonCompletions = $this->completionRepository->findWithScoreByUser($user);

        foreach ($lessonCompletions as $lc) {
            $lcScore = $lc->getScore();
            if ($lcScore !== null) {
                $lesson = $lc->getLesson();
                $module = $lesson ? $lesson->getModule() : null;
                $courses = $module ? $module->getCourses() : null;
                $lcCourse = $courses ? $courses->first() : null;

                if ($cohort) {
                    if (!$lcCourse || !$cohort->getCourses()->contains($lcCourse)) {
                        continue;
                    }
                }

                $evaluations[] = [
                    'title' => $lesson ? ($lesson->getTitle() ?? 'Lesson') : 'Lesson',
                    'course' => $module ? ($module->getTitle() ?? 'Lesson') : 'Lesson',
                    'score' => $lcScore,
                    'total' => 20.0,
                    'grade' => $this->calculateGrade($lcScore),
                    'date' => $lc->getUpdatedAt() ?? $lc->getCreatedAt(),
                    'duration' => $lesson && $lesson->getDuration() ? ((string)$lesson->getDuration() . ' min') : '10 min',
                    'feedback' => null,
                    'type' => 'lesson'
                ];
            }
        }

        return $evaluations;
    }

    private function getModuleRecords(User $user, ?\App\Entity\Cohort $cohort): array
    {
        $evaluations = [];
        $moduleCompletions = $this->moduleCompletionRepository->findWithScoreByUser($user);

        foreach ($moduleCompletions as $mc) {
            $mcScore = $mc->getScore();
            if ($mcScore !== null) {
                $module = $mc->getModule();
                $courses = $module ? $module->getCourses() : null;
                $firstCourse = $courses ? $courses->first() : null;

                if ($cohort) {
                    if (!$firstCourse || !$cohort->getCourses()->contains($firstCourse)) {
                        continue;
                    }
                }

                $evaluations[] = [
                    'title' => $module ? ($module->getTitle() ?? 'Module') : 'Module',
                    'course' => $firstCourse ? ($firstCourse->getTitle() ?? 'Module') : 'Module',
                    'score' => $mcScore,
                    'total' => 20.0,
                    'grade' => $this->calculateGrade($mcScore),
                    'date' => $mc->getUpdatedAt() ?? $mc->getCreatedAt(),
                    'duration' => '30 min',
                    'feedback' => null,
                    'type' => 'module'
                ];
            }
        }

        return $evaluations;
    }

    private function getEvaluationRecords(User $user, ?\App\Entity\Cohort $cohort): array
    {
        $evaluations = [];

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
            $scaleScore = ($evalScore / $maxScore) * 20.0;

            $cohortTitle = 'Évaluation';
            if ($eval->getCohort()) {
                $cohortTitle = $eval->getCohort()->getTitle() ?? 'Évaluation';
            }

            $evaluations[] = [
                'title' => $eval->getTitle() ?? 'Évaluation',
                'course' => $cohortTitle,
                'score' => $evalScore,
                'total' => $maxScore,
                'grade' => $this->calculateGrade($scaleScore),
                'date' => $eval->getCreatedAt(),
                'duration' => null,
                'feedback' => $eval->getFeedback(),
                'type' => 'evaluation'
            ];
        }

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
