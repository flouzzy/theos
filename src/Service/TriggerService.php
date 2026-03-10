<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\Lesson;
use App\Repository\UserRepository;
use App\Repository\LessonRepository;
use App\Repository\CompletionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class TriggerService
{
    public function __construct(
        private UserRepository $userRepository,
        private CompletionRepository $completionRepository,
        private NotificationService $notificationService,
        private CoachAIAgent $aiAgent,
        private UrlGeneratorInterface $urlGenerator,
    ) {}

    /**
     * Process daily triggers for all active users.
     */
    public function processDailyTriggers(): void
    {
        $users = $this->userRepository->findAll();

        foreach ($users as $user) {
            $this->processStreakTrigger($user);
            $this->processDailyDigestTrigger($user);
            $this->processHabitTrigger($user);
            $this->processInactivityTrigger($user);
            $this->processMilestoneTrigger($user);
        }
    }

    /**
     * Trigger #11: Inactivity trigger: 'We miss you, here is a 5-min micro-lesson'
     */
    private function processInactivityTrigger(User $user): void
    {
        $now = new \DateTimeImmutable();
        $lastConnection = $user->getLastConnectionAt();

        if ($lastConnection && $now->diff($lastConnection)->days === 3) {
            // Check if we already notified for this inactivity
            $this->notificationService->addNotification(
                $user,
                "👋 Tu nous manques !",
                "Cela fait 3 jours que nous ne t'avons pas vu. Et si tu prenais 5 minutes pour une petite leçon ?",
                $this->urlGenerator->generate('app_home', [], UrlGeneratorInterface::ABSOLUTE_URL)
            );
        }
    }

    /**
     * Trigger #12: Milestone reminder: 'You are 1 lesson away from completing the course'
     */
    private function processMilestoneTrigger(User $user): void
    {
        foreach ($user->getCourses() as $course) {
            $allLessons = [];
            foreach ($course->getModules() as $module) {
                foreach ($module->getLessons() as $lesson) {
                    $allLessons[] = $lesson;
                }
            }

            if (empty($allLessons)) continue;

            $completions = $this->completionRepository->findBy([
                'user' => $user,
                'lesson' => $allLessons
            ]);

            $completedCount = 0;
            foreach ($completions as $c) {
                if ($c->isCompleted()) $completedCount++;
            }

            $remaining = count($allLessons) - $completedCount;

            if ($remaining === 1) {
                $this->notificationService->addNotification(
                    $user,
                    "🎯 Presque arrivé !",
                    sprintf("Il ne te reste plus qu'UNE seule leçon pour terminer le cours '%s'. Tu y es presque !", $course->getTitle()),
                    $this->urlGenerator->generate('course_show', ['slug' => $course->getSlug()], UrlGeneratorInterface::ABSOLUTE_URL)
                );
            }
        }
    }

    /**
     * Trigger #5: AI nudge: 'You usually study at 8 PM, ready to start?'
     */
    private function processHabitTrigger(User $user): void
    {
        // Get last 20 completions to find habits
        $completions = $this->completionRepository->findBy(['user' => $user], ['createdAt' => 'DESC'], 20);
        
        if (count($completions) < 5) {
            return;
        }

        $hours = [];
        foreach ($completions as $completion) {
            $hour = (int)$completion->getCreatedAt()->format('H');
            $hours[$hour] = ($hours[$hour] ?? 0) + 1;
        }

        arsort($hours);
        $usualHour = (int)key($hours);
        $frequency = current($hours);

        if ($frequency >= 3) {
            $now = new \DateTimeImmutable('now', new \DateTimeZone($user->getTimezone()));
            $currentHour = (int)$now->format('H');

            if ($currentHour === $usualHour) {
                // Check if we already notified recently to avoid spamming every minute of that hour
                // This would need a 'last_habit_nudge_at' field or checking existing notifications
                $this->notificationService->addNotification(
                    $user,
                    "🧠 C'est ton heure habituelle !",
                    "Tu étudies souvent à cette heure-ci. Prêt pour ta dose quotidienne de savoir ?",
                    $this->urlGenerator->generate('app_home', [], UrlGeneratorInterface::ABSOLUTE_URL)
                );
            }
        }
    }

    /**
     * Trigger #2: Push notification: 'Don't break your streak!'
     * Check if user hasn't studied today and is about to lose their streak.
     */
    private function processStreakTrigger(User $user): void
    {
        if ($user->getStreak() <= 0) {
            return;
        }

        $now = new \DateTimeImmutable('now', new \DateTimeZone($user->getTimezone()));
        $lastStreakDate = $user->getLastStreakDate();

        if (!$lastStreakDate) {
            return;
        }

        $lastStreakDate = $lastStreakDate->setTimezone(new \DateTimeZone($user->getTimezone()));
        
        // If last study was yesterday and hasn't studied today yet
        $diff = $now->diff($lastStreakDate->setTime(0, 0, 0));
        
        if ($diff->days === 1 && $now->format('H') >= 18) { // 6 PM local time nudge
            $this->notificationService->addNotification(
                $user,
                "🔥 Ne perds pas ton rythme !",
                "Tu as une série de {$user->getStreak()} jours ! Étudie une leçon maintenant pour la maintenir.",
                $this->urlGenerator->generate('app_home', [], UrlGeneratorInterface::ABSOLUTE_URL)
            );
        }
    }

    /**
     * Trigger #1: Smart daily email digest with AI-curated next steps.
     */
    private function processDailyDigestTrigger(User $user): void
    {
        // For simplicity, we send it if they haven't connected in 24h
        $now = new \DateTimeImmutable();
        if ($user->getLastConnectionAt() && $now->diff($user->getLastConnectionAt())->days < 1) {
            // They are active, maybe no need for digest? 
            // Or send it anyway in the morning. Let's say morning digest at 8 AM.
        }

        if ($now->format('H') != '08') {
            // Only at 8 AM (server time for now, ideally user time)
            // return; 
        }

        // Logic to find next steps
        $nextLesson = $this->findNextLesson($user);
        
        if (!$nextLesson) {
            return;
        }

        $aiNudge = $this->aiAgent->generateNextStepNudge($user, $nextLesson);

        $this->notificationService->addNotification(
            $user,
            "💡 Ton programme du jour",
            $aiNudge,
            $this->urlGenerator->generate('lesson_show', ['lessonId' => $nextLesson->getId(), 'moduleId' => $nextLesson->getModule()->getId()], UrlGeneratorInterface::ABSOLUTE_URL)
        );
    }

    private function findNextLesson(User $user): ?Lesson
    {
        // Find the first non-completed lesson in the user's courses
        foreach ($user->getCourses() as $course) {
            foreach ($course->getModules() as $module) {
                foreach ($module->getLessons() as $lesson) {
                    $completion = $this->completionRepository->findOneBy([
                        'user' => $user,
                        'lesson' => $lesson
                    ]);
                    if (!$completion || !$completion->isCompleted()) {
                        return $lesson;
                    }
                }
            }
        }
        return null;
    }
}
