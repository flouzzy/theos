<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Event\UserVerifiedEvent;
use App\Event\TrainingCompletionEvent;
use App\Service\BadgeManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class BadgeSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private BadgeManager $badgeManager
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            UserVerifiedEvent::class => 'onUserVerified',
            TrainingCompletionEvent::class => 'onTrainingCompletion',
        ];
    }

    public function onUserVerified(UserVerifiedEvent $event): void
    {
        $this->badgeManager->checkAndAwardBadges($event->getUser());
    }

    public function onTrainingCompletion(TrainingCompletionEvent $event): void
    {
        $this->badgeManager->checkAndAwardBadges($event->getUser());
    }
}
