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
    #[Route('/', name: 'index')]
    public function index(): Response
    {
        // Gamification mock data
        $streak = 14; // Mock value
        $xp = 1250; // Mock value
        $level = 5; // Mock value
        $nextLevelXp = 2000;

        return $this->render('coach/index.html.twig', [
            'streak' => $streak,
            'xp' => $xp,
            'level' => $level,
            'nextLevelXp' => $nextLevelXp,
        ]);
    }
}
