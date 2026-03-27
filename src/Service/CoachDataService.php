<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Course;
use App\Entity\Lesson;
use App\Entity\User;
use App\Entity\Event;
use App\Repository\CompletionRepository;
use App\Repository\EventRepository;
use Doctrine\ORM\EntityManagerInterface;

class CoachDataService
{
    public function __construct(
        private readonly CompletionRepository $completionRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly EventRepository $eventRepository,
    ) {
    }

    /**
     * Returns XP earned per day of the current week (Mon-Sun).
     * Each completion = 10 XP by default.
     *
     * @return array<string, int> ['L' => 20, 'M' => 10, ...]
     */
    public function getWeeklyXpData(User $user): array
    {
        $days = ['L' => 0, 'M' => 0, 'Me' => 0, 'J' => 0, 'V' => 0, 'S' => 0, 'D' => 0];
        $dayMap = [1 => 'L', 2 => 'M', 3 => 'Me', 4 => 'J', 5 => 'V', 6 => 'S', 7 => 'D'];

        $startOfWeek = new \DateTimeImmutable('monday this week');
        $endOfWeek = new \DateTimeImmutable('sunday this week 23:59:59');

        $qb = $this->entityManager->createQueryBuilder();
        $completions = $qb->select('c')
            ->from(\App\Entity\Completion::class, 'c')
            ->where('c.user = :user')
            ->andWhere('c.completed = true')
            ->andWhere('c.createdAt >= :start')
            ->andWhere('c.createdAt <= :end')
            ->setParameter('user', $user)
            ->setParameter('start', $startOfWeek)
            ->setParameter('end', $endOfWeek)
            ->getQuery()
            ->getResult();

        foreach ($completions as $completion) {
            $dayOfWeek = (int) $completion->getCreatedAt()->format('N'); // 1=Mon...7=Sun
            $dayKey = $dayMap[$dayOfWeek] ?? 'L';
            $days[$dayKey] += 10; // Each completion = 10 XP
        }

        return $days;
    }

    /**
     * Returns the total XP earned this week.
     */
    public function getWeeklyXpTotal(User $user): int
    {
        return array_sum($this->getWeeklyXpData($user));
    }

    /**
     * Returns the last incomplete lesson from a subscribed course (for "Reprendre" button).
     */
    public function getNextLesson(User $user): ?Lesson
    {
        $completedIds = $this->completionRepository->findCompletedLessonIdsByUser($user);

        // Preload courses, modules, and lessons to prevent N+1 queries
        $this->entityManager->createQueryBuilder()
            ->select('u', 'c', 'm', 'l')
            ->from(User::class, 'u')
            ->leftJoin('u.courses', 'c')
            ->leftJoin('c.modules', 'm')
            ->leftJoin('m.lessons', 'l')
            ->where('u = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();

        foreach ($user->getCourses() as $course) {
            $lesson = $this->findFirstIncompleteLesson($course, $completedIds);
            if ($lesson !== null) {
                return $lesson;
            }
        }

        return null;
    }

    /**
     * @param array<int> $completedIds
     */
    private function findFirstIncompleteLesson(Course $course, array $completedIds): ?Lesson
    {
        foreach ($course->getModules() as $module) {
            foreach ($module->getSortedLessons() as $lesson) {
                if (!in_array($lesson->getId(), $completedIds, true)) {
                    return $lesson;
                }
            }
        }

        return null;
    }

    /**
     * Returns the last completed lesson (for "Révision recommandée").
     */
    public function getLastCompletedLesson(User $user): ?Lesson
    {
        $qb = $this->entityManager->createQueryBuilder();
        $result = $qb->select('c')
            ->from(\App\Entity\Completion::class, 'c')
            ->join('c.lesson', 'l')
            ->where('c.user = :user')
            ->andWhere('c.completed = true')
            ->setParameter('user', $user)
            ->orderBy('c.updatedAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        return $result?->getLesson();
    }

    /**
     * Compute streak message dynamically.
     * @return array{message: string, nextTarget: int, progress: int}
     */
    public function getStreakInfo(User $user): array
    {
        $events = $this->eventRepository->findBy(
            ['user' => $user, 'type' => Event::TYPE_STREAK_LOGIN],
            ['createdAt' => 'DESC']
        );

        $currentStreak = 0;
        $bestStreak = 0;
        $tempStreak = 0;
        $lastDate = null;

        foreach ($events as $event) {
            $date = $event->getCreatedAt()->format('Y-m-d');

            if ($lastDate === null) {
                $currentStreak = 1;
                $tempStreak = 1;
                $lastDate = $date;
                continue;
            }

            $diff = (new \DateTimeImmutable($date))->diff(new \DateTimeImmutable($lastDate))->days; // Always positive difference since $lastDate is newer

            if ($diff === 1) {
                $tempStreak++;

                // If this chain is contiguous from the very first event, it's the current streak
                $diffFromLatest = (new \DateTimeImmutable($date))->diff(new \DateTimeImmutable($events[0]->getCreatedAt()->format('Y-m-d')))->days;
                if ($tempStreak === ($diffFromLatest + 1)) {
                    $currentStreak = $tempStreak;
                }
            } elseif ($diff > 1) {
                $bestStreak = max($bestStreak, $tempStreak);
                $tempStreak = 1;
            }

            $lastDate = $date;
        }

        $bestStreak = max($bestStreak, $tempStreak);

        // A streak is broken if the last event was more than 1 day ago (neither today nor yesterday)
        if (!empty($events)) {
            $latestDate = $events[0]->getCreatedAt()->format('Y-m-d');
            $today = (new \DateTimeImmutable())->format('Y-m-d');
            $diffToday = (new \DateTimeImmutable($today))->diff(new \DateTimeImmutable($latestDate))->days;
            if ($diffToday > 1) {
                $currentStreak = 0;
            }
        }

        $nextTarget = $this->getNextStreakMilestone($currentStreak);
        $previousMilestone = $this->getPreviousStreakMilestone($currentStreak);
        $range = $nextTarget - $previousMilestone;
        $progress = $range > 0 ? (int) round(($currentStreak - $previousMilestone) / $range * 100) : 100;

        return [
            'message' => $currentStreak > 0
                ? sprintf('Complète une leçon pour atteindre %d jours', $currentStreak + 1)
                : 'Commence ta première série !',
            'nextTarget' => $nextTarget,
            'progress' => min($progress, 100),
            'currentStreak' => $currentStreak,
            'bestStreak' => $bestStreak,
        ];
    }

    private function getNextStreakMilestone(int $streak): int
    {
        $milestones = [3, 7, 14, 30, 60, 90, 180, 365];
        foreach ($milestones as $m) {
            if ($streak < $m) {
                return $m;
            }
        }
        return $streak + 30;
    }

    private function getPreviousStreakMilestone(int $streak): int
    {
        $milestones = [0, 3, 7, 14, 30, 60, 90, 180, 365];
        $prev = 0;
        foreach ($milestones as $m) {
            if ($streak < $m) {
                return $prev;
            }
            $prev = $m;
        }
        return $prev;
    }
}
