<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use App\Repository\CompletionRepository;
use App\Repository\XpTransactionRepository;

class YearInReviewService
{
    public function __construct(
        private CompletionRepository $completionRepository,
        private XpTransactionRepository $xpTransactionRepository
    ) {}

    public function getYearlyStats(User $user, int $year): array
    {
        $start = new \DateTimeImmutable("$year-01-01 00:00:00");
        $end = new \DateTimeImmutable("$year-12-31 23:59:59");

        return [
            'xp' => $this->xpTransactionRepository->findXpGainedByUserBetween($user, $start, $end),
            'completions' => $this->completionRepository->countByUserBetween($user, $start, $end),
            'badges' => $user->getBadges()->count(),
        ];
    }
}
