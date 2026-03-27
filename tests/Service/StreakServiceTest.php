<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\User;
use App\Service\StreakService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class StreakServiceTest extends TestCase
{
    /** @var EntityManagerInterface&MockObject */
    private EntityManagerInterface $entityManager;
    private StreakService $streakService;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->streakService = new StreakService($this->entityManager);
    }

    public function testRepairStreakFailsWhenNotEnoughCoins(): void
    {
        $user = new User();
        $user->setRocherCoins(20);
        $user->setStreak(5);

        // EntityManager shouldn't be flushed since the repair fails
        $this->entityManager->expects($this->never())
            ->method('flush');

        $result = $this->streakService->repairStreak($user, 50);

        $this->assertFalse($result);
        $this->assertSame(20, $user->getRocherCoins());
        $this->assertSame(5, $user->getStreak());
    }

    public function testRepairStreakSucceeds(): void
    {
        $user = new User();
        $user->setRocherCoins(100);
        $user->setStreak(5);

        // EntityManager should be flushed exactly once since repair succeeds
        $this->entityManager->expects($this->once())
            ->method('flush');

        $result = $this->streakService->repairStreak($user, 50);

        $this->assertTrue($result);
        $this->assertSame(50, $user->getRocherCoins()); // 100 - 50 = 50
        $this->assertSame(6, $user->getStreak()); // 5 + 1 = 6
    }
}
