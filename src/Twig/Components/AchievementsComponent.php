<?php

namespace App\Twig\Components;

use App\Entity\User;
use App\Service\AchievementService;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
class AchievementsComponent
{
    use DefaultActionTrait;

    public function __construct(
        private Security $security,
        private AchievementService $achievementService
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

        return $this->achievementService->getAchievements($user);
    }
}
