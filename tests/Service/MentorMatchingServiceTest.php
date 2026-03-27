<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Skill;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\MentorMatchingService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class MentorMatchingServiceTest extends TestCase
{
    private UserRepository&MockObject $userRepository;
    private MentorMatchingService $mentorMatchingService;

    protected function setUp(): void
    {
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->mentorMatchingService = new MentorMatchingService($this->userRepository);
    }

    public function testFindPotentialMentorsSortsBySkillIntersection(): void
    {
        $skill1 = new Skill();
        $skill1->setName('PHP');

        $skill2 = new Skill();
        $skill2->setName('Symfony');

        $skill3 = new Skill();
        $skill3->setName('JavaScript');

        $mentee = new User();
        $mentee->addSkill($skill1);
        $mentee->addSkill($skill2);

        // Mentor 1 has 0 overlapping skills
        $mentor1 = new User();
        $mentor1->addSkill($skill3);

        // Mentor 2 has 2 overlapping skills
        $mentor2 = new User();
        $mentor2->addSkill($skill1);
        $mentor2->addSkill($skill2);

        // Mentor 3 has 1 overlapping skill
        $mentor3 = new User();
        $mentor3->addSkill($skill1);

        $this->userRepository->expects($this->once())
            ->method('findBy')
            ->with(['isMentor' => true])
            ->willReturn([$mentor1, $mentor2, $mentor3]);

        $result = $this->mentorMatchingService->findPotentialMentors($mentee, 3);

        $this->assertCount(3, $result);
        $this->assertSame($mentor2, $result[0]); // 2 overlapping
        $this->assertSame($mentor3, $result[1]); // 1 overlapping
        $this->assertSame($mentor1, $result[2]); // 0 overlapping
    }

    public function testFindPotentialMentorsRespectsLimit(): void
    {
        $mentee = new User();

        $mentors = [];
        for ($i = 0; $i < 5; $i++) {
            $mentors[] = new User();
        }

        $this->userRepository->expects($this->once())
            ->method('findBy')
            ->with(['isMentor' => true])
            ->willReturn($mentors);

        $result = $this->mentorMatchingService->findPotentialMentors($mentee, 2);

        $this->assertCount(2, $result);
    }

    public function testFindPotentialMentorsWithEmptyMentors(): void
    {
        $mentee = new User();

        $this->userRepository->expects($this->once())
            ->method('findBy')
            ->with(['isMentor' => true])
            ->willReturn([]);

        $result = $this->mentorMatchingService->findPotentialMentors($mentee);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }
}
