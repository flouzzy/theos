<?php

namespace App\Tests;

use App\Entity\Cohort;
use App\Entity\Evaluation;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class EvaluationTest extends TestCase
{
    public function testEvaluationCreation(): void
    {
        $user = new User();
        $user->setEmail('test.eval@ex.com');

        $cohort = new Cohort();
        $cohort->setTitle('Promo 2026 Test');

        $eval = new Evaluation();
        $eval->setTitle('Contrôle de connaissances SQL');
        $eval->setScore(15);
        $eval->setMaxScore(20);
        $eval->setFeedback('Bon travail !');
        $eval->setUser($user);
        $eval->setCohort($cohort);

        $this->assertEquals('Contrôle de connaissances SQL', $eval->getTitle());
        $this->assertEquals(15, $eval->getScore());
        $this->assertEquals(20, $eval->getMaxScore());
        $this->assertEquals('Bon travail !', $eval->getFeedback());
        $this->assertSame($user, $eval->getUser());
        $this->assertSame($cohort, $eval->getCohort());
    }
}
