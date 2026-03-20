<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\LeaderboardService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class LeaderboardServiceTest extends TestCase
{
    private UserRepository&MockObject $userRepository;
    private LeaderboardService $leaderboardService;

    protected function setUp(): void
    {
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->leaderboardService = new LeaderboardService($this->userRepository);
    }

    public function testGetStudentOfTheWeekReturnsUser(): void
    {
        $user = $this->createMock(User::class);

        $this->userRepository->expects($this->once())
            ->method('findOneBy')
            ->with([], ['xp' => 'DESC'])
            ->willReturn($user);

        $result = $this->leaderboardService->getStudentOfTheWeek();

        $this->assertSame($user, $result);
    }

    public function testGetStudentOfTheWeekReturnsNullWhenNoUserFound(): void
    {
        $this->userRepository->expects($this->once())
            ->method('findOneBy')
            ->with([], ['xp' => 'DESC'])
            ->willReturn(null);

        $result = $this->leaderboardService->getStudentOfTheWeek();

        $this->assertNull($result);
    }
}
