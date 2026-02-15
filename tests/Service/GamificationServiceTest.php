<?php

namespace App\Tests\Service;

use App\Entity\User;
use App\Service\GamificationService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class GamificationServiceTest extends TestCase
{
    public function testAddXp()
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $translator = $this->createMock(TranslatorInterface::class);
        $service = new GamificationService($entityManager, $translator);

        $user = new User();
        // User xp is 0 by default

        $entityManager->expects($this->once())->method('persist')->with($user);
        $entityManager->expects($this->once())->method('flush');

        $service->addXp($user, 10);
        $this->assertEquals(10, $user->getXp());
    }

    public function testUpdateStreakIncrement()
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $translator = $this->createMock(TranslatorInterface::class);

        $service = new GamificationService($entityManager, $translator);

        $user = new User();
        $user->setStreak(1);
        $yesterday = (new \DateTimeImmutable('yesterday'));
        $user->setLastStreakDate($yesterday);

        // Expect persist and flush
        $entityManager->expects($this->once())->method('persist')->with($user);
        $entityManager->expects($this->once())->method('flush');

        $service->updateStreak($user);

        $this->assertEquals(2, $user->getStreak());
        $this->assertEquals((new \DateTimeImmutable())->format('Y-m-d'), $user->getLastStreakDate()->format('Y-m-d'));
    }

    public function testUpdateStreakReset()
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $translator = $this->createMock(TranslatorInterface::class);
        $service = new GamificationService($entityManager, $translator);

        $user = new User();
        $user->setStreak(5);
        $twoDaysAgo = (new \DateTimeImmutable('-2 days'));
        $user->setLastStreakDate($twoDaysAgo);

        $entityManager->expects($this->once())->method('persist')->with($user);
        $entityManager->expects($this->once())->method('flush');

        $service->updateStreak($user);

        $this->assertEquals(1, $user->getStreak());
        $this->assertEquals((new \DateTimeImmutable())->format('Y-m-d'), $user->getLastStreakDate()->format('Y-m-d'));
    }

    public function testUpdateStreakSameDay()
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $translator = $this->createMock(TranslatorInterface::class);
        $service = new GamificationService($entityManager, $translator);

        $user = new User();
        $user->setStreak(5);
        $user->setLastStreakDate(new \DateTimeImmutable());

        // No persist/flush expected as date is same and streak not updated
        $entityManager->expects($this->never())->method('persist');
        $entityManager->expects($this->never())->method('flush');

        $service->updateStreak($user);

        $this->assertEquals(5, $user->getStreak());
    }
}
