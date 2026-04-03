<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Repository\TriviaQuestionRepository;
use App\Service\GamificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api', name: 'api_')]
#[IsGranted('IS_AUTHENTICATED')]
class TriviaController extends AbstractController
{
    #[Route('/trivia', name: 'trivia', methods: ['GET'])]
    public function getTrivia(TriviaQuestionRepository $repo): JsonResponse
    {
        $q = $repo->findRandom();
        if (!$q) return $this->json(['error' => 'No question'], Response::HTTP_NOT_FOUND);
        return $this->json(['id' => $q->getId(), 'q' => $q->getQuestion(), 'o' => $q->getOptions()]);
    }

    #[Route('/trivia/check', name: 'trivia_check', methods: ['POST'])]
    public function check(
        Request $request,
        TriviaQuestionRepository $repo,
        GamificationService $gam,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (!$this->isCsrfTokenValid('trivia_check', $data['_token'] ?? '')) {
            return $this->json(['error' => 'Invalid CSRF token'], Response::HTTP_FORBIDDEN);
        }

        $q = $repo->find($data['id'] ?? 0);
        
        if (!$q) return $this->json(['error' => 'Invalid question'], Response::HTTP_BAD_REQUEST);

        $user = $this->getUser();
        if ($q->getCorrectAnswer() === ($data['answer'] ?? '')) {
            $user->setQuizCombo($user->getQuizCombo() + 1);
            $multiplier = 1 + (int)($user->getQuizCombo() / 5) * 0.1; // +10% tous les 5
            $gam->addXp($user, (int)(20 * $multiplier), 'trivia_win');
            $entityManager->flush();
            return $this->json(['correct' => true, 'combo' => $user->getQuizCombo()]);
        }
        $user->setQuizCombo(0);
        $entityManager->flush();
        return $this->json(['correct' => false]);
    }
}
