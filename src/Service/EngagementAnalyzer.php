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
        private EntityManagerInterface $entityManager,
        private \App\Service\AI\AgentManager $agentManager,
    ) {}

    /**
     * Analyse l'engagement d'un utilisateur et retourne un score (0-100).
     * Plus le score est élevé, plus l'utilisateur est "à risque".
     *
     * Désormais propulsé par le modèle de Machine Learning RandomForest d'AiHub.
     */
    public function calculateRiskScore(User $user, Cohort $cohort, ?array $prefetchedEvaluations = null, ?int $prefetchedCompletionsCount = null): int
    {
        $prediction = $this->agentManager->predictStudentChurn($user);
        return (int) (round($prediction['churn_probability'] * 100));
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

        $lessonIds = [];
        foreach ($course->getModules() as $module) {
            foreach ($module->getLessons() as $lesson) {
                $lessonIds[] = $lesson->getId();
            }
        }

        $stats = $this->completionRepository->getEfficacyDataForLessons($lessonIds);

        foreach ($course->getModules() as $module) {
            foreach ($module->getLessons() as $lesson) {
                $lessonId = $lesson->getId();
                
                $totalCompletions = $stats[$lessonId]['completionCount'] ?? 0;
                $avgScore = $stats[$lessonId]['avgScore'] ?? 0.0;

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
