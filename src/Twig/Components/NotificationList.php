<?php

declare(strict_types=1);

namespace App\Twig\Components;

use App\Entity\Notification;
use App\Repository\NotificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent('NotificationList')]
final class NotificationList
{
    use DefaultActionTrait;

    public function __construct(
        private readonly NotificationRepository $notificationRepository,
        private readonly Security $security,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    /**
     * @return Notification[]
     */
    public function getNotifications(): array
    {
        /** @var \App\Entity\User $user */
        $user = $this->security->getUser();
        if (!$user) {
            return [];
        }

        return $this->notificationRepository->findAllByUser($user, 50);
    }

    #[LiveAction]
    public function markAsRead(#[LiveArg] int $id): void
    {
        $notification = $this->notificationRepository->find($id);

        if (!$notification) {
            return;
        }

        // We can't see other people's notifications, but we can see (and mark) global ones
        if ($notification->getUser() !== null && $notification->getUser() !== $this->security->getUser()) {
            return;
        }
        
        // If it's a global notification, marking it as read will mark it for EVERYONE.
        // This is a current limitation of the schema, but better than doing nothing.

        $notification->setIsRead(true);
        $this->entityManager->flush();
    }

    #[LiveAction]
    public function markAllAsRead(): void
    {
        /** @var \App\Entity\User|null $user */
        $user = $this->security->getUser();
        
        if ($user) {
            $this->notificationRepository->markAllAsRead($user);
        }
    }
}
