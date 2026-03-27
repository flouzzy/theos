<?php

namespace App\Tests\Service;

use App\Entity\Completion;
use App\Entity\Course;
use App\Entity\Lesson;
use App\Entity\Module;
use App\Entity\User;
use App\Repository\CompletionRepository;
use App\Service\CoachDataService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CoachDataServiceTest extends TestCase
{
    /** @var CompletionRepository&MockObject */
    private CompletionRepository $completionRepository;

    /** @var EntityManagerInterface&MockObject */
    private EntityManagerInterface $entityManager;

    private CoachDataService $coachDataService;

    protected function setUp(): void
    {
        $this->completionRepository = $this->createMock(CompletionRepository::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);

        $this->coachDataService = new CoachDataService(
            $this->completionRepository,
            $this->entityManager
        );
    }

    public function testGetWeeklyXpDataAndTotal(): void
    {
        $user = $this->createMock(User::class);

        // We will mock two completions on Monday and Wednesday
        $monday = new \DateTimeImmutable('monday this week 10:00:00');
        $wednesday = new \DateTimeImmutable('wednesday this week 14:00:00');

        $completion1 = $this->createMock(Completion::class);
        $completion1->method('getCreatedAt')->willReturn($monday);

        $completion2 = $this->createMock(Completion::class);
        $completion2->method('getCreatedAt')->willReturn($wednesday);

        $query = $this->getMockBuilder(Query::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getResult'])
            ->getMock();

        $query->method('getResult')->willReturn([$completion1, $completion2]);

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->method('select')->willReturnSelf();
        $queryBuilder->method('from')->willReturnSelf();
        $queryBuilder->method('where')->willReturnSelf();
        $queryBuilder->method('andWhere')->willReturnSelf();
        $queryBuilder->method('setParameter')->willReturnSelf();
        $queryBuilder->method('getQuery')->willReturn($query);

        $this->entityManager->method('createQueryBuilder')
            ->willReturn($queryBuilder);

        $expectedData = [
            'L' => 10,
            'M' => 0,
            'Me' => 10,
            'J' => 0,
            'V' => 0,
            'S' => 0,
            'D' => 0,
        ];

        $resultData = $this->coachDataService->getWeeklyXpData($user);
        $this->assertEquals($expectedData, $resultData);

        $resultTotal = $this->coachDataService->getWeeklyXpTotal($user);
        $this->assertEquals(20, $resultTotal);
    }

    public function testGetNextLesson(): void
    {
        $user = $this->createMock(User::class);
        $course = $this->createMock(Course::class);
        $module = $this->createMock(Module::class);

        $lesson1 = $this->createMock(Lesson::class);
        $lesson1->method('getId')->willReturn(1);
        $lesson1->method('getItemOrder')->willReturn(1);

        $lesson2 = $this->createMock(Lesson::class);
        $lesson2->method('getId')->willReturn(2);
        $lesson2->method('getItemOrder')->willReturn(2);

        $lesson3 = $this->createMock(Lesson::class);
        $lesson3->method('getId')->willReturn(3);
        $lesson3->method('getItemOrder')->willReturn(3);

        $module->method('getSortedLessons')->willReturn([$lesson1, $lesson2, $lesson3]);
        $course->method('getModules')->willReturn(new ArrayCollection([$module]));
        $user->method('getCourses')->willReturn(new ArrayCollection([$course]));

        // Mark lesson 1 as completed
        $this->completionRepository->method('findCompletedLessonIdsByUser')
            ->with($user)
            ->willReturn([1]);

        $result = $this->coachDataService->getNextLesson($user);

        // Lesson 2 is next based on item order
        $this->assertSame($lesson2, $result);
    }

    public function testGetNextLessonAllCompleted(): void
    {
        $user = $this->createMock(User::class);
        $course = $this->createMock(Course::class);
        $module = $this->createMock(Module::class);

        $lesson = $this->createMock(Lesson::class);
        $lesson->method('getId')->willReturn(1);
        $lesson->method('getItemOrder')->willReturn(1);

        $module->method('getSortedLessons')->willReturn([$lesson]);
        $course->method('getModules')->willReturn(new ArrayCollection([$module]));
        $user->method('getCourses')->willReturn(new ArrayCollection([$course]));

        // Mark all as completed
        $this->completionRepository->method('findCompletedLessonIdsByUser')
            ->with($user)
            ->willReturn([1]);

        $result = $this->coachDataService->getNextLesson($user);

        // No uncompleted lesson
        $this->assertNull($result);
    }

    public function testGetLastCompletedLesson(): void
    {
        $user = $this->createMock(User::class);
        $lesson = $this->createMock(Lesson::class);

        $completion = $this->createMock(Completion::class);
        $completion->method('getLesson')->willReturn($lesson);

        $query = $this->getMockBuilder(Query::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getOneOrNullResult'])
            ->getMock();

        $query->method('getOneOrNullResult')->willReturn($completion);

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->method('select')->willReturnSelf();
        $queryBuilder->method('from')->willReturnSelf();
        $queryBuilder->method('join')->willReturnSelf();
        $queryBuilder->method('where')->willReturnSelf();
        $queryBuilder->method('andWhere')->willReturnSelf();
        $queryBuilder->method('setParameter')->willReturnSelf();
        $queryBuilder->method('orderBy')->willReturnSelf();
        $queryBuilder->method('setMaxResults')->willReturnSelf();
        $queryBuilder->method('getQuery')->willReturn($query);

        $this->entityManager->method('createQueryBuilder')
            ->willReturn($queryBuilder);

        $result = $this->coachDataService->getLastCompletedLesson($user);

        $this->assertSame($lesson, $result);
    }

    public function testGetStreakInfoZeroStreak(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getStreak')->willReturn(0);

        $result = $this->coachDataService->getStreakInfo($user);

        $this->assertEquals([
            'message' => 'Commence ta première série !',
            'nextTarget' => 3,
            'progress' => 0,
        ], $result);
    }

    public function testGetStreakInfoMidwayStreak(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getStreak')->willReturn(10); // Between 7 and 14

        $result = $this->coachDataService->getStreakInfo($user);

        $this->assertEquals([
            'message' => 'Complète une leçon pour atteindre 11 jours',
            'nextTarget' => 14,
            'progress' => (int) round((10 - 7) / (14 - 7) * 100), // (3 / 7) * 100 = 43
        ], $result);
    }

    public function testGetStreakInfoMaxStreak(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getStreak')->willReturn(400); // Beyond 365

        $result = $this->coachDataService->getStreakInfo($user);

        $this->assertEquals([
            'message' => 'Complète une leçon pour atteindre 401 jours',
            'nextTarget' => 430, // 400 + 30
            'progress' => 54, // (400 - 365) / (430 - 365) * 100 = 35 / 65 * 100 = 53.8 -> 54
        ], $result);
    }
}
