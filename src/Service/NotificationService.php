<?php

namespace App\Service;

use App\Entity\Notification;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;

class NotificationService
{
    public function __construct(private EntityManagerInterface $entityManager, private MailerInterface $mailer)
    {
    }

    public function createAndSendNotification(User $user, string $emailSubject, string $emailContent, string $title = ''): void
    {
        // Créer la notification
        $notification = new Notification();
        $notification->setUser($user);
        $notification->setTitle($title);
        $notification->setMessage($emailContent);

        $this->entityManager->persist($notification);
        $this->entityManager->flush();

        // Envoyer l'email
        $email = (new TemplatedEmail())
            ->from('no-reply@academie.lerocher.fr')
            ->to($user->getEmail())
            ->subject($emailSubject)
            // path of the Twig template to render
            ->htmlTemplate('notification/email.html.twig')
            ->context([
                'content' => $emailContent,
                'user' => $user,
            ]);

        $this->mailer->send($email);
    }
}
