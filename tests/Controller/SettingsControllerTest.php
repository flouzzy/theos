<?php

namespace App\Tests\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SettingsControllerTest extends WebTestCase
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
        $this->entityManager->close();
        parent::tearDown();
    }

    private function createUser(string $email): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setPassword('password');
        $user->setFullname('Test User Settings');

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    public function testIndexAccessDeniedForAnonymousUser(): void
    {
        $this->client->request('GET', '/settings/');

        $this->assertResponseRedirects('/login');
    }

    public function testNotificationsAccessDeniedForAnonymousUser(): void
    {
        $this->client->request('GET', '/settings/notifications');

        $this->assertResponseRedirects('/login');
    }

    public function testIndexAccessGrantedForAuthenticatedUser(): void
    {
        $user = $this->createUser('settings_index@example.com');
        $this->client->loginUser($user);

        $this->client->request('GET', '/settings/');

        $this->assertResponseIsSuccessful();
    }

    public function testNotificationsAccessGrantedForAuthenticatedUser(): void
    {
        $user = $this->createUser('settings_notifications@example.com');
        $this->client->loginUser($user);

        $this->client->request('GET', '/settings/notifications');

        $this->assertResponseIsSuccessful();
    }
}
