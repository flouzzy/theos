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

    public function testDateTimeAbleTrait(): void
    {
        $courseCompletion = new CourseCompletion();
        
        $this->assertNull($courseCompletion->getCreatedAt());
        $this->assertNull($courseCompletion->getUpdatedAt());

        $courseCompletion->setDateTimeValue();

        $this->assertInstanceOf(\DateTimeImmutable::class, $courseCompletion->getCreatedAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $courseCompletion->getUpdatedAt());
        
        $createdAt = $courseCompletion->getCreatedAt();
        $updatedAt = $courseCompletion->getUpdatedAt();

        // Wait a bit to ensure updatedAt changes if we call it again
        usleep(1000);
        $courseCompletion->setDateTimeValue();

        $this->assertSame($createdAt, $courseCompletion->getCreatedAt());
        $this->assertNotSame($updatedAt, $courseCompletion->getUpdatedAt());
    }
}
