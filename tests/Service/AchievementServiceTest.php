<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Badge;
use App\Entity\Completion;
use App\Entity\CourseCompletion;
use App\Entity\User;
use App\Repository\CompletionRepository;
use App\Repository\CourseCompletionRepository;
use App\Service\AchievementService;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AchievementServiceTest extends TestCase
{
    private CourseCompletionRepository&MockObject $courseCompletionRepository;
    private CompletionRepository&MockObject $completionRepository;
    private AchievementService $achievementService;

    protected function setUp(): void
    {
        $this->courseCompletionRepository = $this->createMock(CourseCompletionRepository::class);
        $this->completionRepository = $this->createMock(CompletionRepository::class);

        $this->achievementService = new AchievementService(
            $this->courseCompletionRepository,
            $this->completionRepository
        );
    }

    public function testEmptyAchievements(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getBadges')->willReturn(new ArrayCollection());

        $this->courseCompletionRepository->method('findBy')->willReturn([]);
        $this->completionRepository->method('countTotalDurationByUser')->willReturn(0);

        $achievements = $this->achievementService->getAchievements($user);

        $this->assertEmpty($achievements);
    }

    public function testFirstCourseCompletedAchievement(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getBadges')->willReturn(new ArrayCollection());

        $date = new \DateTimeImmutable('2023-01-01');
        $completion = $this->createMock(CourseCompletion::class);
        $completion->method('getCreatedAt')->willReturn($date);

        $this->courseCompletionRepository->expects($this->exactly(2))
            ->method('findBy')
            ->willReturnMap([
                [['user' => $user, 'completed' => true], ['createdAt' => 'ASC'], 1, null, [$completion]],
                [['user' => $user, 'completed' => true], ['createdAt' => 'ASC'], null, null, [$completion]],
            ]);

        $this->completionRepository->method('countTotalDurationByUser')->willReturn(0);

        $achievements = $this->achievementService->getAchievements($user);

        $this->assertCount(1, $achievements);
        $this->assertEquals('🎓', $achievements[0]['icon']);
        $this->assertEquals('Premier cours complété', $achievements[0]['title']);
        $this->assertEquals($date, $achievements[0]['date']);
        $this->assertTrue($achievements[0]['reached']);
    }

    public function testFiveCoursesCompletedAchievement(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getBadges')->willReturn(new ArrayCollection());

        $completions = [];
        for ($i = 1; $i <= 5; $i++) {
            $completion = $this->createMock(CourseCompletion::class);
            $completion->method('getCreatedAt')->willReturn(new \DateTimeImmutable("2023-01-0$i"));
            $completions[] = $completion;
        }

        $this->courseCompletionRepository->expects($this->exactly(2))
            ->method('findBy')
            ->willReturnMap([
                [['user' => $user, 'completed' => true], ['createdAt' => 'ASC'], 1, null, [$completions[0]]],
                [['user' => $user, 'completed' => true], ['createdAt' => 'ASC'], null, null, $completions],
            ]);

        $this->completionRepository->method('countTotalDurationByUser')->willReturn(0);

        $achievements = $this->achievementService->getAchievements($user);

        $this->assertCount(2, $achievements);
        $this->assertEquals('🎓', $achievements[0]['icon']);
        $this->assertEquals('⭐', $achievements[1]['icon']);
        $this->assertEquals('5 cours terminés', $achievements[1]['title']);
        $this->assertEquals($completions[4]->getCreatedAt(), $achievements[1]['date']);
    }

    public function testTwentyHoursLearningAchievement(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getBadges')->willReturn(new ArrayCollection());

        $this->courseCompletionRepository->method('findBy')->willReturn([]);

        $this->completionRepository->method('countTotalDurationByUser')->willReturn(1200);

        $lastCompletionDate = new \DateTimeImmutable('2023-02-01');
        $lastCompletion = $this->createMock(Completion::class);
        $lastCompletion->method('getCreatedAt')->willReturn($lastCompletionDate);

        $this->completionRepository->method('findBy')
            ->with(['user' => $user, 'completed' => true], ['createdAt' => 'DESC'], 1)
            ->willReturn([$lastCompletion]);

        $achievements = $this->achievementService->getAchievements($user);

        $this->assertCount(1, $achievements);
        $this->assertEquals('🔥', $achievements[0]['icon']);
        $this->assertEquals('20h d\'apprentissage', $achievements[0]['title']);
        $this->assertEquals($lastCompletionDate, $achievements[0]['date']);
    }

    public function testExistingBadges(): void
    {
        $user = $this->createMock(User::class);

        $badgeDate = new \DateTimeImmutable('2023-03-01');
        $badge = $this->createMock(Badge::class);
        $badge->method('getTitle')->willReturn('Expert');
        $badge->method('getCreatedAt')->willReturn($badgeDate);
        $badge->method('getDescription')->willReturn('A true expert');

        $user->method('getBadges')->willReturn(new ArrayCollection([$badge]));

        $this->courseCompletionRepository->method('findBy')->willReturn([]);
        $this->completionRepository->method('countTotalDurationByUser')->willReturn(0);

        $achievements = $this->achievementService->getAchievements($user);

        $this->assertCount(1, $achievements);
        $this->assertEquals('🏆', $achievements[0]['icon']);
        $this->assertEquals('Expert', $achievements[0]['title']);
        $this->assertEquals($badgeDate, $achievements[0]['date']);
        $this->assertEquals('A true expert', $achievements[0]['description']);
    }

    public function testAllAchievementsCombined(): void
    {
        $user = $this->createMock(User::class);

        // 5 Course Completions
        $completions = [];
        for ($i = 1; $i <= 5; $i++) {
            $completion = $this->createMock(CourseCompletion::class);
            $completion->method('getCreatedAt')->willReturn(new \DateTimeImmutable("2023-01-0$i"));
            $completions[] = $completion;
        }
        $this->courseCompletionRepository->method('findBy')->willReturnMap([
            [['user' => $user, 'completed' => true], ['createdAt' => 'ASC'], 1, null, [$completions[0]]],
            [['user' => $user, 'completed' => true], ['createdAt' => 'ASC'], null, null, $completions],
        ]);

        // 20h Learning
        $this->completionRepository->method('countTotalDurationByUser')->willReturn(1200);
        $lastCompletion = $this->createMock(Completion::class);
        $lastCompletion->method('getCreatedAt')->willReturn(new \DateTimeImmutable('2023-02-01'));
        $this->completionRepository->method('findBy')->willReturn([$lastCompletion]);

        // 1 Badge
        $badge = $this->createMock(Badge::class);
        $badge->method('getTitle')->willReturn('Badge Title');
        $badge->method('getCreatedAt')->willReturn(new \DateTimeImmutable('2023-03-01'));
        $user->method('getBadges')->willReturn(new ArrayCollection([$badge]));

        $achievements = $this->achievementService->getAchievements($user);

        $this->assertCount(4, $achievements);
        $this->assertEquals('🎓', $achievements[0]['icon']);
        $this->assertEquals('⭐', $achievements[1]['icon']);
        $this->assertEquals('🔥', $achievements[2]['icon']);
        $this->assertEquals('🏆', $achievements[3]['icon']);
    }
}
