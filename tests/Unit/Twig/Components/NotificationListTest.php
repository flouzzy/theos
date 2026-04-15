<?php

namespace App\Tests\Unit\Twig\Components;

use App\Entity\Notification;
use App\Entity\User;
use App\Repository\NotificationRepository;
use App\Twig\Components\NotificationList;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;

class NotificationListTest extends TestCase
{
    private NotificationRepository&MockObject $notificationRepository;
    private Security&MockObject $security;
    private EntityManagerInterface&MockObject $entityManager;
    private NotificationList $component;

    protected function setUp(): void
    {
        $this->notificationRepository = $this->createMock(NotificationRepository::class);
        $this->security = $this->createMock(Security::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);

        $this->component = new NotificationList(
            $this->notificationRepository,
            $this->security,
            $this->entityManager
        );
    }

    public function testMarkAsReadReturnsNullWhenNotificationNotFound(): void
    {
        $this->notificationRepository->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn(null);

        $this->assertNull($this->component->markAsRead(1));
    }

    public function testMarkAsReadReturnsNullWhenNotificationBelongsToDifferentUser(): void
    {
        $notificationUser = $this->createMock(User::class);
        $currentUser = $this->createMock(User::class);

        $notification = $this->createMock(Notification::class);
        $notification->method('getUser')->willReturn($notificationUser);

        $this->security->method('getUser')->willReturn($currentUser);

        $this->notificationRepository->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($notification);

        $this->entityManager->expects($this->never())->method('flush');

        $this->assertNull($this->component->markAsRead(1));
    }

    public function testMarkAsReadMarksGlobalNotificationAsRead(): void
    {
        $currentUser = $this->createMock(User::class);

        $notification = $this->createMock(Notification::class);
        $notification->method('getUser')->willReturn(null);
        $notification->method('getLink')->willReturn(null);

        $notification->expects($this->once())
            ->method('setIsRead')
            ->with(true);

        $this->security->method('getUser')->willReturn($currentUser);

        $this->notificationRepository->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($notification);

        $this->entityManager->expects($this->once())->method('flush');

        $this->assertNull($this->component->markAsRead(1));
    }

    public function testMarkAsReadMarksOwnNotificationAsRead(): void
    {
        $user = $this->createMock(User::class);

        $notification = $this->createMock(Notification::class);
        // Using exactly to allow two calls for user check
        $notification->method('getUser')->willReturn($user);
        $notification->method('getLink')->willReturn(null);

        $notification->expects($this->once())
            ->method('setIsRead')
            ->with(true);

        $this->security->method('getUser')->willReturn($user);

        $this->notificationRepository->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($notification);

        $this->entityManager->expects($this->once())->method('flush');

        $this->assertNull($this->component->markAsRead(1));
    }

    public function testMarkAsReadReturnsRedirectResponseWhenLinkExists(): void
    {
        $user = $this->createMock(User::class);
        $link = 'https://example.com/notification/1';

        $notification = $this->createMock(Notification::class);
        $notification->method('getUser')->willReturn($user);
        $notification->method('getLink')->willReturn($link);

        $notification->expects($this->once())
            ->method('setIsRead')
            ->with(true);

        $this->security->method('getUser')->willReturn($user);

        $this->notificationRepository->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($notification);

        $this->entityManager->expects($this->once())->method('flush');

        $response = $this->component->markAsRead(1);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame($link, $response->getTargetUrl());
    }
}
