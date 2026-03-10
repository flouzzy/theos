<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Badge;
use App\Entity\BadgeType;
use App\Entity\User;
use App\Repository\BadgeRepository;
use App\Repository\BadgeTypeRepository;
use App\Repository\CourseCompletionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class BadgeManager
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private BadgeRepository $badgeRepository,
        private BadgeTypeRepository $badgeTypeRepository,
        private CourseCompletionRepository $courseCompletionRepository,
        private LoggerInterface $logger
    ) {
    }

    public function checkAndAwardBadges(User $user): void
    {
        $this->checkCourseCompletionBadges($user);
        $this->checkEarlyBirdBadge($user);
    }

    private function checkCourseCompletionBadges(User $user): void
    {
        $completedCourseIds = $this->courseCompletionRepository->findCompletedCourseIdsForUser($user);
        $count = count($completedCourseIds);

        if ($count >= 1) {
            $this->awardBadgeIfPossible($user, BadgeType::CODE_COURSE_COMPLETION, 'Premier pas', "Félicitations pour avoir terminé votre premier cours !");
        }
        
        // We could add more milestones here, e.g. 5 courses, 10 courses, etc.
    }

    private function checkEarlyBirdBadge(User $user): void
    {
        // Example logic: awarded if the user registered within the last 7 days (this is just a placeholder logic)
        // Or if they completed a course within X days of registration
        $createdAt = $user->getCreatedAt();
        if ($createdAt && $createdAt > new \DateTimeImmutable('-7 days')) {
            $this->awardBadgeIfPossible($user, BadgeType::CODE_EARLY_BIRD, 'Lève-tôt', "Vous avez rejoint l'académie récemment et vous êtes déjà actif !");
        }
    }

    private function awardBadgeIfPossible(User $user, string $badgeTypeCode, string $defaultTitle, string $defaultDescription): void
    {
        $badgeType = $this->badgeTypeRepository->findOneBy(['code' => $badgeTypeCode]);
        if (!$badgeType) {
            $this->logger->warning(sprintf('BadgeType with code %s not found.', $badgeTypeCode));
            return;
        }

        // Check if user already has a badge of this type
        // Note: In some systems, a user might have multiple badges of the same type but different tiers.
        // For simplicity, we check if the user has ANY badge associated with this BadgeType.
        foreach ($user->getBadges() as $existingBadge) {
            if ($existingBadge->getBadgeType() === $badgeType) {
                return;
            }
        }

        // Create the badge or associate the existing template badge
        // Usually, there is a set of "Template" badges. 
        // If Badge entity is intended to be unique per user-award, we create it.
        // If Badge is a template, we just add the user to the Badge's users collection.
        
        // Based on the Badge entity structure (ManyToMany with User), Badge seems to be a template.
        $badge = $this->badgeRepository->findOneBy(['badgeType' => $badgeType, 'title' => $defaultTitle]);
        
        if (!$badge) {
            $badge = new Badge();
            $badge->setTitle($defaultTitle);
            $badge->setDescription($defaultDescription);
            $badge->setBadgeType($badgeType);
            $this->entityManager->persist($badge);
        }

        $user->addBadge($badge);
        $this->entityManager->flush();
        
        $this->logger->info(sprintf('Awarded badge %s to user %s', $defaultTitle, $user->getEmail()));
    }
}
