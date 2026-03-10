<?php

namespace App\Twig\Components;

use App\Entity\User;
use App\Repository\CompletionRepository;
use App\Repository\CourseCompletionRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
class AchievementsComponent
{
    use DefaultActionTrait;

    public function __construct(
        private Security $security,
        private CourseCompletionRepository $courseCompletionRepository,
        private CompletionRepository $completionRepository
    ) {
    }

    /**
     * @return array<int, array{icon: string, title: string, date: \DateTimeInterface|null, reached: bool, description?: string|null}>
     */
    public function getAchievements(): array
    {
        /** @var User $user */
        $user = $this->security->getUser();
        if (!$user) {
            return [];
        }

        $achievements = [];

        // 1. Premier cours complété
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

        // 2. 5 cours terminés
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

        // 3. 20h d'apprentissage
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

        // 4. Badges existants en base de données
        foreach ($user->getBadges() as $badge) {
            $achievements[] = [
                'icon' => '🏆',
                'title' => $badge->getTitle(),
                'date' => $badge->getCreatedAt(),
                'reached' => true,
                'description' => $badge->getDescription(),
            ];
        }

        return $achievements;
    }
}
