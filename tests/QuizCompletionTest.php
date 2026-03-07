<?php

namespace App\Tests;

use App\Entity\Completion;
use App\Entity\Lesson;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class QuizCompletionTest extends TestCase
{
    public function testQuizCompletionWithScore(): void
    {
        $user = new User();
        $user->setEmail('student@example.com');

        $lesson = new Lesson();
        $lesson->setTitle('Introduction PHP');

        $completion = new Completion();
        $completion->setUser($user);
        $completion->setLesson($lesson);
        
        // Simuler la passage d'un quiz
        $completion->setCompleted(true);
        $completion->setScore(85.5);

        $this->assertTrue($completion->isCompleted());
        $this->assertEquals(85.5, $completion->getScore());
        $this->assertSame($user, $completion->getUser());
        $this->assertSame($lesson, $completion->getLesson());
    }
}
