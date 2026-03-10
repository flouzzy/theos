<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Team;
use App\Entity\User;
use App\Repository\TeamRepository;
use App\Service\CompletionCalculator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/team', name: 'team_')]
class TeamController extends AbstractController
{
    #[Route('/dashboard', name: 'dashboard')]
    public function dashboard(TeamRepository $teamRepository, CompletionCalculator $completionCalculator): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // Récupérer les équipes possédées par le manager
        $teams = $teamRepository->findBy(['owner' => $user]);

        $teamsData = [];
        foreach ($teams as $team) {
            $membersData = [];
            foreach ($team->getMembers() as $member) {
                $membersData[] = [
                    'user' => $member,
                    'progress' => $completionCalculator->calculateGlobalProgress($member),
                ];
            }
            $teamsData[] = [
                'team' => $team,
                'members' => $membersData,
            ];
        }

        return $this->render('team/dashboard.html.twig', [
            'teamsData' => $teamsData,
        ]);
    }

    #[Route('/{id}/add-member', name: 'add_member', methods: ['POST'])]
    public function addMember(Team $team, Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('EDIT', $team);

        $email = $request->request->get('email');
        if ($email) {
            $user = $entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
            if ($user) {
                $team->addMember($user);
                $entityManager->flush();
                $this->addFlash('success', sprintf('Utilisateur %s ajouté à l\'équipe.', $email));
            } else {
                $this->addFlash('error', sprintf('Utilisateur %s non trouvé.', $email));
            }
        }

        return $this->redirectToRoute('team_dashboard');
    }

    #[Route('/{id}/remove-member/{userId}', name: 'remove_member')]
    public function removeMember(Team $team, int $userId, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('EDIT', $team);

        $user = $entityManager->getRepository(User::class)->find($userId);
        if ($user && $team->getMembers()->contains($user)) {
            $team->removeMember($user);
            $entityManager->flush();
            $this->addFlash('success', 'Utilisateur retiré de l\'équipe.');
        }

        return $this->redirectToRoute('team_dashboard');
    }
}
