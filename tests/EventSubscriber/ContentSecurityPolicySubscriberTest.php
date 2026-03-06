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
        $expectedPolicy = "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; font-src 'self' data:; connect-src 'self'; object-src 'none'; frame-ancestors 'none';";
        $this->assertSame($expectedPolicy, $response->headers->get('Content-Security-Policy'));
    }
}
