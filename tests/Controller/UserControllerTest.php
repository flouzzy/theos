<?php

namespace App\Tests\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class UserControllerTest extends WebTestCase
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
        $connection = $this->entityManager->getConnection();
        $connection->executeStatement('PRAGMA foreign_keys = OFF');

        $user = $this->entityManager->getRepository(User::class)->findOneBy(['username' => 'test-user-profile-slug']);
        if ($user) {
            $this->entityManager->remove($user);
            $this->entityManager->flush();
        }

        $connection->executeStatement('PRAGMA foreign_keys = ON');

        $this->entityManager->close();
        parent::tearDown();
    }

    public function testUserProfileIsSuccessful(): void
    {
        // Créer un utilisateur de test
        $user = new User();
        $user->setEmail('test-profile@example.com');
        $user->setUsername('test-user-profile-slug');
        $user->setPassword('password'); // Note: normally we would hash this, but we don't login for this test
        $user->setFullname('Test User');
        $user->setRoles(['ROLE_USER']);
        // Need to set isVerified and other not null defaults possibly
        $user->setIsVerified(true);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // Accéder à la route
        $this->client->request('GET', '/user/test-user-profile-slug');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Test User');
    }

    public function testUserProfileNotFound(): void
    {
        $this->client->request('GET', '/user/non-existent-user-profile-slug');
        $this->assertResponseStatusCodeSame(404);
    }
}
