<?php

namespace App\Controller;

use App\Service\CoachDataService;
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
        private bool $coachEnabled,
        private readonly CoachDataService $coachData,
    ) {}

    #[Route('/', name: 'index')]
    public function index(): Response
    {
        if (!$this->coachEnabled) {
            throw $this->createNotFoundException('Coach is not enabled');
        }

        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        $weeklyXp = $this->coachData->getWeeklyXpData($user);
        $nextLesson = $this->coachData->getNextLesson($user);
        $reviewLesson = $this->coachData->getLastCompletedLesson($user);
        $streakInfo = $this->coachData->getStreakInfo($user);

        return $this->render('coach/index.html.twig', [
            'streak' => $user->getStreak(),
            'xp' => $user->getXp(),
            'badgesCount' => count($user->getBadges()),
            'weeklyXp' => $weeklyXp,
            'weeklyXpTotal' => array_sum($weeklyXp),
            'nextLesson' => $nextLesson,
            'reviewLesson' => $reviewLesson,
            'streakInfo' => $streakInfo,
        ]);
    }
    #[Route('/chat', name: 'chat', methods: ['POST'])]
    public function chat(\Symfony\Component\HttpFoundation\Request $request, \App\Service\CoachAIAgent $agent): \Symfony\Component\HttpFoundation\JsonResponse
    {
        if (!$this->coachEnabled) {
            return $this->json(['error' => 'Coach is not enabled'], 403);
        }

        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        $data = json_decode($request->getContent(), true);
        $message = $data['message'] ?? '';

        if (empty($message)) {
            return $this->json(['error' => 'Message is empty'], 400);
        }

        $reply = $agent->generateResponse($user, $message);

        return $this->json(['reply' => $reply]);
    }

    #[Route('/history', name: 'history', methods: ['GET'])]
    public function history(\App\Service\CoachAIAgent $agent): \Symfony\Component\HttpFoundation\JsonResponse
    {
        if (!$this->coachEnabled) {
            return $this->json(['error' => 'Coach is not enabled'], 403);
        }

        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        return $this->json([
            'history' => $agent->getHistory($user)
        ]);
    }
}
