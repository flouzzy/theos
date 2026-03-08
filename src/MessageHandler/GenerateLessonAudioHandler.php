<?php

namespace App\MessageHandler;

use App\Message\GenerateLessonAudioMessage;
use App\Repository\LessonRepository;
use App\Service\GeminiAudioService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Psr\Log\LoggerInterface;

#[AsMessageHandler]
class GenerateLessonAudioHandler
{
    public function __construct(
        private LessonRepository $lessonRepository,
        private GeminiAudioService $geminiAudioService,
        private LoggerInterface $logger
    ) {
    }

    public function __invoke(GenerateLessonAudioMessage $message): void
    {
        $lesson = $this->lessonRepository->find($message->getLessonId());

        if (!$lesson) {
            $this->logger->error('Leçon non trouvée lors de la génération audio: ' . $message->getLessonId());
            return;
        }

        try {
            $this->geminiAudioService->generateAudio(
                $lesson, 
                $message->getVoiceName(), 
                $message->getDirectorNotes()
            );
            $this->logger->info('Audio généré avec succès pour la leçon: ' . $lesson->getTitle());
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la génération audio Gemini: ' . $e->getMessage());
        }
    }
}
