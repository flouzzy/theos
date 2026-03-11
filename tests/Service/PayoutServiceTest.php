<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Course;
use App\Entity\Payout;
use App\Entity\User;
use App\Repository\CompletionRepository;
use App\Repository\CourseRepository;
use App\Repository\TransactionRepository;
use App\Service\PayoutService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class PayoutServiceTest extends TestCase
{
    public function testCalculateMonthlyPayouts(): void
    {
        $month = new \DateTimeImmutable('2026-03-01');
        
        $transactionRepo = $this->createMock(TransactionRepository::class);
        $transactionRepo->method('findTotalRevenueBetween')->willReturn(100000); // 1000 EUR

        $completionRepo = $this->createMock(CompletionRepository::class);
        
        $course1 = $this->createMock(Course::class);
        $course1->method('getId')->willReturn(1);
        $course1->method('getRevenueSharePercentage')->willReturn(50);
        $creator1 = $this->createMock(User::class);
        $course1->method('getAuthor')->willReturn($creator1);

        $course2 = $this->createMock(Course::class);
        $course2->method('getId')->willReturn(2);
        $course2->method('getRevenueSharePercentage')->willReturn(30);
        $creator2 = $this->createMock(User::class);
        $course2->method('getAuthor')->willReturn($creator2);

        $courseRepo = $this->createMock(CourseRepository::class);
        $courseRepo->method('findAll')->willReturn([$course1, $course2]);

        $completionRepo->method('countCompletionsForCourseBetween')->willReturnCallback(function($course) use ($course1, $course2) {
            if ($course === $course1) return 60;
            if ($course === $course2) return 40;
            return 0;
        });

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->atLeastOnce())->method('persist');
        $entityManager->expects($this->once())->method('flush');

        $service = new PayoutService($transactionRepo, $completionRepo, $courseRepo, $entityManager);
        $payouts = $service->calculateMonthlyPayouts($month);

        // Total completions = 100
        // Course 1 ratio = 60/100 = 0.6. Course 1 revenue = 1000 * 0.6 = 600. Payout = 600 * 50% = 300 EUR (30000 cents)
        // Course 2 ratio = 40/100 = 0.4. Course 2 revenue = 1000 * 0.4 = 400. Payout = 400 * 30% = 120 EUR (12000 cents)

        $this->assertCount(2, $payouts);
        $this->assertEquals(30000, $payouts[0]->getAmount());
        $this->assertEquals(12000, $payouts[1]->getAmount());
    }
}
