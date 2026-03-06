<?php

declare(strict_types=1);

namespace App\Tests\Unit\MessageHandler;

use App\Entity\User;
use App\Message\Notification;
use App\MessageHandler\NotificationHandler;
use App\Repository\UserRepository;
use App\Service\NotificationService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Contracts\Translation\TranslatorInterface;

class NotificationHandlerTest extends TestCase
{
    private MockObject&UserRepository $userRepository;
    private MockObject&NotificationService $notificationService;
    private MockObject&TranslatorInterface $translator;
    private MockObject&Security $security;
    private NotificationHandler $handler;

    protected function setUp(): void
    {
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->notificationService = $this->createMock(NotificationService::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->security = $this->createMock(Security::class);

        $this->handler = new NotificationHandler(
            $this->userRepository,
            $this->notificationService,
            $this->translator,
            $this->security
        );
    }

    public function testInvokeSendsNotificationsToOtherVerifiedUsers(): void
    {
        $user1 = $this->createMock(User::class);
        $user2 = $this->createMock(User::class);
        $currentUser = $this->createMock(User::class);

        $notification = new Notification('Test Content', 'Test Title');

        $this->userRepository->expects($this->once())
            ->method('findVerifiedUsersIterator')
            ->willReturn([$user1, $user2, $currentUser]);

        $this->security->method('getUser')->willReturn($currentUser);

        $this->translator->expects($this->exactly(2))
            ->method('trans')
            ->with('Test Title')
            ->willReturn('Translated Title');

        $capturedUsers = [];
        $this->notificationService->expects($this->exactly(2))
            ->method('createNotification')
            ->willReturnCallback(function ($content, $title, $user, $flush) use (&$capturedUsers) {
                $this->assertEquals('Test Content', $content);
                $this->assertEquals('Translated Title', $title);
                $this->assertFalse($flush);
                $capturedUsers[] = $user;
                return $this->createMock(\App\Entity\Notification::class);
            });

        $this->notificationService->expects($this->once())
            ->method('flush');

        ($this->handler)($notification);

        $this->assertCount(2, $capturedUsers);
        $this->assertContains($user1, $capturedUsers);
        $this->assertContains($user2, $capturedUsers);
        $this->assertNotContains($currentUser, $capturedUsers);
    }

    public function testInvokeSendsToAllIfNoCurrentUser(): void
    {
        $user1 = $this->createMock(User::class);
        $user2 = $this->createMock(User::class);

        $notification = new Notification('Content', 'Title');

        $this->userRepository->method('findVerifiedUsersIterator')
            ->willReturn([$user1, $user2]);

        $this->security->method('getUser')->willReturn(null);

        $this->translator->expects($this->exactly(2))
            ->method('trans')
            ->willReturn('Translated Title');

        $this->notificationService->expects($this->exactly(2))
            ->method('createNotification');

        $this->notificationService->expects($this->once())
            ->method('flush');

        ($this->handler)($notification);
    }

    public function testInvokeWithNoUsers(): void
    {
        $notification = new Notification('Content', 'Title');

        $this->userRepository->method('findVerifiedUsersIterator')
            ->willReturn([]);

        $this->notificationService->expects($this->never())
            ->method('createNotification');

        $this->notificationService->expects($this->once())
            ->method('flush');

        ($this->handler)($notification);
    }
}
