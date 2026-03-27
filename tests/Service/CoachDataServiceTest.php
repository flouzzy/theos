<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Event;
use App\Entity\User;
use App\Repository\CompletionRepository;
use App\Repository\EventRepository;
use App\Service\CoachDataService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class CoachDataServiceTest extends TestCase
{
    private CoachDataService $coachDataService;
    private $eventRepository;

    protected function setUp(): void
    {
        $completionRepository = $this->createMock(CompletionRepository::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $this->eventRepository = $this->createMock(EventRepository::class);

        $this->coachDataService = new CoachDataService(
            $completionRepository,
            $entityManager,
            $this->eventRepository
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
        $event = new Event();
        $event->setCreatedAt(new \DateTimeImmutable($dateString));
        return $event;
    }

    /**
     * @dataProvider streakEventDataProvider
     */
    public function testGetStreakInfoCalculatesProperly(array $eventDates, array $expected): void
    {
        $user = $this->createMock(User::class);

        $events = [];
        foreach ($eventDates as $dateString) {
            $events[] = $this->createEvent($dateString);
        }

        $this->eventRepository
            ->method('findBy')
            ->with(
                ['user' => $user, 'type' => Event::TYPE_STREAK_LOGIN],
                ['createdAt' => 'DESC']
            )
            ->willReturn($events);

        $result = $this->coachDataService->getStreakInfo($user);

        $this->assertEquals($expected, $result);
    }

    public static function streakEventDataProvider(): array
    {
        $today = (new \DateTimeImmutable())->format('Y-m-d');
        $yesterday = (new \DateTimeImmutable('-1 days'))->format('Y-m-d');
        $twoDaysAgo = (new \DateTimeImmutable('-2 days'))->format('Y-m-d');
        $threeDaysAgo = (new \DateTimeImmutable('-3 days'))->format('Y-m-d');
        $fiveDaysAgo = (new \DateTimeImmutable('-5 days'))->format('Y-m-d');
        $sixDaysAgo = (new \DateTimeImmutable('-6 days'))->format('Y-m-d');
        $sevenDaysAgo = (new \DateTimeImmutable('-7 days'))->format('Y-m-d');

        return [
            '0 days streak (no events)' => [
                [],
                [
                    'message' => 'Commence ta première série !',
                    'nextTarget' => 3,
                    'progress' => 0,
                    'currentStreak' => 0,
                    'bestStreak' => 0,
                ]
            ],
            '1 day streak (today)' => [
                [$today],
                [
                    'message' => 'Complète une leçon pour atteindre 2 jours',
                    'nextTarget' => 3,
                    'progress' => 33,
                    'currentStreak' => 1,
                    'bestStreak' => 1,
                ]
            ],
            '1 day streak (yesterday)' => [
                [$yesterday],
                [
                    'message' => 'Complète une leçon pour atteindre 2 jours',
                    'nextTarget' => 3,
                    'progress' => 33,
                    'currentStreak' => 1,
                    'bestStreak' => 1,
                ]
            ],
            '0 days streak (last event 2 days ago)' => [
                [$twoDaysAgo, $threeDaysAgo], // broken streak
                [
                    'message' => 'Commence ta première série !',
                    'nextTarget' => 3,
                    'progress' => 0,
                    'currentStreak' => 0,
                    'bestStreak' => 2, // 2 days in a row previously
                ]
            ],
            '3 days streak (today, yesterday, 2 days ago)' => [
                [$today, $yesterday, $twoDaysAgo],
                [
                    'message' => 'Complète une leçon pour atteindre 4 jours',
                    'nextTarget' => 7,
                    'progress' => 0,
                    'currentStreak' => 3,
                    'bestStreak' => 3,
                ]
            ],
            '2 days streak current, but best streak is 3' => [
                [$today, $yesterday, $fiveDaysAgo, $sixDaysAgo, $sevenDaysAgo],
                [
                    'message' => 'Complète une leçon pour atteindre 3 jours',
                    'nextTarget' => 3,
                    'progress' => 67,
                    'currentStreak' => 2,
                    'bestStreak' => 3,
                ]
            ],
        ];
    }
}
