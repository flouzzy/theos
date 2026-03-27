<?php

namespace App\Tests\EventSubscriber;

use App\EventSubscriber\ContentSecurityPolicySubscriber;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

class ContentSecurityPolicySubscriberTest extends TestCase
{
    public function testGetSubscribedEvents(): void
    {
        $events = ContentSecurityPolicySubscriber::getSubscribedEvents();

        $this->assertArrayHasKey(KernelEvents::RESPONSE, $events);
        $this->assertSame('onKernelResponse', $events[KernelEvents::RESPONSE]);
    }

    public function testOnKernelResponse(): void
    {
        $subscriber = new ContentSecurityPolicySubscriber();

        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = new Request();
        $response = new Response();

        $event = new ResponseEvent(
            $kernel,
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            $response
        );

        $subscriber->onKernelResponse($event);

        $this->assertTrue($response->headers->has('Content-Security-Policy'));
        $expectedPolicy = "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' data: https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com; img-src 'self' data: https:; connect-src 'self' https://cdn.jsdelivr.net; font-src 'self' data: https://fonts.gstatic.com; object-src 'none'; frame-ancestors 'none'; frame-src 'self' https://www.youtube.com https://youtube.com https://docs.google.com;";
        $this->assertSame($expectedPolicy, $response->headers->get('Content-Security-Policy'));
    }
}
