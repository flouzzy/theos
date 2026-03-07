<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/coach', name: 'coach_')]
#[IsGranted('IS_AUTHENTICATED')]
class CoachController extends AbstractController
{
    public function __construct(
        #[\Symfony\Component\DependencyInjection\Attribute\Autowire(env: 'bool:COACH_ENABLED')]
        private bool $coachEnabled
    ) {}

    #[Route('/', name: 'index')]
    public function index(): Response
    {
        if (!$this->coachEnabled) {
            throw $this->createNotFoundException('Coach is not enabled');
        }

        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        return $this->render('coach/index.html.twig', [
            'streak' => $user->getStreak(),
            'xp' => $user->getXp(),
            'badgesCount' => count($user->getBadges()),
        ]);
    }
    #[Route('/chat', name: 'chat', methods: ['POST'])]
    public function chat(\Symfony\Component\HttpFoundation\Request $request, \App\Service\CoachAIAgent $agent): \Symfony\Component\HttpFoundation\JsonResponse
    {
        if (!$this->coachEnabled) {
            return $this->json(['error' => 'Coach is not enabled'], 403);
        }

        $data = json_decode($request->getContent(), true);
        $message = $data['message'] ?? '';
        $history = $data['history'] ?? [];

        if (empty($message)) {
            return $this->json(['error' => 'Message is empty'], 400);
        }

        $reply = $agent->generateResponse($history, $message);

        return $this->json(['reply' => $reply]);
    }
}
