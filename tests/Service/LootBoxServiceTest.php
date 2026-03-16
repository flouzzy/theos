<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Bonus;
use App\Entity\User;
use App\Repository\BonusRepository;
use App\Service\LootBoxService;
use App\Service\NotificationService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class LootBoxServiceTest extends TestCase
{
    private BonusRepository $bonusRepository;
    private EntityManagerInterface $entityManager;
    private NotificationService $notificationService;
    private LootBoxService $lootBoxService;

    protected function setUp(): void
    {
        $this->bonusRepository = $this->createMock(BonusRepository::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->notificationService = $this->createMock(NotificationService::class);

        $this->lootBoxService = new LootBoxService(
            $this->bonusRepository,
            $this->entityManager,
            $this->notificationService
        );
    }

    public function testUnlockRandomBonusNoBonusFound(): void
    {
        $user = $this->createMock(User::class);

        $this->bonusRepository->expects($this->once())
            ->method('findRandomBonus')
            ->willReturn(null);

        $user->expects($this->never())->method('getUnlockedBonuses');
        $this->entityManager->expects($this->never())->method('persist');
        $this->entityManager->expects($this->never())->method('flush');
        $this->notificationService->expects($this->never())->method('addNotification');

        $this->lootBoxService->unlockRandomBonus($user);
    }

    public function testUnlockRandomBonusUserAlreadyHasBonus(): void
    {
        $user = $this->createMock(User::class);
        $bonus = $this->createMock(Bonus::class);

        $this->bonusRepository->expects($this->once())
            ->method('findRandomBonus')
            ->willReturn($bonus);

        $unlockedBonuses = new ArrayCollection([$bonus]);

        $user->expects($this->once())
            ->method('getUnlockedBonuses')
            ->willReturn($unlockedBonuses);

        $user->expects($this->never())->method('addUnlockedBonus');
        $this->entityManager->expects($this->never())->method('persist');
        $this->entityManager->expects($this->never())->method('flush');
        $this->notificationService->expects($this->never())->method('addNotification');

        $this->lootBoxService->unlockRandomBonus($user);
    }

    public function testUnlockRandomBonusSuccess(): void
    {
        $user = $this->createMock(User::class);
        $bonus = $this->createMock(Bonus::class);

        $bonus->expects($this->once())->method('getTitle')->willReturn('Special PDF');
        $bonus->expects($this->once())->method('getType')->willReturn('pdf');

        $this->bonusRepository->expects($this->once())
            ->method('findRandomBonus')
            ->willReturn($bonus);

        $unlockedBonuses = new ArrayCollection([]);

        $user->expects($this->once())
            ->method('getUnlockedBonuses')
            ->willReturn($unlockedBonuses);

        $user->expects($this->once())
            ->method('addUnlockedBonus')
            ->with($bonus);

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($user);

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->notificationService->expects($this->once())
            ->method('addNotification')
            ->with(
                $user,
                "🎁 Surprise ! Tu as débloqué un bonus",
                "Félicitations pour tes progrès ! Tu as débloqué : Special PDF (PDF)",
                null
            );

        $this->lootBoxService->unlockRandomBonus($user);
    }
}
