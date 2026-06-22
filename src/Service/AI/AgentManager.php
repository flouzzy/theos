<?php
 
declare(strict_types=1);
 
namespace App\Service\AI;
 
use App\Entity\User;
use App\Repository\CompletionRepository;
use App\Repository\EvaluationRepository;
use App\Repository\CommentRepository;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
 
class AgentManager
{
    public function __construct(
        private readonly HttpClientInterface $client,
        private readonly CompletionRepository $completionRepository,
        private readonly EvaluationRepository $evaluationRepository,
        private readonly CommentRepository $commentRepository,
        private readonly LoggerInterface $logger,
    ) {
    }
 
    public function predictStudentChurn(User $user): array
    {
        $daysSinceLastLogin = 100;
        if ($user->getLastConnectionAt()) {
            $daysSinceLastLogin = (new \DateTimeImmutable())->diff($user->getLastConnectionAt())->days;
        }
 
        $lessonsCompleted = count($this->completionRepository->findCompletedLessonIdsByUser($user));
 
        $evaluations = $this->evaluationRepository->findBy(['user' => $user]);
        $totalPercentage = 0.0;
        $evalCount = count($evaluations);
        if ($evalCount > 0) {
            foreach ($evaluations as $eval) {
                $max = $eval->getMaxScore() ?: 20.0;
                $score = $eval->getScore() ?: 0.0;
                $totalPercentage += ($score / $max) * 100;
            }
            $quizAverage = $totalPercentage / $evalCount;
        } else {
            $quizAverage = 80.0; // Default fallback score
        }
 
        $commentsCount = $this->commentRepository->count(['user' => $user]);
 
        try {
            $response = $this->client->request('POST', 'http://aihub-service:8000/theos/predict-churn', [
                'json' => [
                    'days_since_last_login' => (int) $daysSinceLastLogin,
                    'lessons_completed' => (int) $lessonsCompleted,
                    'quiz_average_score' => (float) $quizAverage,
                    'comments_posted' => (int) $commentsCount,
                ],
                'timeout' => 5.0,
            ]);
 
            return $response->toArray();
        } catch (\Throwable $e) {
            $this->logger->error('Theos churn prediction failed', ['error' => $e->getMessage()]);
 
            // Fallback calculation matching python heuristics
            $score = 0.0;
            if ($daysSinceLastLogin > 30) {
                $score += 0.60;
            } elseif ($daysSinceLastLogin > 7) {
                $score += 0.25;
            }
            if ($quizAverage < 50.0) {
                $score += 0.30;
            } elseif ($quizAverage < 70.0) {
                $score += 0.15;
            }
            $score -= min(0.20, $lessonsCompleted * 0.02);
            $score -= min(0.15, $commentsCount * 0.03);
            $proba = max(0.0, min(1.0, $score));
 
            return [
                'churn_probability' => $proba,
                'at_risk' => $proba >= 0.50,
                'fallback' => true,
            ];
        }
    }
}
