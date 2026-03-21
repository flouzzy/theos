<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\User;
use App\Repository\BadgeRepository;
use App\Repository\CourseCompletionRepository;
use App\Service\YearInReviewService;
use PHPUnit\Framework\TestCase;

class YearInReviewServiceTest extends TestCase
{
    public function testGetYearlyStats(): void
    {
        $user = $this->createMock(User::class);

        $completionRepo = $this->createMock(CourseCompletionRepository::class);
        $completionRepo->expects($this->once())
            ->method('count')
            ->with(['user' => $user])
            ->willReturn(12);

        $badgeRepo = $this->createMock(BadgeRepository::class);
        $badgeRepo->expects($this->once())
            ->method('count')
            ->with(['user' => $user])
            ->willReturn(5);

        $service = new YearInReviewService($completionRepo, $badgeRepo);

        $year = 2024;
        $stats = $service->getYearlyStats($user, $year);

        $this->assertSame([
            'totalCourses' => 12,
            'totalBadges' => 5,
            'topSkill' => 'PHP',
        ], $stats);
    }
}
