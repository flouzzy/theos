<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/settings', name: 'settings_')]
#[IsGranted('IS_AUTHENTICATED')]
class SettingsController extends AbstractController
{
    #[Route('/', name: 'index')]
    public function index(): Response
    {
        return $this->render('settings/index.html.twig', [
            'user' => $this->getUser(),
        ]);
    }

    #[Route('/notifications', name: 'notifications')]
    public function notifications(): Response
    {
        // Logic to update notification preferences would go here
        return $this->render('settings/notifications.html.twig');
    }
}
