<?php

namespace App\Tests\Entity;

use App\Entity\Course;
use App\Entity\CourseCompletion;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class CourseCompletionTest extends TestCase
{
    public function testCourseCompletionEntity(): void
    {
        $courseCompletion = new CourseCompletion();

        $user = new User();
        $course = new Course();

        $courseCompletion->setUser($user);
        $this->assertSame($user, $courseCompletion->getUser());

        $courseCompletion->setCourse($course);
        $this->assertSame($course, $courseCompletion->getCourse());

        $courseCompletion->setCompleted(true);
        $this->assertTrue($courseCompletion->isCompleted());

        $courseCompletion->setCompleted(false);
        $this->assertFalse($courseCompletion->isCompleted());
    }
}
