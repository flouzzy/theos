<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;

class LeaderboardService
{
    public function __construct(
        private UserRepository $userRepository
    ) {}

    public function getStudentOfTheWeek(): ?User
    {
        // Simple mock for logic: top XP in last 7 days
        return $this->userRepository->findOneBy([], ['xp' => 'DESC']);
    }
}
