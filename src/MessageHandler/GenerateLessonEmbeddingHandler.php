<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\GenerateLessonEmbeddingMessage;
use App\Repository\LessonRepository;
use App\Service\RecommendationService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class GenerateLessonEmbeddingHandler
{
    public function __construct(
        private LessonRepository $lessonRepository,
        private RecommendationService $recommendationService,
    ) {
    }

    public function __invoke(GenerateLessonEmbeddingMessage $message): void
    {
        $lesson = $this->lessonRepository->find($message->getLessonId());

        if (!$lesson) {
            return;
        }

        $this->recommendationService->updateLessonEmbedding($lesson);
    }
}
