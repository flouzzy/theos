<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\CompletionRepository;
use App\Repository\CourseCompletionRepository;

class AchievementService
{
    public function __construct(
        private CourseCompletionRepository $courseCompletionRepository,
        private CompletionRepository $completionRepository
    ) {
    }

    /**
     * @return array<int, array{icon: string, title: string, date: \DateTimeInterface|null, reached: bool, description?: string|null}>
     */
    public function getAchievements(User $user): array
    {
        $achievements = [];

        $this->addFirstCourseCompletedAchievement($user, $achievements);
        $this->addFiveCoursesCompletedAchievement($user, $achievements);
        $this->addTwentyHoursLearningAchievement($user, $achievements);
        $this->addExistingBadges($user, $achievements);

        return $achievements;
    }

    private function addFirstCourseCompletedAchievement(User $user, array &$achievements): void
    {
        $firstCompletion = $this->courseCompletionRepository->findBy(
            ['user' => $user, 'completed' => true],
            ['createdAt' => 'ASC'],
            1
        );

        if ($firstCompletion) {
            $achievements[] = [
                'icon' => '🎓',
                'title' => 'Premier cours complété',
                'date' => $firstCompletion[0]->getCreatedAt(),
                'reached' => true,
            ];
        }
    }

    private function addFiveCoursesCompletedAchievement(User $user, array &$achievements): void
    {
        $completions = $this->courseCompletionRepository->findBy(
            ['user' => $user, 'completed' => true],
            ['createdAt' => 'ASC']
        );

        if (count($completions) >= 5) {
            $achievements[] = [
                'icon' => '⭐',
                'title' => '5 cours terminés',
                'date' => $completions[4]->getCreatedAt(),
                'reached' => true,
            ];
        }
    }

    private function addTwentyHoursLearningAchievement(User $user, array &$achievements): void
    {
        $totalMinutes = $this->completionRepository->countTotalDurationByUser($user);
        if ($totalMinutes >= 1200) { // 20h * 60m = 1200m
            // On prend la date de la dernière complétion pour simplifier
            $lastCompletion = $this->completionRepository->findBy(
                ['user' => $user, 'completed' => true],
                ['createdAt' => 'DESC'],
                1
            );
            $achievements[] = [
                'icon' => '🔥',
                'title' => '20h d\'apprentissage',
                'date' => $lastCompletion ? $lastCompletion[0]->getCreatedAt() : null,
                'reached' => true,
            ];
        }
    }

    private function addExistingBadges(User $user, array &$achievements): void
    {
        foreach ($user->getBadges() as $badge) {
            $achievements[] = [
                'icon' => '🏆',
                'title' => $badge->getTitle(),
                'date' => $badge->getCreatedAt(),
                'reached' => true,
                'description' => $badge->getDescription(),
            ];
        }
    }
}
