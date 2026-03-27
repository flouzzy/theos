<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class ContentSecurityPolicySubscriber implements EventSubscriberInterface
{
    public function onKernelResponse(ResponseEvent $event): void
    {
        $response = $event->getResponse();

        // Define the CSP policy
        // Note: 'unsafe-inline' and 'unsafe-eval' are allowed here for compatibility with Stimulus/Hotwire/Tailwind as per project requirements.
        // In a stricter environment, we would use nonces or hashes.
        $policy = "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' data: https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com; img-src 'self' data: https:; connect-src 'self' https://cdn.jsdelivr.net; font-src 'self' data: https://fonts.gstatic.com; object-src 'none'; frame-ancestors 'none'; frame-src 'self' https://www.youtube.com https://youtube.com https://docs.google.com;";

        $response->headers->set('Content-Security-Policy', $policy);

    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => 'onKernelResponse',
        ];
    }
}
