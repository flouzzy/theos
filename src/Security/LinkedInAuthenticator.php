<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class LinkedInAuthenticator extends OAuth2Authenticator
{
    use TargetPathTrait;

    public function __construct(
        private ClientRegistry $clientRegistry,
        private EntityManagerInterface $entityManager,
        private RouterInterface $router
    ) {
    }

    public function supports(Request $request): ?bool
    {
        return $request->attributes->get('_route') === 'connect_linkedin_check';
    }

    public function authenticate(Request $request): Passport
    {
        $client = $this->clientRegistry->getClient('linkedin');
        $accessToken = $this->fetchAccessToken($client);

        return new SelfValidatingPassport(
            new UserBadge($accessToken->getToken(), function () use ($accessToken, $client) {
                /** @var \League\OAuth2\Client\Provider\LinkedInResourceOwner $linkedinUser */
                $linkedinUser = $client->fetchUserFromToken($accessToken);

                $email = $linkedinUser->getEmail();

                // 1. Recheche par linkedinId
                $user = $this->entityManager->getRepository(User::class)->findOneBy(['linkedinId' => $linkedinUser->getId()]);

                if (!$user) {
                    // 2. Recherche par email
                    $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);

                    if (!$user) {
                        // 3. Création
                        $user = new User();
                        $user->setEmail($email);
                        $user->setFirstname($linkedinUser->getFirstName() ?? '');
                        $user->setLastname($linkedinUser->getLastName() ?? '');
                        // On n'a pas forcément getName() sur l'objet de base league pour LinkedIn, à vérifier selon la version
                        $user->setFullname($user->getFirstname() . ' ' . $user->getLastname());
                        $user->setIsVerified(true);
                        $user->setPassword('oauth_placeholder');
                        $this->entityManager->persist($user);
                    }

                    $user->setLinkedinId((string)$linkedinUser->getId());
                    $this->entityManager->flush();
                }

                return $user;
            })
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $targetPath = $this->getTargetPath($request->getSession(), $firewallName);

        if ($targetPath) {
            return new RedirectResponse($targetPath);
        }

        return new RedirectResponse($this->router->generate('home'));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $message = strtr($exception->getMessageKey(), $exception->getMessageData());

        $request->getSession()->getFlashBag()->add('error', $message);

        return new RedirectResponse($this->router->generate('login'));
    }
}
