<?php

// src/MessageHandler/NotificationHandler.php
namespace App\MessageHandler;

use App\Message\Notification;
use App\Repository\UserRepository;
use App\Service\NotificationService;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsMessageHandler]
class NotificationHandler
{
    public function __construct(
        private UserRepository $userRepository,
        private NotificationService $notificationService,
        private TranslatorInterface $translator,
        private Security $security
    ) {
    }
    public function __invoke(Notification $notification)
    {
        // Envoie d'une notification à tous les utilisateurs
        $users = $this->userRepository->iterateVerifiedUsers();
        foreach ($users as $user) {
            if ($user !== $this->security->getUser()) {
                $this->notificationService->createNotification(
                    $notification->getContent(),
                    $this->translator->trans($notification->getTitle()),
                    $user,
                    flush: false
                );
            }
        }
        $this->notificationService->flush();
    }
}
