<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class StreakService
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    public function repairStreak(User $user, int $cost = 50): bool
    {
        if ($user->getRocherCoins() < $cost) {
            return false;
        }

        $user->setRocherCoins($user->getRocherCoins() - $cost);
        $user->setStreak($user->getStreak() + 1); // Simplifié
        
        $this->entityManager->flush();
        return true;
    }
}
