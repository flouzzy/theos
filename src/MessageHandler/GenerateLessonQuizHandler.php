<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\GenerateLessonQuizMessage;
use App\Repository\LessonRepository;
use App\Service\QuizGeneratorService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class GenerateLessonQuizHandler
{
    public function __construct(
        private LessonRepository $lessonRepository,
        private QuizGeneratorService $quizGenerator,
    ) {
    }

    public function __invoke(GenerateLessonQuizMessage $message): void
    {
        $lesson = $this->lessonRepository->find($message->getLessonId());

        if (!$lesson) {
            return;
        }

        $this->quizGenerator->generateQuiz($lesson);
    }
}
