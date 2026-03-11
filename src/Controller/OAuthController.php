<?php

declare(strict_types=1);

namespace App\Controller;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class OAuthController extends AbstractController
{
    #[Route('/connect/google', name: 'connect_google_start')]
    public function connectGoogle(ClientRegistry $clientRegistry): Response
    {
        return $clientRegistry
            ->getClient('google')
            ->redirect([
                'email', 'profile'
            ]);
    }

    #[Route('/connect/google/check', name: 'connect_google_check')]
    public function connectGoogleCheck(): void
    {
        // Intercepted by GoogleAuthenticator
    }

    #[Route('/connect/linkedin', name: 'connect_linkedin_start')]
    public function connectLinkedin(ClientRegistry $clientRegistry): Response
    {
        return $clientRegistry
            ->getClient('linkedin')
            ->redirect([
                'openid', 'profile', 'email'
            ]);
    }

    #[Route('/connect/linkedin/check', name: 'connect_linkedin_check')]
    public function connectLinkedinCheck(): void
    {
        // Intercepted by LinkedInAuthenticator
    }
}
