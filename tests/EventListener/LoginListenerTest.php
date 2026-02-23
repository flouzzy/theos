<?php

namespace App\Tests\EventListener;

use App\Entity\User;
use App\EventListener\LoginListener;
use App\Service\GamificationService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

class LoginListenerTest extends TestCase
{
    private GamificationService|MockObject $gamificationService;
    private LoginListener $listener;

    protected function setUp(): void
    {
        $this->gamificationService = $this->createMock(GamificationService::class);
        $this->listener = new LoginListener($this->gamificationService);
    }

    public function testOnSecurityInteractiveLoginWithNonAppUser(): void
    {
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn(null); // Or some other object

        $event = $this->createMock(InteractiveLoginEvent::class);
        $event->method('getAuthenticationToken')->willReturn($token);

        $this->gamificationService->expects($this->never())->method('updateStreak');
        $this->gamificationService->expects($this->never())->method('addXp');

        $this->listener->onSecurityInteractiveLogin($event);
    }

    public function testOnFirstLoginEver(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getLastStreakDate')->willReturn(null);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $event = $this->createMock(InteractiveLoginEvent::class);
        $event->method('getAuthenticationToken')->willReturn($token);

        $this->gamificationService->expects($this->once())->method('updateStreak')->with($user);
        $this->gamificationService->expects($this->once())->method('addXp')->with($user, 20, 'daily_login');

        $this->listener->onSecurityInteractiveLogin($event);
    }

    public function testOnLoginToday(): void
    {
        $today = (new \DateTimeImmutable())->setTime(0, 0, 0);

        $user = $this->createMock(User::class);
        // We simulate that the user logged in "today" already (or streak date was set to today)
        // Note: The listener calculates "today" using new \DateTimeImmutable(), so as long as we use the same system time, it matches.
        // To be safe against midnight crossing, we use new \DateTimeImmutable() in the test too.
        $user->method('getLastStreakDate')->willReturn($today);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $event = $this->createMock(InteractiveLoginEvent::class);
        $event->method('getAuthenticationToken')->willReturn($token);

        $this->gamificationService->expects($this->once())->method('updateStreak')->with($user);
        // Should NOT add XP because it's not the first login today
        $this->gamificationService->expects($this->never())->method('addXp');

        $this->listener->onSecurityInteractiveLogin($event);
    }

    public function testOnLoginYesterday(): void
    {
        $yesterday = (new \DateTimeImmutable())->modify('-1 day')->setTime(0, 0, 0);

        $user = $this->createMock(User::class);
        $user->method('getLastStreakDate')->willReturn($yesterday);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $event = $this->createMock(InteractiveLoginEvent::class);
        $event->method('getAuthenticationToken')->willReturn($token);

        $this->gamificationService->expects($this->once())->method('updateStreak')->with($user);
        // Should add XP because last login was yesterday (diff > 0)
        $this->gamificationService->expects($this->once())->method('addXp')->with($user, 20, 'daily_login');

        $this->listener->onSecurityInteractiveLogin($event);
    }
}
