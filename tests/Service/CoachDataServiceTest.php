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

    private function createEvent(string $dateString): Event
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
