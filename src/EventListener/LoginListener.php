<?php

namespace App\EventListener;

use App\Entity\User;
use App\Service\GamificationService;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

final class LoginListener
{
    public function __construct(
        private GamificationService $gamificationService
    ) {}

    #[AsEventListener(event: 'security.interactive_login')]
    public function onSecurityInteractiveLogin(InteractiveLoginEvent $event): void
    {
        $user = $event->getAuthenticationToken()->getUser();

        if (!$user instanceof User) {
            return;
        }

        $now = new \DateTimeImmutable();
        $today = $now->setTime(0, 0, 0);
        $lastStreakDate = $user->getLastStreakDate();

        $isFirstLoginToday = !$lastStreakDate || $today->diff($lastStreakDate->setTime(0, 0, 0))->days > 0;

        $this->gamificationService->updateStreak($user);

        if ($isFirstLoginToday) {
            $this->gamificationService->addXp($user, 20, 'daily_login');
        }
    }
}
