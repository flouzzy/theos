<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/leaderboard', name: 'leaderboard_')]
#[IsGranted('IS_AUTHENTICATED')]
class LeaderboardController extends AbstractController
{
    #[Route('', name: 'index')]
    public function index(UserRepository $userRepository): Response
    {
        $topUsers = $userRepository->findTopUsersByXp(50);

        return $this->render('leaderboard/index.html.twig', [
            'topUsers' => $topUsers,
        ]);
    }
}
