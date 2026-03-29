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

    public function testGetAchievementsEmpty(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getBadges')->willReturn(new ArrayCollection());

        $this->courseCompletionRepository->method('findBy')->willReturn([]);
        $this->completionRepository->method('countTotalDurationByUser')->willReturn(0);

        $achievements = $this->achievementService->getAchievements($user);

        $this->assertEmpty($achievements);
    }

    public function testGetAchievementsFirstCourseCompleted(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getBadges')->willReturn(new ArrayCollection());

        $createdAt = new \DateTimeImmutable('2023-01-01');
        $courseCompletion = $this->createMock(CourseCompletion::class);
        $courseCompletion->method('getCreatedAt')->willReturn($createdAt);

        $this->courseCompletionRepository->method('findBy')
            ->willReturnMap([
                [['user' => $user, 'completed' => true], ['createdAt' => 'ASC'], 1, null, [$courseCompletion]],
                [['user' => $user, 'completed' => true], ['createdAt' => 'ASC'], null, null, [$courseCompletion]],
            ]);

        $this->completionRepository->method('countTotalDurationByUser')->willReturn(0);

        $achievements = $this->achievementService->getAchievements($user);

        $this->assertCount(1, $achievements);
        $this->assertEquals('Premier cours complété', $achievements[0]['title']);
        $this->assertEquals('🎓', $achievements[0]['icon']);
        $this->assertEquals($createdAt, $achievements[0]['date']);
        $this->assertTrue($achievements[0]['reached']);
    }

    public function testGetAchievementsFiveCoursesCompleted(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getBadges')->willReturn(new ArrayCollection());

        $completions = [];
        for ($i = 0; $i < 5; $i++) {
            $createdAt = new \DateTimeImmutable("2023-01-0$i");
            $completion = $this->createMock(CourseCompletion::class);
            $completion->method('getCreatedAt')->willReturn($createdAt);
            $completions[] = $completion;
        }

        $this->courseCompletionRepository->method('findBy')
            ->willReturnMap([
                [['user' => $user, 'completed' => true], ['createdAt' => 'ASC'], 1, null, [$completions[0]]],
                [['user' => $user, 'completed' => true], ['createdAt' => 'ASC'], null, null, $completions],
            ]);

        $this->completionRepository->method('countTotalDurationByUser')->willReturn(0);

        $achievements = $this->achievementService->getAchievements($user);

        $this->assertCount(2, $achievements);
        $this->assertEquals('Premier cours complété', $achievements[0]['title']);
        $this->assertEquals('5 cours terminés', $achievements[1]['title']);
        $this->assertEquals('⭐', $achievements[1]['icon']);
        $this->assertEquals($completions[4]->getCreatedAt(), $achievements[1]['date']);
    }

    public function testGetAchievementsTwentyHoursLearning(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getBadges')->willReturn(new ArrayCollection());

        $this->courseCompletionRepository->method('findBy')->willReturn([]);
        $this->completionRepository->method('countTotalDurationByUser')->willReturn(1200);

        $createdAt = new \DateTimeImmutable('2023-02-01');
        $completion = $this->createMock(Completion::class);
        $completion->method('getCreatedAt')->willReturn($createdAt);

        $this->completionRepository->method('findBy')
            ->with(['user' => $user, 'completed' => true], ['createdAt' => 'DESC'], 1)
            ->willReturn([$completion]);

        $achievements = $this->achievementService->getAchievements($user);

        $this->assertCount(1, $achievements);
        $this->assertEquals('20h d\'apprentissage', $achievements[0]['title']);
        $this->assertEquals('🔥', $achievements[0]['icon']);
        $this->assertEquals($createdAt, $achievements[0]['date']);
    }

    public function testGetAchievementsExistingBadges(): void
    {
        $user = $this->createMock(User::class);

        $createdAt = new \DateTimeImmutable('2023-03-01');
        $badge = $this->createMock(Badge::class);
        $badge->method('getTitle')->willReturn('Expert PHP');
        $badge->method('getCreatedAt')->willReturn($createdAt);
        $badge->method('getDescription')->willReturn('A complété tous les cours PHP');

        $user->method('getBadges')->willReturn(new ArrayCollection([$badge]));

        $this->courseCompletionRepository->method('findBy')->willReturn([]);
        $this->completionRepository->method('countTotalDurationByUser')->willReturn(0);

        $achievements = $this->achievementService->getAchievements($user);

        $this->assertCount(1, $achievements);
        $this->assertEquals('Expert PHP', $achievements[0]['title']);
        $this->assertEquals('🏆', $achievements[0]['icon']);
        $this->assertEquals($createdAt, $achievements[0]['date']);
        $this->assertEquals('A complété tous les cours PHP', $achievements[0]['description']);
    }

    public function testGetAchievementsCombined(): void
    {
        $user = $this->createMock(User::class);

        // Existing badge
        $badgeCreatedAt = new \DateTimeImmutable('2023-03-01');
        $badge = $this->createMock(Badge::class);
        $badge->method('getTitle')->willReturn('Expert PHP');
        $badge->method('getCreatedAt')->willReturn($badgeCreatedAt);
        $badge->method('getDescription')->willReturn('A complété tous les cours PHP');
        $user->method('getBadges')->willReturn(new ArrayCollection([$badge]));

        // Course completions
        $completions = [];
        for ($i = 0; $i < 5; $i++) {
            $createdAt = new \DateTimeImmutable("2023-01-0$i");
            $completion = $this->createMock(CourseCompletion::class);
            $completion->method('getCreatedAt')->willReturn($createdAt);
            $completions[] = $completion;
        }

        $this->courseCompletionRepository->method('findBy')
            ->willReturnMap([
                [['user' => $user, 'completed' => true], ['createdAt' => 'ASC'], 1, null, [$completions[0]]],
                [['user' => $user, 'completed' => true], ['createdAt' => 'ASC'], null, null, $completions],
            ]);

        // Learning hours
        $this->completionRepository->method('countTotalDurationByUser')->willReturn(1500);
        $learningCreatedAt = new \DateTimeImmutable('2023-02-01');
        $learningCompletion = $this->createMock(Completion::class);
        $learningCompletion->method('getCreatedAt')->willReturn($learningCreatedAt);
        $this->completionRepository->method('findBy')
            ->with(['user' => $user, 'completed' => true], ['createdAt' => 'DESC'], 1)
            ->willReturn([$learningCompletion]);

        $achievements = $this->achievementService->getAchievements($user);

        $this->assertCount(4, $achievements);
        $this->assertEquals('Premier cours complété', $achievements[0]['title']);
        $this->assertEquals('5 cours terminés', $achievements[1]['title']);
        $this->assertEquals('20h d\'apprentissage', $achievements[2]['title']);
        $this->assertEquals('Expert PHP', $achievements[3]['title']);
    }
}
