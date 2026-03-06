<?php

namespace App\Tests\Entity;

use App\Entity\Course;
use PHPUnit\Framework\TestCase;

class CourseVisibilityTest extends TestCase
{
    public function testDefaultVisibilityIsPublic(): void
    {
        $course = new Course();
        $this->assertTrue($course->isIsPublic(), 'By default, a new course should be public.');
    }

    public function testSetVisibility(): void
    {
        $course = new Course();
        $course->setIsPublic(false);
        $this->assertFalse($course->isIsPublic(), 'Visibility should be restrictable.');

        $course->setIsPublic(true);
        $this->assertTrue($course->isIsPublic(), 'Visibility should be toggleable back to public.');
    }
}
