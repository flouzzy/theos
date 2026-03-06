<?php

declare(strict_types=1);

namespace App\Tests\EventSubscriber;

use App\Entity\User;
use App\EventSubscriber\LogoutSubscriber;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Event\LogoutEvent;

class LogoutSubscriberTest extends TestCase
{
    public function testOnLogoutEventUpdatesLastConnectionAt(): void
    {
        $user = new User();
        $user->setFirstname('John');
        $user->setLastname('Doe');

        $this->assertNull($user->getLastConnectionAt());

        $token = $this->createMock(TokenInterface::class);
        $token->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $request = $this->createMock(Request::class);
        $event = new LogoutEvent($request, $token);

        $subscriber = new LogoutSubscriber();
        $subscriber->onLogoutEvent($event);

        $this->assertNotNull($user->getLastConnectionAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $user->getLastConnectionAt());
    }

    public function testOnLogoutEventWithNullToken(): void
    {
        $request = $this->createMock(Request::class);
        $event = new LogoutEvent($request, null);

        $subscriber = new LogoutSubscriber();
        $subscriber->onLogoutEvent($event);

        $this->assertTrue(true, 'Subscriber handled null token without throwing exceptions.');
    }

    public function testGetSubscribedEvents(): void
    {
        $events = LogoutSubscriber::getSubscribedEvents();
        $this->assertArrayHasKey(LogoutEvent::class, $events);
        $this->assertEquals('onLogoutEvent', $events[LogoutEvent::class]);
    }
}
