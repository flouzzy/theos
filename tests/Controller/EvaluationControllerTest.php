<?php

namespace App\Tests\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class EvaluationControllerTest extends WebTestCase
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
        $user->setEmail('test_evaluation_' . uniqid() . '@example.com');
        $user->setPassword('password');
        $user->setFirstname('Test');
        $user->setLastname('Evaluation');
        $user->setRoles(['ROLE_USER']);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // Simuler la connexion
        $this->client->loginUser($user);

        // Accéder à la route
        $this->client->request('GET', '/evaluation/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('title', 'Mes Évaluations');

        // Clean up
        $this->entityManager->remove($user);
        $this->entityManager->flush();
    }

    public function testIndexRedirectsUnauthenticatedUser(): void
    {
        $this->client->request('GET', '/evaluation/');
        $this->assertResponseRedirects('/login');
    }
}
