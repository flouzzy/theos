<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class LocaleSubscriber implements EventSubscriberInterface
{
    public function __construct(private string $defaultLocale = 'fr')
    {
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        // 1. Essayer de récupérer depuis la session
        if ($locale = $request->getSession()->get('_locale')) {
            $request->setLocale($locale);
            return;
        }

        // 2. Essayer de détecter depuis le header Accept-Language
        $preferredLocale = $request->getPreferredLanguage(['fr', 'en']);
        if ($preferredLocale) {
            $request->setLocale($preferredLocale);
            return;
        }

        // 3. Sinon, utiliser la locale par défaut
        $request->setLocale($this->defaultLocale);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => [['onKernelRequest', 20]],
        ];
    }
}
