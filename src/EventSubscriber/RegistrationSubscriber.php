<?php

namespace App\EventSubscriber;

use App\Event\UserVerifiedEvent;
use App\Service\BrevoApi;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class RegistrationSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private BrevoApi $brevoApi
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            UserVerifiedEvent::class => 'onUserVerified',
        ];
    }

    public function onUserVerified(UserVerifiedEvent $event): void
    {
        $user = $event->getUser();
        $this->brevoApi->addContactToOnboardedList($user);
    }
}
