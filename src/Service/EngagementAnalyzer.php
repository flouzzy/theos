<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use App\Entity\Cohort;
use App\Entity\Course;
use App\Repository\CompletionRepository;
use App\Repository\EvaluationRepository;
use Doctrine\ORM\EntityManagerInterface;

class EngagementAnalyzer
{
    private const INACTIVITY_THRESHOLD_DAYS = 7;
    private const SCORE_THRESHOLD = 10.0;

    public function __construct(
        private CompletionRepository $completionRepository,
        private EvaluationRepository $evaluationRepository,
        private EntityManagerInterface $entityManager
    ) {}

    /**
     * Analyse l'engagement d'un utilisateur et retourne un score (0-100).
     * Plus le score est élevé, plus l'utilisateur est "à risque".
     */
    public function calculateRiskScore(User $user, Cohort $cohort, ?array $prefetchedEvaluations = null, ?int $prefetchedCompletionsCount = null): int
    {
        $riskScore = 0;

        // 1. Inactivité (40% du score)
        $lastConnection = $user->getLastConnectionAt();
        if ($lastConnection) {
            $daysSinceLastConnection = (new \DateTimeImmutable())->diff($lastConnection)->days;
            if ($daysSinceLastConnection >= self::INACTIVITY_THRESHOLD_DAYS) {
                $riskScore += min(40, ($daysSinceLastConnection - self::INACTIVITY_THRESHOLD_DAYS) * 5 + 20);
            }
        } else {
            $riskScore += 40; // Jamais connecté
        }

        // 2. Performance académique (30% du score)
        $evaluations = $prefetchedEvaluations ?? $this->evaluationRepository->findBy(['user' => $user, 'cohort' => $cohort], ['createdAt' => 'DESC'], 5);
        if (count($evaluations) > 0) {
            $avgScore = array_sum(array_map(fn($e) => $e->getScore(), $evaluations)) / count($evaluations);
            if ($avgScore < self::SCORE_THRESHOLD) {
                $riskScore += 30;
            } elseif ($avgScore < 14) {
                $riskScore += 15;
            }
        }

        // 3. Progression (30% du score)
        $completionsCount = $prefetchedCompletionsCount ?? $this->completionRepository->countByUserAndCohort($user, $cohort);
        // On suppose qu'un cours moyen a 20 leçons (à affiner si on a le total réel)
        $estimatedTotalLessons = 20; 
        $completionRate = ($completionsCount / $estimatedTotalLessons) * 100;
        
        if ($completionRate < 20) {
            $riskScore += 30;
        } elseif ($completionRate < 50) {
            $riskScore += 15;
        }

        return min(100, $riskScore);
    }

    /**
     * Retourne la liste des étudiants à risque pour un cohort donné.
     */
    public function getAtRiskStudents(Cohort $cohort, int $threshold = 50): array
    {
        $atRisk = [];

        $users = $cohort->getUsers()->toArray();
        if (empty($users)) {
            return [];
        }

        $completionsCounts = $this->completionRepository->countByUsersAndCohort($users, $cohort);
        $latestEvaluations = $this->evaluationRepository->findLatestByUsersAndCohort($users, $cohort, 5);

        foreach ($users as $user) {
            $userId = $user->getId();
            $userCompletions = $completionsCounts[$userId] ?? 0;
            $userEvaluations = $latestEvaluations[$userId] ?? [];

            $score = $this->calculateRiskScore($user, $cohort, $userEvaluations, $userCompletions);
            if ($score >= $threshold) {
                $atRisk[] = [
                    'user' => $user,
                    'riskScore' => $score,
                    'status' => $this->getRiskStatus($score)
                ];
            }
        }

        usort($atRisk, fn($a, $b) => $b['riskScore'] <=> $a['riskScore']);

        return $atRisk;
    }

    /**
     * Analyse l'efficacité du contenu d'un cours.
     */
    public function getContentEfficacy(Course $course): array
    {
        $efficacyData = [];
        foreach ($course->getModules() as $module) {
            foreach ($module->getLessons() as $lesson) {
                $completions = $this->completionRepository->findBy(['lesson' => $lesson]);
                $totalCompletions = count($completions);
                
                $avgScore = 0;
                $scoredCompletions = array_filter($completions, fn($c) => $c->getScore() !== null);
                if (count($scoredCompletions) > 0) {
                    $avgScore = array_sum(array_map(fn($c) => $c->getScore(), $scoredCompletions)) / count($scoredCompletions);
                }

                $efficacyData[] = [
                    'lesson' => $lesson,
                    'module' => $module,
                    'completionCount' => $totalCompletions,
                    'avgScore' => round($avgScore, 2),
                    'status' => $this->getEfficacyStatus($avgScore, $totalCompletions)
                ];
            }
        }

        return $efficacyData;
    }

    private function getEfficacyStatus(float $avgScore, int $completionCount): string
    {
        if ($completionCount === 0) return 'Pas assez de données';
        if ($avgScore > 0 && $avgScore < 10) return 'Faible';
        if ($avgScore >= 10 && $avgScore < 15) return 'Moyen';
        if ($avgScore >= 15) return 'Excellent';
        return 'Bon';
    }

    private function getRiskStatus(int $score): string
    {
        if ($score >= 80) return 'Critique';
        if ($score >= 50) return 'Élevé';
        if ($score >= 30) return 'Modéré';
        return 'Faible';
    }
}
