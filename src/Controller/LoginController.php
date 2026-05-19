<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\SendMail;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\FormLoginAuthenticator;

class LoginController extends AbstractController
{
    #[Route('/login/magic-request', name: 'request_magic_link', methods: ['POST'])]
    public function requestMagicLink(Request $request, EntityManagerInterface $entityManager, SendMail $sendMail, string $defaultFromEmail): Response
    {
        $email = $request->getPayload()->getString('email');
        $user = $entityManager->getRepository(User::class)->findOneBy(['email' => $email]);

        if ($user) {
            $token = bin2hex(random_bytes(32));
            $user->setLoginToken($token);
            $user->setLoginTokenExpiresAt(new \DateTimeImmutable('+15 minutes'));
            $entityManager->flush();

            $url = $this->generateUrl('login_magic_link', ['token' => $token], UrlGeneratorInterface::ABSOLUTE_URL);

            $sendMail->send(
                $defaultFromEmail,
                $email,
                'Lien de connexion magique',
                'emails/magic_link.html.twig',
                ['url' => $url, 'user' => $user]
            );
        }

        $this->addFlash('success', 'Si votre email existe, un lien de connexion vous a été envoyé.');
        return $this->redirectToRoute('login');
    }

    #[Route('/login/magic/{token}', name: 'login_magic_link')]
    public function loginMagicLink(
        string $token, 
        EntityManagerInterface $entityManager, 
        UserAuthenticatorInterface $authenticator,
        FormLoginAuthenticator $formAuthenticator,
        Request $request
    ): Response {
        $user = $entityManager->getRepository(User::class)->findOneBy(['loginToken' => $token]);

        if (!$user || !$user->isLoginTokenValid()) {
            $this->addFlash('error', 'Lien invalide ou expiré.');
            return $this->redirectToRoute('login');
        }

        // Consume token
        $user->setLoginToken(null);
        $user->setLoginTokenExpiresAt(null);
        $entityManager->flush();

        return $authenticator->authenticateUser(
            $user,
            $formAuthenticator,
            $request
        );
    }

    #[Route('/login', name: 'login')]
    public function index(AuthenticationUtils $authenticationUtils): Response
    {
        // Connecté ?
        if ($this->getUser()) {
            // Redirection vers la home
            return $this->redirectToRoute('home');
        }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('login/index.html.twig', [
            'last_username' => $lastUsername,
            'error'         => $error,
        ]);
    }
}
