<?php

namespace App\Controller;

use App\Repository\CohortRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CohortInvitationController extends AbstractController
{
    #[Route('/join/{token}', name: 'cohort_join')]
    public function join(string $token, CohortRepository $cohortRepository, Request $request): Response
    {
        $cohort = $cohortRepository->findOneBy(['invitationToken' => $token]);

        if (!$cohort) {
            $this->addFlash('danger', 'Lien d\'invitation invalide.');
            return $this->redirectToRoute('home');
        }

        // Store cohort token in session
        $request->getSession()->set('pending_cohort_token', $token);

        if ($this->getUser()) {
            return $this->redirectToRoute('cohort_complete_join');
        }

        $this->addFlash('info', 'Veuillez vous inscrire ou vous connecter pour rejoindre la promotion ' . $cohort->getTitle());
        return $this->redirectToRoute('register');
    }

    #[Route('/invitation/complete', name: 'cohort_complete_join')]
    public function completeJoin(CohortRepository $cohortRepository, Request $request, \Doctrine\ORM\EntityManagerInterface $entityManager): Response
    {
        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $token = $request->getSession()->get('pending_cohort_token');
        if (!$token) {
            return $this->redirectToRoute('home');
        }

        $cohort = $cohortRepository->findOneBy(['invitationToken' => $token]);
        if ($cohort) {
            if (!$cohort->getUsers()->contains($user)) {
                $cohort->addUser($user);
                $user->addCohort($cohort);
                
                // Save
                $entityManager->flush();
                
                $this->addFlash('success', 'Bienvenue dans la promotion ' . $cohort->getTitle() . ' !');
            } else {
                $this->addFlash('info', 'Vous faites déjà partie de cette promotion.');
            }
        }

        $request->getSession()->remove('pending_cohort_token');

        return $this->redirectToRoute('home');
    }
}
