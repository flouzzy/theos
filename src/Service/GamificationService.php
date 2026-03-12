<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\Badge;
use App\Entity\BadgeType;
use App\Entity\Course;
use App\Entity\XpTransaction;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class GamificationService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private TranslatorInterface $translator,
        private NotificationService $notificationService,
        private UrlGeneratorInterface $urlGenerator
    ) {}

    public function addXp(User $user, int $amount, string $reason = ''): void
    {
        $oldXp = $user->getXp();
        $user->addXp($amount);

        $transaction = new XpTransaction();
        $transaction->setUser($user);
        $transaction->setAmount($amount);
        $transaction->setReason($reason);
        $this->entityManager->persist($transaction);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $this->checkLeaderboardOvertake($user, $oldXp);
    }

    public function checkLeaderboardOvertake(User $user, int $oldXp): void
    {
        $newXp = $user->getXp();
        if ($newXp <= $oldXp) {
            return;
        }

        $userRepository = $this->entityManager->getRepository(User::class);
        
        // Find users who were strictly above oldXp and are now strictly below or equal to newXp
        // Actually, anyone who had more than $oldXp but less than $newXp
        $overtakenUsers = $userRepository->createQueryBuilder('u')
            ->where('u.id != :userId')
            ->andWhere('u.xp < :newXp')
            ->andWhere('u.xp >= :oldXp')
            ->setParameter('userId', $user->getId())
            ->setParameter('newXp', $newXp)
            ->setParameter('oldXp', $oldXp)
            ->getQuery()
            ->getResult();

        foreach ($overtakenUsers as $overtakenUser) {
            if (!$overtakenUser instanceof User) {
                continue;
            }
            $this->notificationService->addNotification(
                $overtakenUser,
                "📊 Classement mis à jour",
                sprintf("Oh non ! %s vient de te doubler au classement. Reprends l'avantage maintenant !", $user->getFullname()),
                $this->urlGenerator->generate('leaderboard_index', [], UrlGeneratorInterface::ABSOLUTE_URL)
            );
        }
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
        $badgeTypeRepo = $this->entityManager->getRepository(BadgeType::class);
        $badgeType = $badgeTypeRepo->findOneBy(['code' => $code]);

        if (!$badgeType) {
            $badgeType = new BadgeType();
            $badgeType->setCode($code);
            $badgeType->setTitle($typeTitle ?? $badgeTitle);
            $badgeType->setDescription($typeDesc ?? $badgeDesc);
            $this->entityManager->persist($badgeType);
        }

        $badgeRepo = $this->entityManager->getRepository(Badge::class);
        $badge = $badgeRepo->findOneBy(['title' => $badgeTitle, 'badgeType' => $badgeType]);

        if (!$badge) {
            $badge = new Badge();
            $badge->setTitle($badgeTitle);
            $badge->setDescription($badgeDesc);
            $badge->setBadgeType($badgeType);
            $this->entityManager->persist($badge);
        }

        if (!$user->getBadges()->contains($badge)) {
            $user->addBadge($badge);
            $this->entityManager->persist($user);

            // Add notification for new badge
            $this->notificationService->addNotification(
                $user,
                "🏅 Nouveau badge débloqué !",
                sprintf("Félicitations ! Tu as remporté le badge : %s.", $badgeTitle),
                $this->urlGenerator->generate('profile_index', [], UrlGeneratorInterface::ABSOLUTE_URL)
            );

            if ($flush) {
                $this->entityManager->flush();
            }
        }
    }
}
