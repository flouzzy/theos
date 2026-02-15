<?php

namespace App\EventSubscriber;

use App\Event\UserUpdatedEvent;
use App\Service\BrevoApi;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class UserUpdatedSubscriber implements EventSubscriberInterface
{
    public function __construct(private BrevoApi $brevoApi)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            UserUpdatedEvent::class => 'onUserUpdated',
        ];
    }

    public function onUserUpdated(UserUpdatedEvent $event): void
    {
        $user = $event->getUser();

        // Update fullname
        $user->setFullname($user->getFirstname() . ' ' . $user->getLastname());

        // Sync with Brevo
        $this->brevoApi->addOrUpdateContact($user);
    }
}
