<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use App\Repository\CourseCompletionRepository;
use App\Repository\BadgeRepository;

class YearInReviewService
{
    public function __construct(
        private CourseCompletionRepository $completionRepo,
        private BadgeRepository $badgeRepo
    ) {}

    public function getYearlyStats(User $user, int $year): array
    {
        return [
            'totalCourses' => $this->completionRepo->count(['user' => $user]), // Simplifié pour l'exemple
            'totalBadges' => $this->badgeRepo->count(['user' => $user]),
            'topSkill' => 'PHP',
        ];
    }
}
