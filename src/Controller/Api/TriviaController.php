<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Repository\TriviaQuestionRepository;
use App\Service\GamificationService;
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
    public function check(Request $request, TriviaQuestionRepository $repo, GamificationService $gam): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $q = $repo->find($data['id'] ?? 0);
        
        if (!$q) return $this->json(['error' => 'Invalid question'], Response::HTTP_BAD_REQUEST);

        if ($q->getCorrectAnswer() === ($data['answer'] ?? '')) {
            $gam->addXp($this->getUser(), 20, 'trivia_win');
            return $this->json(['correct' => true]);
        }
        return $this->json(['correct' => false]);
    }
}
