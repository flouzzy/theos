<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use App\Repository\BonusRepository;
use Doctrine\ORM\EntityManagerInterface;

class LootBoxService
{
    public function __construct(
        private BonusRepository $bonusRepository,
        private EntityManagerInterface $entityManager,
        private NotificationService $notificationService
    ) {}

    public function unlockRandomBonus(User $user): void
    {
        $bonus = $this->bonusRepository->findRandomBonus();
        
        if (!$bonus) {
            return;
        }

        if (!$user->getUnlockedBonuses()->contains($bonus)) {
            $user->addUnlockedBonus($bonus);
            $this->entityManager->persist($user);
            $this->entityManager->flush();

            $this->notificationService->addNotification(
                $user,
                "🎁 Surprise ! Tu as débloqué un bonus",
                sprintf("Félicitations pour tes progrès ! Tu as débloqué : %s (%s)", $bonus->getTitle(), strtoupper($bonus->getType() ?? 'Contenu')),
                // Assuming a profile page or downloads page
                null
            );
        }
    }
}
