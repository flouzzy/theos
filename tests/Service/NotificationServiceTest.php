<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Notification;
use App\Entity\User;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class NotificationServiceTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private MailerInterface $mailer;
    private UrlGeneratorInterface $router;
    private LoggerInterface $logger;
    private NotificationService $notificationService;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->mailer = $this->createMock(MailerInterface::class);
        $this->router = $this->createMock(UrlGeneratorInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->notificationService = new NotificationService(
            $this->entityManager,
            $this->mailer,
            $this->router,
            $this->logger,
            'sender@example.com',
            'Sender Name'
        );
    }

    public function testCreateNotification(): void
    {
        $user = $this->createMock(User::class);
        $content = 'Test Content';
        $title = 'Test Title';

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->callback(function ($notification) use ($user, $content, $title) {
                return $notification instanceof Notification
                    && $notification->getMessage() === $content
                    && $notification->getTitle() === $title
                    && $notification->getUser() === $user;
            }));

        $this->entityManager->expects($this->once())
            ->method('flush');

        $notification = $this->notificationService->createNotification($content, $title, $user, true);

        $this->assertInstanceOf(Notification::class, $notification);
        $this->assertEquals($content, $notification->getMessage());
        $this->assertEquals($title, $notification->getTitle());
        $this->assertSame($user, $notification->getUser());
    }

    public function testCreateNotificationWithoutFlush(): void
    {
        $user = $this->createMock(User::class);
        $content = 'Test Content';
        $title = 'Test Title';

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(Notification::class));

        $this->entityManager->expects($this->never())
            ->method('flush');

        $this->notificationService->createNotification($content, $title, $user, false);
    }

    public function testFlush(): void
    {
        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->notificationService->flush();
    }

    public function testCreateAndSendNotification(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getEmail')->willReturn('test@example.com');
        $user->method('getId')->willReturn(1);

        $content = 'Test Content';
        $title = 'Test Title';
        $url = 'http://example.com/notification/1';

        $this->router->expects($this->once())
            ->method('generate')
            ->with('notification_show', $this->anything(), UrlGeneratorInterface::ABSOLUTE_URL)
            ->willReturn($url);

        $this->entityManager->expects($this->once()) // persist notification
            ->method('persist')
            ->with($this->isInstanceOf(Notification::class));

        $this->entityManager->expects($this->exactly(2)) // flush twice: once for creation, once for sentAt update
            ->method('flush');

        $this->mailer->expects($this->once())
            ->method('send')
            ->with($this->callback(function (TemplatedEmail $email) use ($user, $content, $title, $url) {
                $context = $email->getContext();
                return $email->getTo()[0]->getAddress() === 'test@example.com'
                    && $email->getSubject() === $title
                    && $email->getHtmlTemplate() === 'notification/email.html.twig'
                    && $context['message'] === $content
                    && $context['user'] === $user
                    && $context['url'] === $url;
            }));

        $this->notificationService->createAndSendNotification($content, $title, $user);
    }

    public function testCreateAndSendNotificationWithNoEmail(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getEmail')->willReturn(null);
        $user->method('getId')->willReturn(1);

        $content = 'Test Content';
        $title = 'Test Title';

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Cannot send notification: User has no email.', ['user_id' => 1]);

        $this->mailer->expects($this->never())
            ->method('send');

        $this->notificationService->createAndSendNotification($content, $title, $user);
    }

    public function testCreateAndSendNotificationRetry(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getEmail')->willReturn('test@example.com');
        $user->method('getId')->willReturn(1);

        $content = 'Test Content';
        $title = 'Test Title';

        $this->router->method('generate')->willReturn('http://example.com/notification/1');

        $exception = new TransportException('Transport error');

        $matcher = $this->exactly(2);
        $this->mailer->expects($matcher)
            ->method('send')
            ->willReturnCallback(function (TemplatedEmail $email) use ($matcher, $exception) {
                if ($matcher->numberOfInvocations() === 1) {
                    throw $exception;
                }

                // Second call should have the X-Transport header
                if (!$email->getHeaders()->has('X-Transport') || $email->getHeaders()->get('X-Transport')->getBody() !== 'alternative') {
                    throw new \Exception('Expected X-Transport header on retry');
                }
            });

        $this->logger->expects($this->once())
            ->method('error')
            ->with($exception->getDebug());

        $this->notificationService->createAndSendNotification($content, $title, $user);
    }
}
