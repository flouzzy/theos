<?php

namespace App\Service;

use App\Entity\Notification;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class NotificationService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private MailerInterface $mailer,
        private UrlGeneratorInterface $router
    ) {
    }

    public function createNotification(string $content, string $title = '', User $user = null): Notification
    {
        // Créer la notification
        $notification = new Notification();
        if ($user) {
            $notification->setUser($user);
        }
        $notification->setTitle($title);
        $notification->setMessage($content);
        $this->entityManager->persist($notification);
        $this->entityManager->flush();
        return $notification;
    }

    public function createAndSendNotification(string $content, string $title, User $user): void
    {
        $notification = $this->createNotification($content, $title, $user);
        $this->sendNotification($notification, $user);
    }

    private function sendNotification(Notification $notification, User $user): void
    {
        $email = (new TemplatedEmail())
            ->from('no-reply@academie.lerocher.fr')
            ->to($user->getEmail())
            ->subject($notification->getTitle())
            // path of the Twig template to render
            ->htmlTemplate('notification/email.html.twig')
            ->context([
                'message' => $notification->getMessage(),
                'user' => $user,
                // Generate absolute url for notification show route
                'url' => $this->router->generate(
                    'notification_show',
                    [
                        'id' => $notification->getId(),
                    ],
                    UrlGeneratorInterface::ABSOLUTE_URL
                ),
            ]);

        $this->mailer->send($email);

        // Save notification sentAt date
        $notification->setSentAt(new \DateTimeImmutable());
        $this->entityManager->flush();
    }
}
