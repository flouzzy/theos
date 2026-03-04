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
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        return $this->render('coach/index.html.twig', [
            'streak' => $user->getStreak(),
            'xp' => $user->getXp(),
            'badgesCount' => count($user->getBadges()),
        ]);
    }
}
