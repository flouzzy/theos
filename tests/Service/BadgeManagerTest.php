<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Badge;
use App\Entity\BadgeType;
use App\Entity\User;
use App\Repository\BadgeRepository;
use App\Repository\BadgeTypeRepository;
use App\Repository\CourseCompletionRepository;
use App\Service\BadgeManager;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class BadgeManagerTest extends TestCase
{
    private EntityManagerInterface&MockObject $entityManager;
    private BadgeRepository&MockObject $badgeRepository;
    private BadgeTypeRepository&MockObject $badgeTypeRepository;
    private CourseCompletionRepository&MockObject $courseCompletionRepository;
    private LoggerInterface&MockObject $logger;
    private BadgeManager $badgeManager;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->badgeRepository = $this->createMock(BadgeRepository::class);
        $this->badgeTypeRepository = $this->createMock(BadgeTypeRepository::class);
        $this->courseCompletionRepository = $this->createMock(CourseCompletionRepository::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->badgeManager = new BadgeManager(
            $this->entityManager,
            $this->badgeRepository,
            $this->badgeTypeRepository,
            $this->courseCompletionRepository,
            $this->logger
        );
    }

    public function testCheckAndAwardBadgesAwardsCourseCompletionBadge(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getBadges')->willReturn(new ArrayCollection());
        $user->method('getCreatedAt')->willReturn(new \DateTimeImmutable('-10 days')); // Not early bird

        $this->courseCompletionRepository->method('findCompletedCourseIdsForUser')->willReturn([1]);

        $badgeType = new BadgeType();
        $badgeType->setCode(BadgeType::CODE_COURSE_COMPLETION);
        $this->badgeTypeRepository->method('findOneBy')->willReturn($badgeType);

        $this->badgeRepository->expects($this->once())
            ->method('findOneBy')
            ->willReturn(null); // No template badge exists, one will be created

        $this->entityManager->expects($this->atLeastOnce())
            ->method('persist')
            ->with($this->isInstanceOf(Badge::class));

        $user->expects($this->once())
            ->method('addBadge')
            ->with($this->isInstanceOf(Badge::class));

        $this->badgeManager->checkAndAwardBadges($user);
    }

    public function testCheckAndAwardBadgesAwardsEarlyBirdBadge(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getBadges')->willReturn(new ArrayCollection());
        $user->method('getCreatedAt')->willReturn(new \DateTimeImmutable('-1 days')); // Early bird

        $this->courseCompletionRepository->method('findCompletedCourseIdsForUser')->willReturn([]);

        $badgeType = new BadgeType();
        $badgeType->setCode(BadgeType::CODE_EARLY_BIRD);
        $this->badgeTypeRepository->method('findOneBy')->willReturn($badgeType);

        $user->expects($this->once())
            ->method('addBadge')
            ->with($this->isInstanceOf(Badge::class));

        $this->badgeManager->checkAndAwardBadges($user);
    }

    public function testCheckAndAwardBadgesDoesNotAwardTwice(): void
    {
        $user = $this->createMock(User::class);
        $badgeType = new BadgeType();
        $badgeType->setCode(BadgeType::CODE_COURSE_COMPLETION);

        $existingBadge = new Badge();
        $existingBadge->setBadgeType($badgeType);
        
        $user->method('getBadges')->willReturn(new ArrayCollection([$existingBadge]));
        $user->method('getCreatedAt')->willReturn(new \DateTimeImmutable('-10 days'));

        $this->courseCompletionRepository->method('findCompletedCourseIdsForUser')->willReturn([1]);
        $this->badgeTypeRepository->method('findOneBy')->willReturn($badgeType);

        $user->expects($this->never())
            ->method('addBadge');

        $this->badgeManager->checkAndAwardBadges($user);
    }
}
