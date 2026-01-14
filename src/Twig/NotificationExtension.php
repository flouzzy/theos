<?php

namespace App\Twig;

use App\Repository\NotificationRepository;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class NotificationExtension extends AbstractExtension
{
    public function __construct(
        private NotificationRepository $notificationRepository
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('unread_notifications_count', [$this, 'getUnreadCount']),
        ];
    }

    public function getUnreadCount(): int
    {
        // Use the optimized method if available, otherwise fallback or implementation here
        // Ideally checking method_exists or just implementing the logic via repo query
        
        return $this->notificationRepository->countUnread();
    }
}
