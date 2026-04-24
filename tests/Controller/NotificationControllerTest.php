<?php

namespace App\Tests\Controller;

use App\Entity\Notification;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class NotificationControllerTest extends WebTestCase
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
        // Clean up database using a direct connection and disabling foreign keys constraint for SQLite
        $connection = $this->entityManager->getConnection();
        $connection->executeStatement('PRAGMA foreign_keys = OFF');
        $connection->executeStatement('DELETE FROM notification');
        $connection->executeStatement('DELETE FROM user');
        $connection->executeStatement('PRAGMA foreign_keys = ON');

        $this->entityManager->close();
        parent::tearDown();
    }

    private function createUser(string $email, string $password = 'password'): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setFirstname('Test');
        $user->setLastname('User');
        $user->setIsVerified(true);
        $user->setRoles(['ROLE_USER']);

        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        $user->setPassword($hasher->hashPassword($user, $password));

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    public function testIndexRequiresLogin(): void
    {
        $this->client->request('GET', '/notification/');

        $this->assertResponseRedirects('/login');
    }

    public function testIndexIsSuccessfulForAuthenticatedUser(): void
    {
        $user = $this->createUser('user1@example.com');
        $this->client->loginUser($user);

        // Create a notification for the user
        $notification = new Notification();
        $notification->setMessage('Test notification message');
        $notification->setUser($user);

        $this->entityManager->persist($notification);
        $this->entityManager->flush();

        $this->client->request('GET', '/notification/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'Test notification message');
    }

    public function testShowMarksNotificationAsRead(): void
    {
        $user = $this->createUser('user2@example.com');
        $this->client->loginUser($user);

        $notification = new Notification();
        $notification->setMessage('Unread notification');
        $notification->setUser($user);
        $notification->setIsRead(false);

        $this->entityManager->persist($notification);
        $this->entityManager->flush();

        $this->client->request('GET', '/notification/' . $notification->getId());

        $this->assertResponseIsSuccessful();

        // Clear EntityManager to test actual database state
        $this->entityManager->clear();

        // Check database
        $updatedNotification = $this->entityManager->getRepository(Notification::class)->find($notification->getId());
        $this->assertTrue($updatedNotification->isRead());
    }

    public function testShowRedirectsWhenLinkIsPresent(): void
    {
        $user = $this->createUser('user3@example.com');
        $this->client->loginUser($user);

        $notification = new Notification();
        $notification->setMessage('Notification with link');
        $notification->setUser($user);
        $notification->setLink('https://example.com/target-page');

        $this->entityManager->persist($notification);
        $this->entityManager->flush();

        $this->client->request('GET', '/notification/' . $notification->getId());

        $this->assertResponseRedirects('https://example.com/target-page');
    }

    public function testShowPreventsAccessingOthersNotification(): void
    {
        $userA = $this->createUser('usera@example.com');
        $userB = $this->createUser('userb@example.com');

        // Log in as user A
        $this->client->loginUser($userA);

        // Notification belongs to user B
        $notification = new Notification();
        $notification->setMessage('User B private notification');
        $notification->setUser($userB);

        $this->entityManager->persist($notification);
        $this->entityManager->flush();

        $this->client->request('GET', '/notification/' . $notification->getId());

        // Should redirect to index
        $this->assertResponseRedirects('/notification/');
    }

    public function testShowAllowsAccessingGeneralNotification(): void
    {
        $user = $this->createUser('user4@example.com');
        $this->client->loginUser($user);

        // Notification with no user targeted (general notification)
        $notification = new Notification();
        $notification->setMessage('General broadcast notification');
        $notification->setUser(null);

        $this->entityManager->persist($notification);
        $this->entityManager->flush();

        $this->client->request('GET', '/notification/' . $notification->getId());

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'General broadcast notification');
    }
}
