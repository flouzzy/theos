<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;

class MentorMatchingService
{
    public function __construct(
        private UserRepository $userRepository
    ) {}

    public function findPotentialMentors(User $mentee, int $limit = 5): array
    {
        $mentors = $this->userRepository->findBy(['isMentor' => true]);
        
        // Simple matching based on skill intersection count
        usort($mentors, function(User $a, User $b) use ($mentee) {
            $scoreA = $this->calculateCompatibility($mentee, $a);
            $scoreB = $this->calculateCompatibility($mentee, $b);
            return $scoreB <=> $scoreA;
        });

        return array_slice($mentors, 0, $limit);
    }

    private function calculateCompatibility(User $mentee, User $mentor): int
    {
        $menteeSkills = $mentee->getSkills();
        $mentorSkills = $mentor->getSkills();
        
        $intersection = 0;
        foreach ($menteeSkills as $skill) {
            if ($mentorSkills->contains($skill)) {
                $intersection++;
            }
        }
        return $intersection;
    }
}
