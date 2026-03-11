<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Cohort;
use App\Entity\Course;
use App\Entity\Lesson;
use App\Entity\Module;
use App\Entity\User;
use App\Entity\Completion;
use App\Repository\CompletionRepository;
use App\Repository\EvaluationRepository;
use App\Service\EngagementAnalyzer;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class EngagementAnalyzerTest extends TestCase
{
    public function testCalculateRiskScore(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getLastConnectionAt')->willReturn(new \DateTimeImmutable('-10 days'));
        
        $cohort = $this->createMock(Cohort::class);
        
        $completionRepo = $this->createMock(CompletionRepository::class);
        $completionRepo->method('countByUserAndCohort')->willReturn(2); // 2/20 = 10% progress

        $evaluationRepo = $this->createMock(EvaluationRepository::class);
        $evaluationRepo->method('findBy')->willReturn([]); // No evaluations

        $entityManager = $this->createMock(EntityManagerInterface::class);

        $analyzer = new EngagementAnalyzer($completionRepo, $evaluationRepo, $entityManager);
        $score = $analyzer->calculateRiskScore($user, $cohort);

        // Inactivity: 7+ days -> 20 + (10-7)*5 = 35. Max 40.
        // Acad perf: 0 -> 0.
        // Progress: 10% < 20% -> 30.
        // Total: ~65.
        $this->assertGreaterThan(50, $score);
    }

    public function testGetContentEfficacy(): void
    {
        $lesson = $this->createMock(Lesson::class);
        $lesson->method('getTitle')->willReturn('Test Lesson');
        
        $module = $this->createMock(Module::class);
        $module->method('getTitle')->willReturn('Test Module');
        $module->method('getLessons')->willReturn(new ArrayCollection([$lesson]));
        
        $course = $this->createMock(Course::class);
        $course->method('getModules')->willReturn(new ArrayCollection([$module]));

        $completion1 = $this->createMock(Completion::class);
        $completion1->method('getScore')->willReturn(18.0);
        
        $completion2 = $this->createMock(Completion::class);
        $completion2->method('getScore')->willReturn(16.0);

        $completionRepo = $this->createMock(CompletionRepository::class);
        $completionRepo->method('findBy')->willReturn([$completion1, $completion2]);

        $evaluationRepo = $this->createMock(EvaluationRepository::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);

        $analyzer = new EngagementAnalyzer($completionRepo, $evaluationRepo, $entityManager);
        $efficacy = $analyzer->getContentEfficacy($course);

        $this->assertCount(1, $efficacy);
        $this->assertEquals(17.0, $efficacy[0]['avgScore']);
        $this->assertEquals('Excellent', $efficacy[0]['status']);
    }
}
