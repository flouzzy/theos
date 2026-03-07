<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\Badge;
use App\Entity\BadgeType;
use App\Entity\Course;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class GamificationService
{
    private array $badgeTypeCache = [];
    private array $badgeCache = [];

    public function __construct(
        private EntityManagerInterface $entityManager,
        private TranslatorInterface $translator
    ) {}

    public function addXp(User $user, int $amount, string $reason = ''): void
    {
        $user->addXp($amount);
        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }

    public function updateStreak(User $user): void
    {
        $now = new \DateTimeImmutable();
        $today = $now->setTime(0, 0, 0);
        $lastStreakDate = $user->getLastStreakDate();
        $updated = false;

        if ($lastStreakDate) {
            $lastStreakDay = $lastStreakDate->setTime(0, 0, 0);
            $diff = $today->diff($lastStreakDay)->days;

            if ($diff === 1) {
                // Streak continues
                $user->setStreak($user->getStreak() + 1);
                $updated = true;
            } elseif ($diff > 1) {
                // Streak broken
                $user->setStreak(1);
                $updated = true;
            }
            // If diff === 0, already updated today, do nothing unless it's the first time setting today
        } else {
            // First streak
            $user->setStreak(1);
            $updated = true;
        }

        // Always update the date if it's not today or if we updated the streak
        if ($updated || !$lastStreakDate || $today->diff($lastStreakDate->setTime(0, 0, 0))->days > 0) {
             $user->setLastStreakDate($now);
             $this->entityManager->persist($user);
             $this->entityManager->flush();

             if ($updated) {
                 $this->checkStreakBadges($user);
             }
        }
    }

    public function checkStreakBadges(User $user): void
    {
        $streak = $user->getStreak();
        $milestones = [
            3 => ['code' => 'STREAK_3', 'title' => 'On Fire', 'desc' => '3 day learning streak'],
            7 => ['code' => 'STREAK_7', 'title' => 'Dedicated', 'desc' => '7 day learning streak'],
            30 => ['code' => 'STREAK_30', 'title' => 'Unstoppable', 'desc' => '30 day learning streak'],
        ];

        if (array_key_exists($streak, $milestones)) {
            $milestone = $milestones[$streak];
            $this->awardBadge($user, $milestone['code'], $milestone['title'], $milestone['desc']);
        }
    }

    public function awardCourseCompletionBadge(User $user, Course $course, bool $flush = true): void
    {
        $badgeTitle = 'Completed ' . $course->getTitle();
        $badgeDesc = 'Awarded for completing the course: ' . $course->getTitle();
        $typeTitle = 'Course Completion';
        $typeDesc = 'Badge awarded for completing a course.';

        $this->awardBadge($user, 'COURSE_COMPLETION', $badgeTitle, $badgeDesc, $typeTitle, $typeDesc, $flush);
    }

    public function awardEarlyBirdBadge(User $user, Course $course, \DateTimeInterface $startDate, bool $flush = true): void
    {
        $now = new \DateTimeImmutable();
        $diff = $now->diff($startDate);

        // Ensure comparison accounts for absolute difference in days
        if ($diff->days < 7) {
            $badgeTitle = 'Early Bird: ' . $course->getTitle();
            $badgeDesc = 'Completed ' . $course->getTitle() . ' within 7 days';
            $typeTitle = 'Early Bird';
            $typeDesc = 'Completed a course within 7 days';

            $this->awardBadge($user, 'EARLY_BIRD', $badgeTitle, $badgeDesc, $typeTitle, $typeDesc, $flush);
        }
    }

    public function awardBadge(
        User $user,
        string $code,
        string $badgeTitle,
        string $badgeDesc,
        ?string $typeTitle = null,
        ?string $typeDesc = null,
        bool $flush = true
    ): void {
        if (isset($this->badgeTypeCache[$code])) {
            $badgeType = $this->badgeTypeCache[$code];
        } else {
            $badgeTypeRepo = $this->entityManager->getRepository(BadgeType::class);
            $badgeType = $badgeTypeRepo->findOneBy(['code' => $code]);

            if (!$badgeType) {
                $badgeType = new BadgeType();
                $badgeType->setCode($code);
                $badgeType->setTitle($typeTitle ?? $badgeTitle);
                $badgeType->setDescription($typeDesc ?? $badgeDesc);
                $this->entityManager->persist($badgeType);
            }
            $this->badgeTypeCache[$code] = $badgeType;
        }

        $badgeCacheKey = $badgeTitle . '|' . spl_object_id($badgeType);
        if (isset($this->badgeCache[$badgeCacheKey])) {
            $badge = $this->badgeCache[$badgeCacheKey];
        } else {
            $badgeRepo = $this->entityManager->getRepository(Badge::class);
            $badge = $badgeRepo->findOneBy(['title' => $badgeTitle, 'badgeType' => $badgeType]);

            if (!$badge) {
                $badge = new Badge();
                $badge->setTitle($badgeTitle);
                $badge->setDescription($badgeDesc);
                $badge->setBadgeType($badgeType);
                $this->entityManager->persist($badge);
            }
            $this->badgeCache[$badgeCacheKey] = $badge;
        }

        if (!$user->getBadges()->contains($badge)) {
            $user->addBadge($badge);
            $this->entityManager->persist($user);

            if ($flush) {
                $this->entityManager->flush();
            }
        }
    }
}
