<?php

namespace App\Tests\Service;

use App\Entity\User;
use App\Service\GamificationService;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class GamificationServiceTest extends TestCase
{
    private function createService($entityManager): GamificationService
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $notificationService = $this->createMock(NotificationService::class);
        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        return new GamificationService($entityManager, $translator, $notificationService, $urlGenerator);
    }

    public function testAddXp(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $service = $this->createService($entityManager);

        $user = new User();
        // User xp is 0 by default

        $entityManager->expects($this->exactly(2))->method('persist');
        $entityManager->expects($this->once())->method('flush');

        $service->addXp($user, 10);
        $this->assertGreaterThanOrEqual(10, $user->getXp());
    }

    public function testUpdateStreakIncrement(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $service = $this->createService($entityManager);

        $user = new User();
        $user->setStreak(1);
        $yesterday = (new \DateTimeImmutable('yesterday'));
        $user->setLastStreakDate($yesterday);

        // Expect persist and flush
        $entityManager->expects($this->once())->method('persist')->with($user);
        $entityManager->expects($this->once())->method('flush');

        $service->updateStreak($user);

        $this->assertEquals(2, $user->getStreak());
        $this->assertEquals((new \DateTimeImmutable())->format('Y-m-d'), $user->getLastStreakDate()?->format('Y-m-d'));
    }

    public function testUpdateStreakReset(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $service = $this->createService($entityManager);

        $user = new User();
        $user->setStreak(5);
        $twoDaysAgo = (new \DateTimeImmutable('-2 days'));
        $user->setLastStreakDate($twoDaysAgo);

        $entityManager->expects($this->once())->method('persist')->with($user);
        $entityManager->expects($this->once())->method('flush');

        $service->updateStreak($user);

        $this->assertEquals(1, $user->getStreak());
        $this->assertEquals((new \DateTimeImmutable())->format('Y-m-d'), $user->getLastStreakDate()?->format('Y-m-d'));
    }

    public function testUpdateStreakSameDay(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $service = $this->createService($entityManager);

        $user = new User();
        $user->setStreak(5);
        $user->setLastStreakDate(new \DateTimeImmutable());

        // No persist/flush expected as date is same and streak not updated
        $entityManager->expects($this->never())->method('persist');
        $entityManager->expects($this->never())->method('flush');

        $service->updateStreak($user);

        $this->assertEquals(5, $user->getStreak());
    }

    public function testAwardHelpfulBadge(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $service = $this->createService($entityManager);

        $user = $this->createMock(User::class);
        $comment = $this->createMock(\App\Entity\Comment::class);
        $likes = $this->createMock(\Doctrine\Common\Collections\Collection::class);
        $likes->method('count')->willReturn(10);
        $comment->method('getLikes')->willReturn($likes);
        
        $user->method('getComments')->willReturn(new \Doctrine\Common\Collections\ArrayCollection([$comment]));
        
        // On vérifie que la méthode est bien appelée
        // Note: awardBadge déclenche persist/flush, donc on les attend
        $entityManager->expects($this->atLeastOnce())->method('persist');
        $entityManager->expects($this->atLeastOnce())->method('flush');

        $service->awardHelpfulBadge($user);
    }
}
