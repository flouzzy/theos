<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\User;
use App\Repository\CompletionRepository;
use App\Repository\UserRepository;
use App\Repository\XpTransactionRepository;
use App\Service\SendMail;
use App\Service\WeeklyRecapService;
use PHPUnit\Framework\TestCase;

class WeeklyRecapServiceTest extends TestCase
{
    public function testSendWeeklyRecaps(): void
    {
        $user1 = $this->createMock(User::class);
        $user1->method('getEmail')->willReturn('user1@example.com');
        $user1->method('getFirstname')->willReturn('User1');

        $user2 = $this->createMock(User::class);
        $user2->method('getEmail')->willReturn('user2@example.com');
        $user2->method('getFirstname')->willReturn('User2');

        $userRepo = $this->createMock(UserRepository::class);
        $userRepo->method('findBy')->with(['weeklySummary' => true])->willReturn([$user1, $user2]);

        $xpRepo = $this->createMock(XpTransactionRepository::class);
        $xpRepo->method('findXpGainedByUserBetween')->willReturnCallback(function($user) use ($user1, $user2) {
            if ($user === $user1) return 100;
            if ($user === $user2) return 0;
            return 0;
        });

        $completionRepo = $this->createMock(CompletionRepository::class);
        $completionRepo->method('countByUserBetween')->willReturn(5);

        $sendMail = $this->createMock(SendMail::class);
        $sendMail->expects($this->once())->method('send');

        $service = new WeeklyRecapService($userRepo, $xpRepo, $completionRepo, $sendMail, 'from@example.com', 'From', 'App Name');
        $count = $service->sendWeeklyRecaps();

        $this->assertEquals(1, $count); // Only user1 had activity
    }
}
