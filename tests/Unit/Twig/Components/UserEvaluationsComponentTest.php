<?php

namespace App\Tests\Unit\Twig\Components;

use App\Entity\Completion;
use App\Entity\Evaluation;
use App\Entity\ModuleCompletion;
use App\Entity\User;
use App\Repository\CompletionRepository;
use App\Repository\EvaluationRepository;
use App\Repository\ModuleCompletionRepository;
use App\Twig\Components\UserEvaluationsComponent;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;

class UserEvaluationsComponentTest extends TestCase
{
    public function testDateSortingWithMissingDates(): void
    {
        $securityMock = $this->createMock(Security::class);
        $userMock = $this->createMock(User::class);
        $securityMock->method('getUser')->willReturn($userMock);
        $userMock->method('getCurrentCohort')->willReturn(null);

        // Evaluation 1: Middle date
        $eval1 = $this->createMock(Evaluation::class);
        $eval1->method('getCreatedAt')->willReturn(new \DateTimeImmutable('2023-05-01'));
        $eval1->method('getMaxScore')->willReturn(20.0);
        $eval1->method('getScore')->willReturn(15.0);

        // Evaluation 2: Newest date
        $eval2 = $this->createMock(Evaluation::class);
        $eval2->method('getCreatedAt')->willReturn(new \DateTimeImmutable('2023-06-01'));
        $eval2->method('getMaxScore')->willReturn(20.0);
        $eval2->method('getScore')->willReturn(18.0);

        // Evaluation 3: Missing date (null)
        $eval3 = $this->createMock(Evaluation::class);
        $eval3->method('getCreatedAt')->willReturn(null);
        $eval3->method('getMaxScore')->willReturn(20.0);
        $eval3->method('getScore')->willReturn(10.0);

        // Evaluation 4: Oldest date
        $eval4 = $this->createMock(Evaluation::class);
        $eval4->method('getCreatedAt')->willReturn(new \DateTimeImmutable('2022-01-01'));
        $eval4->method('getMaxScore')->willReturn(20.0);
        $eval4->method('getScore')->willReturn(12.0);

        $evalRepoMock = $this->createMock(EvaluationRepository::class);
        // Return them out of order
        $evalRepoMock->method('findBy')->willReturn([$eval1, $eval4, $eval2, $eval3]);

        $mcRepoMock = $this->createMock(ModuleCompletionRepository::class);
        $mcRepoMock->method('findWithScoreByUser')->willReturn([]);

        $completionRepoMock = $this->createMock(CompletionRepository::class);
        $completionRepoMock->method('findWithScoreByUser')->willReturn([]);

        $component = new UserEvaluationsComponent(
            $securityMock,
            $evalRepoMock,
            $mcRepoMock,
            $completionRepoMock
        );

        $result = $component->getEvaluations();

        // Should be sorted by date desc.
        // Missing date (null) becomes '@0' (1970-01-01), so it should be last.
        // Expected order:
        // 1. 2023-06-01 (eval2)
        // 2. 2023-05-01 (eval1)
        // 3. 2022-01-01 (eval4)
        // 4. Missing date (eval3)

        $this->assertCount(4, $result);

        $this->assertEquals(new \DateTimeImmutable('2023-06-01'), $result[0]['date']);
        $this->assertEquals(new \DateTimeImmutable('2023-05-01'), $result[1]['date']);
        $this->assertEquals(new \DateTimeImmutable('2022-01-01'), $result[2]['date']);
        $this->assertNull($result[3]['date']);

        $this->assertEquals(18.0, $result[0]['score']);
        $this->assertEquals(15.0, $result[1]['score']);
        $this->assertEquals(12.0, $result[2]['score']);
        $this->assertEquals(10.0, $result[3]['score']);
    }
}
