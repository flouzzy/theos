<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Lesson;
use App\Repository\LessonRepository;
use GuzzleHttp\Client;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class RecommendationService
{
    private const EMBEDDING_MODEL = 'embedding-001';
    private const API_URL = 'https://generativelanguage.googleapis.com/v1beta/models/' . self::EMBEDDING_MODEL . ':embedContent';

    public function __construct(
        #[Autowire(env: 'GEMINI_API_KEY')]
        private string $apiKey,
        private LessonRepository $lessonRepository,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger,
        private ?Client $client = null
    ) {
        $this->client = $client ?? new Client();
    }

    /**
     * Calcule et stocke l'embedding pour une leçon.
     */
    public function updateLessonEmbedding(Lesson $lesson): void
    {
        if ($this->apiKey === 'test') {
            return;
        }

        $text = $lesson->getTitle() . "\n\n" . $lesson->getDescription() . "\n\n" . strip_tags($lesson->getContent() ?? '');
        
        $payload = [
            'model' => 'models/' . self::EMBEDDING_MODEL,
            'content' => [
                'parts' => [
                    ['text' => $text]
                ]
            ]
        ];

        try {
            $response = $this->client->post(self::API_URL . '?key=' . $this->apiKey, [
                'json' => $payload
            ]);

            $result = json_decode($response->getBody()->getContents(), true);
            $embedding = $result['embedding']['values'] ?? null;

            if ($embedding) {
                $lesson->setEmbeddings($embedding);
                $this->entityManager->flush();
            }
        } catch (\Exception $e) {
            $this->logger->error("Erreur lors de la génération de l'embedding : " . $e->getMessage());
        }
    }

    /**
     * Trouve les leçons les plus similaires à une leçon donnée.
     * 
     * @param Lesson $lesson
     * @param int $limit
     * @return Lesson[]
     */
    public function getRecommendations(Lesson $lesson, int $limit = 3): array
    {
        $targetEmbedding = $lesson->getEmbeddings();
        
        if (!$targetEmbedding) {
            $this->updateLessonEmbedding($lesson);
            $targetEmbedding = $lesson->getEmbeddings();
        }

        if (!$targetEmbedding) {
            return [];
        }

        $allLessons = $this->lessonRepository->findAll();
        $similarities = [];

        $targetNormSq = 0;
        foreach ($targetEmbedding as $val) {
            $targetNormSq += $val * $val;
        }

        foreach ($allLessons as $otherLesson) {
            if ($otherLesson->getId() === $lesson->getId()) {
                continue;
            }

            $otherEmbedding = $otherLesson->getEmbeddings();
            if (!$otherEmbedding) {
                continue;
            }

            $similarity = $this->cosineSimilarity($targetEmbedding, $otherEmbedding, $targetNormSq);
            $similarities[] = [
                'lesson' => $otherLesson,
                'similarity' => $similarity
            ];
        }

        usort($similarities, fn($a, $b) => $b['similarity'] <=> $a['similarity']);

        return array_map(fn($item) => $item['lesson'], array_slice($similarities, 0, $limit));
    }

    /**
     * Calcule la similarité cosinus entre deux vecteurs.
     */
    private function cosineSimilarity(array $vec1, array $vec2, float $normA): float
    {
        $dotProduct = 0;
        $normB = 0;

        foreach ($vec1 as $i => $val) {
            $v2 = $vec2[$i] ?? 0;
            $dotProduct += $val * $v2;
            $normB += $v2 * $v2;
        }

        if ($normA == 0 || $normB == 0) {
            return 0;
        }

        return $dotProduct / (sqrt($normA) * sqrt($normB));
    }
}
