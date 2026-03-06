<?php

namespace App\Tests\Repository;

use App\Entity\Notification;
use App\Entity\User;
use App\Repository\NotificationRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

class NotificationRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private NotificationRepository $notificationRepository;
    private Security $security;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();

        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        $this->security = $this->createMock(Security::class);

        // Manually instantiate the repository and inject the mock Security service
        $registry = $kernel->getContainer()->get('doctrine');
        $this->notificationRepository = new NotificationRepository($registry, $this->security);

        // Clean up the database before each test
        $this->entityManager->createQuery('DELETE FROM App\Entity\Notification')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\User')->execute();
    }

    public function testFindAllUnreadReturnsUnreadNotificationsForUserOrNullUser(): void
    {
        // 1. Create a User
        $user1 = new User();
        $user1->setEmail('user1@example.com');
        $user1->setFirstname('User');
        $user1->setLastname('One');
        $user1->setPassword('password');
        $this->entityManager->persist($user1);

        $user2 = new User();
        $user2->setEmail('user2@example.com');
        $user2->setFirstname('User');
        $user2->setLastname('Two');
        $user2->setPassword('password');
        $this->entityManager->persist($user2);

        // 2. Create Notifications
        $date1 = new \DateTimeImmutable('-1 day');
        $date2 = new \DateTimeImmutable('-2 days');
        $date3 = new \DateTimeImmutable('-3 days');

        // Unread for user1 (Should be returned)
        $notif1 = new Notification();
        $notif1->setTitle('Unread User1');
        $notif1->setMessage('Test');
        $notif1->setUser($user1);
        $notif1->setIsRead(false);
        $notif1->setCreatedAt($date1);
        $this->entityManager->persist($notif1);

        // Read for user1 (Should NOT be returned)
        $notif2 = new Notification();
        $notif2->setTitle('Read User1');
        $notif2->setMessage('Test');
        $notif2->setUser($user1);
        $notif2->setIsRead(true);
        $notif2->setCreatedAt($date2);
        $this->entityManager->persist($notif2);

        // Unread with no user (Should be returned)
        $notif3 = new Notification();
        $notif3->setTitle('Unread Global');
        $notif3->setMessage('Test');
        $notif3->setUser(null);
        $notif3->setIsRead(false);
        $notif3->setCreatedAt($date3);
        $this->entityManager->persist($notif3);

        // Unread for user2 (Should NOT be returned for user1)
        $notif4 = new Notification();
        $notif4->setTitle('Unread User2');
        $notif4->setMessage('Test');
        $notif4->setUser($user2);
        $notif4->setIsRead(false);
        $notif4->setCreatedAt($date1);
        $this->entityManager->persist($notif4);

        $this->entityManager->flush();

        // 3. Mock Security to return user1
        $this->security->expects($this->once())
            ->method('getUser')
            ->willReturn($user1);

        // 4. Call the method
        $results = $this->notificationRepository->findAllUnread();

        // 5. Assertions
        $this->assertCount(2, $results);

        // Since it's ordered by createdAt DESC, notif1 should be first, then notif3
        $this->assertSame($notif1->getId(), $results[0]->getId());
        $this->assertSame($notif3->getId(), $results[1]->getId());
    }

    public function testFindAllUnreadWithNullUserReturnsOnlyGlobalNotifications(): void
    {
         // 1. Create a User
         $user1 = new User();
         $user1->setEmail('user1@example.com');
         $user1->setFirstname('User');
         $user1->setLastname('One');
         $user1->setPassword('password');
         $this->entityManager->persist($user1);

         // 2. Create Notifications
         // Unread for user1 (Should NOT be returned when user is null)
         $notif1 = new Notification();
         $notif1->setTitle('Unread User1');
         $notif1->setMessage('Test');
         $notif1->setUser($user1);
         $notif1->setIsRead(false);
         $notif1->setCreatedAt(new \DateTimeImmutable('-1 day'));
         $this->entityManager->persist($notif1);

         // Unread with no user (Should be returned)
         $notif2 = new Notification();
         $notif2->setTitle('Unread Global');
         $notif2->setMessage('Test');
         $notif2->setUser(null);
         $notif2->setIsRead(false);
         $notif2->setCreatedAt(new \DateTimeImmutable('-2 days'));
         $this->entityManager->persist($notif2);

         $this->entityManager->flush();

         // 3. Mock Security to return null (no logged in user)
         $this->security->expects($this->once())
             ->method('getUser')
             ->willReturn(null);

         // 4. Call the method
         $results = $this->notificationRepository->findAllUnread();

         // 5. Assertions
         $this->assertCount(1, $results);
         $this->assertSame($notif2->getId(), $results[0]->getId());
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
        // $this->entityManager = null; // avoid memory leaks
    }
}
