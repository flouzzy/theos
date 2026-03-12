<?php

namespace App\Tests\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class LeaderboardControllerTest extends WebTestCase
{
    private $client;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = $this->client->getContainer()->get('doctrine')->getManager();
    }

    public function testIndexIsSuccessfulForAuthenticatedUser(): void
    {
        // Créer un utilisateur de test
        $user = new User();
        $user->setEmail('test_leaderboard_' . uniqid() . '@example.com');
        $user->setPassword('password');
        $user->setFirstname('Test');
        $user->setLastname('Leaderboard');
        $user->setRoles(['ROLE_USER']);
        $user->setXp(100);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // Simuler la connexion
        $this->client->loginUser($user);

        // Accéder à la route
        $this->client->request('GET', '/leaderboard');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Classement global');
        
        // Clean up
        $this->entityManager->remove($user);
        $this->entityManager->flush();
    }

    public function testIndexRedirectsUnauthenticatedUser(): void
    {
        $this->client->request('GET', '/leaderboard');
        $this->assertResponseRedirects('/login');
    }
}
