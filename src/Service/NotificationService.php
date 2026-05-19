<?php

namespace App\Service;

use App\Entity\Notification;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class NotificationService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private MailerInterface $mailer,
        private UrlGeneratorInterface $router,
        private LoggerInterface $logger,
        private TranslatorInterface $translator,
        #[Autowire('%default_from_email%')] private string $senderEmail,
        #[Autowire('%default_from_name%')] private string $senderName,
    ) {
    }

    public function createNotification(string $content, string $title = '', ?User $user = null, ?string $link = null, bool $flush = true): Notification
    {
        // Créer la notification
        $notification = new Notification();
        if ($user) {
            $notification->setUser($user);
        }
        $notification->setTitle($title);
        $notification->setMessage($content);
        $notification->setLink($link);
        $this->entityManager->persist($notification);
        if ($flush) {
            $this->entityManager->flush();
        }
        return $notification;
    }

    public function addNotification(User $user, string $title, string $message, ?string $link = null): void
    {
        $this->createAndSendNotification($message, $title, $user, $link);
    }

    /**
     * Add a notification using translation keys so the message is rendered in the
     * reader's locale at display time rather than the sender's locale at save time.
     */
    public function addTranslatableNotification(
        User $user,
        string $titleKey,
        string $messageKey,
        array $params = [],
        ?string $link = null
    ): void {
        $notification = new Notification();
        $notification->setUser($user);
        $notification->setTitleKey($titleKey);
        $notification->setMessageKey($messageKey);
        $notification->setTranslationParams($params);
        // Keep a translated copy in the legacy fields so email delivery and any
        // consumer that hasn't been updated still has something readable.
        $notification->setTitle($this->translator->trans($titleKey, $params));
        $notification->setMessage($this->translator->trans($messageKey, $params));
        $notification->setLink($link);
        $this->entityManager->persist($notification);
        $this->entityManager->flush();
        $this->sendNotification($notification, $user);
    }

    public function flush(): void
    {
        $this->entityManager->flush();
    }

    public function createAndSendNotification(string $content, string $title, User $user, ?string $link = null): void
    {
        $notification = $this->createNotification($content, $title, $user, $link);
        $this->sendNotification($notification, $user);
    }

    private function sendNotification(Notification $notification, User $user): void
    {
        $userEmail = $user->getEmail();
        if (!$userEmail) {
            $this->logger->error('Cannot send notification: User has no email.', ['user_id' => $user->getId()]);
            return;
        }

        $email = (new TemplatedEmail())
            ->from(new Address($this->senderEmail, $this->senderName))
            ->to($userEmail)
            ->subject($notification->getTitle() ?? 'Notification')
            // path of the Twig template to render
            ->htmlTemplate('notification/email.html.twig')
            ->context([
                'message' => $notification->getMessage(),
                'user' => $user,
                // Generate absolute url for notification show route
                'url' => $notification->getLink() ?? $this->router->generate(
                    'notification_show',
                    [
                        'id' => $notification->getId(),
                    ],
                    UrlGeneratorInterface::ABSOLUTE_URL
                ),
            ]);

        try {
            $this->mailer->send($email);
        } catch (TransportExceptionInterface $e) {
            // some error prevented the email sending; display an
            // error message or try to resend the message
            $this->logger->error($e->getDebug());

            // ... use the transport "alternative":
            $email->getHeaders()->addTextHeader('X-Transport', 'alternative');
            $this->mailer->send($email);
        }

        // Save notification sentAt date
        $notification->setSentAt(new \DateTimeImmutable());
        $this->entityManager->flush();
    }
}
