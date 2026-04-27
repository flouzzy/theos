<?php

namespace App\Tests\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ProfileControllerTest extends WebTestCase
{
    private $client;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = $this->client->getContainer()->get('doctrine')->getManager();
    }

    protected function tearDown(): void
    {
        // Clean up test users
        $users = $this->entityManager->getRepository(User::class)->findBy(['email' => 'test_profile_user@example.com']);
        foreach ($users as $user) {
            $this->entityManager->remove($user);
        }
        $this->entityManager->flush();

        $this->entityManager->close();
        parent::tearDown();
    }

    private function createTestUser(): User
    {
        $user = new User();
        $user->setEmail('test_profile_user@example.com');
        $user->setPassword('password');
        $user->setFirstname('Test');
        $user->setLastname('User');
        $user->setRoles(['ROLE_USER']);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    public function testProfileIndexRedirectsWhenUnauthenticated(): void
    {
        $this->client->request('GET', '/profile');
        $this->assertResponseRedirects();
        // Usually redirects to login, let's just ensure it's a redirect to avoid being brittle on exact login path
    }

    public function testProfileEditRedirectsWhenUnauthenticated(): void
    {
        $this->client->request('GET', '/profile/edit');
        $this->assertResponseRedirects();
    }

    public function testProfileIndexIsSuccessfulWhenAuthenticated(): void
    {
        $user = $this->createTestUser();
        $this->client->loginUser($user);

        $this->client->request('GET', '/profile');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Mon profil'); // Assuming it's in French based on twigs
    }

    public function testProfileEditIsSuccessfulWhenAuthenticated(): void
    {
        $user = $this->createTestUser();
        $this->client->loginUser($user);

        $this->client->request('GET', '/profile/edit');

        $this->assertResponseIsSuccessful();
        // Look for something that proves the form rendered
        $this->assertSelectorExists('form[name="user"]');
    }
}
