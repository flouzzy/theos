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

#[Route('/admin/ai-quiz', name: 'admin_ai_quiz_')]
#[IsGranted('ROLE_ADMIN')]
class AdminAIQuizController extends AbstractController
{
    #[Route('/generate/{id}', name: 'generate', methods: ['POST'])]
    public function generate(Lesson $lesson, QuizGeneratorService $quizGenerator): Response
    {
        try {
            $quiz = $quizGenerator->generateQuiz($lesson);
            $this->addFlash('success', sprintf('Quiz "%s" généré avec succès par l\'IA.', $quiz->getTitle()));
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de la génération du quiz : ' . $e->getMessage());
        }

        return $this->redirectToRoute('admin_lesson_edit', ['id' => $lesson->getId()]);
    }
}
