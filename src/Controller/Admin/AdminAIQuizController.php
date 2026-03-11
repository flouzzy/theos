<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Lesson;
use App\Service\QuizGeneratorService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

use App\Message\GenerateLessonQuizMessage;
use Symfony\Component\Messenger\MessageBusInterface;

#[Route('/admin/ai-quiz', name: 'admin_ai_quiz_')]
#[IsGranted('ROLE_ADMIN')]
class AdminAIQuizController extends AbstractController
{
    #[Route('/generate/{id}', name: 'generate', methods: ['POST'])]
    public function generate(Lesson $lesson, MessageBusInterface $bus): Response
    {
        $lessonId = $lesson->getId();
        if (null === $lessonId) {
            throw new \LogicException('Lesson must be persisted before generating quiz.');
        }
        $bus->dispatch(new GenerateLessonQuizMessage($lessonId));

        $this->addFlash('success', 'La génération du quiz a été lancée en tâche de fond. Il sera bientôt disponible.');

        return $this->redirectToRoute('admin_lesson_edit', ['id' => $lesson->getId()]);
    }
}
