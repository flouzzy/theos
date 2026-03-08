<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\Notification;
use App\Entity\User;
use App\Repository\NotificationRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class UserNotificationControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;
    private NotificationRepository $notificationRepository;
    private UserRepository $userRepository;
    private User $user;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get('doctrine')->getManager();
        $this->notificationRepository = $this->entityManager->getRepository(Notification::class);
        $this->userRepository = $this->entityManager->getRepository(User::class);

        // Clean up previous test data
        $this->cleanUp();

        // Create user
        $this->user = new User();
        $this->user->setEmail('test_notification_user@example.com');
        $this->user->setPassword('password');
        $this->user->setFirstname('Test');
        $this->user->setLastname('User');
        $this->entityManager->persist($this->user);
        $this->entityManager->flush();

        // Login
        $this->client->loginUser($this->user);
    }

    protected function tearDown(): void
    {
        $this->cleanUp();
        parent::tearDown();
    }

    private function cleanUp(): void
    {
        $user = $this->userRepository->findOneBy(['email' => 'test_notification_user@example.com']);
        if ($user) {
            // Delete notifications first
            $this->notificationRepository->createQueryBuilder('n')
                ->delete()
                ->where('n.user = :user')
                ->setParameter('user', $user)
                ->getQuery()
                ->execute();

            $this->entityManager->remove($user);
            $this->entityManager->flush();
        }
    }

}
