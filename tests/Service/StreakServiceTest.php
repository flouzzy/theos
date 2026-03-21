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

    public function testRepairStreakWithSufficientCoins(): void
    {
        $user = new User();
        $user->setRocherCoins(100);
        $user->setStreak(5);

        $this->entityManager->expects($this->once())
            ->method('flush');

        $result = $this->streakService->repairStreak($user, 50);

        $this->assertTrue($result);
        $this->assertSame(50, $user->getRocherCoins());
        $this->assertSame(6, $user->getStreak());
    }

    public function testRepairStreakWithInsufficientCoins(): void
    {
        $user = new User();
        $user->setRocherCoins(10);
        $user->setStreak(5);

        $this->entityManager->expects($this->never())
            ->method('flush');

        $result = $this->streakService->repairStreak($user, 50);

        $this->assertFalse($result);
        $this->assertSame(10, $user->getRocherCoins());
        $this->assertSame(5, $user->getStreak());
    }
}
