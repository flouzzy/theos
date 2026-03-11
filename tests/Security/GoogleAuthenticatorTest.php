<?php

declare(strict_types=1);

namespace App\Tests\Security;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Security\GoogleAuthenticator;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Client\Provider\GoogleClient;
use League\OAuth2\Client\Provider\GoogleUser;
use League\OAuth2\Client\Token\AccessToken;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class GoogleAuthenticatorTest extends TestCase
{
    public function testAuthenticate(): void
    {
        $clientRegistry = $this->createMock(ClientRegistry::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $router = $this->createMock(RouterInterface::class);

        $googleClient = $this->createMock(GoogleClient::class);
        $clientRegistry->method('getClient')->with('google')->willReturn($googleClient);

        $accessToken = new AccessToken(['access_token' => 'fake_token']);
        
        $googleAuthenticator = new class($clientRegistry, $entityManager, $router) extends GoogleAuthenticator {
            public function fetchAccessToken(\KnpU\OAuth2ClientBundle\Client\OAuth2ClientInterface $client, array $options = []): AccessToken
            {
                return new AccessToken(['access_token' => 'fake_token']);
            }
        };

        $googleUser = $this->createMock(GoogleUser::class);
        $googleUser->method('getEmail')->willReturn('test@example.com');
        $googleUser->method('getId')->willReturn('12345');
        $googleUser->method('getFirstName')->willReturn('Test');
        $googleUser->method('getLastName')->willReturn('User');

        $googleClient->method('fetchUserFromToken')->willReturn($googleUser);

        $userRepository = $this->createMock(UserRepository::class);
        $entityManager->method('getRepository')->with(User::class)->willReturn($userRepository);

        // Case 1: User exists by googleId
        $user = new User();
        $userRepository->method('findOneBy')->willReturnMap([
            [['googleId' => '12345'], null, $user],
        ]);

        $request = new Request();
        $passport = $googleAuthenticator->authenticate($request);

        $this->assertInstanceOf(SelfValidatingPassport::class, $passport);
        $this->assertSame($user, $passport->getUser());
    }
}
