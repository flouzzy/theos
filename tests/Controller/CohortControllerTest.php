<?php

namespace App\Tests\Controller;

use App\Entity\Cohort;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CohortControllerTest extends WebTestCase
{
    private $client;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get('doctrine')->getManager();

        // Bypass SQLite foreign keys issue if needed
        $conn = $this->entityManager->getConnection();
        if ($conn->getDatabasePlatform()->getName() === 'sqlite') {
            $conn->executeStatement('PRAGMA foreign_keys = OFF');
        }

        // Clean up
        $this->entityManager->createQuery('DELETE FROM App\Entity\Cohort')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\User')->execute();

        if ($conn->getDatabasePlatform()->getName() === 'sqlite') {
            $conn->executeStatement('PRAGMA foreign_keys = ON');
        }
    }

    private function createUser(string $email, string $password, array $roles = []): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setPassword($password); // We don't need real hash for simple login in tests usually, but let's see. WebTestCase loginUser doesn't check password.
        $user->setFirstname('Test');
        $user->setLastname('User');
        $user->setRoles($roles);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    private function createCohort(string $title, string $slug): Cohort
    {
        $cohort = new Cohort();
        $cohort->setTitle($title);
        $cohort->setSlug($slug);
        $cohort->setYear((int)(new \DateTime('now'))->format('Y'));

        $this->entityManager->persist($cohort);
        $this->entityManager->flush();

        return $cohort;
    }

    public function testIndexRedirectsUnauthenticatedUser(): void
    {
        $this->client->request('GET', '/cohort/');
        $this->assertResponseRedirects('/login');
    }

    public function testIndexIsSuccessfulForAuthenticatedUser(): void
    {
        $user = $this->createUser('cohort_index@example.com', 'password');

        $cohort = $this->createCohort('Test Cohort Index', 'test-cohort-index');
        $user->addCohort($cohort);
        $this->entityManager->flush();

        $this->client->loginUser($user);
        $this->client->request('GET', '/cohort/');

        $this->assertResponseIsSuccessful();
    }

    public function testShowCohortIsSuccessful(): void
    {
        $user = $this->createUser('cohort_show@example.com', 'password');
        $cohort = $this->createCohort('Test Cohort Show', 'test-cohort-show');
        $user->addCohort($cohort);
        $this->entityManager->flush();

        $this->client->loginUser($user);
        // CohortController::show requires slug
        $this->client->request('GET', '/cohort/' . $cohort->getSlug());

        $this->assertResponseIsSuccessful();
    }

    public function testSwitchCohort(): void
    {
        $user = $this->createUser('cohort_switch@example.com', 'password');

        $cohort1 = $this->createCohort('Cohort 1', 'cohort-1');
        $cohort2 = $this->createCohort('Cohort 2', 'cohort-2');

        $user->addCohort($cohort1);
        $user->addCohort($cohort2);

        $this->entityManager->flush();

        $this->client->loginUser($user);

        // Request the switch endpoint
        $this->client->request('GET', '/cohort/switch/' . $cohort2->getId());

        // Assert it redirects to cohort index
        $this->assertResponseRedirects('/cohort/');
        $this->client->followRedirect();
        $this->assertResponseIsSuccessful();
    }

    public function testChatCohortIsSuccessful(): void
    {
        $user = $this->createUser('cohort_chat@example.com', 'password');
        $cohort = $this->createCohort('Test Cohort Chat', 'test-cohort-chat');
        $user->addCohort($cohort);
        $this->entityManager->flush();

        $this->client->loginUser($user);
        $this->client->request('GET', '/cohort/' . $cohort->getId() . '/chat');

        $this->assertResponseIsSuccessful();
    }
}
