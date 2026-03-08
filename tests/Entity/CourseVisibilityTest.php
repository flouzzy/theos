<?php

namespace App\Tests\Entity;

use App\Entity\Course;
use App\Entity\Enum\CourseVisibilityEnum;
use PHPUnit\Framework\TestCase;

class CourseVisibilityTest extends TestCase
{
    public function testDefaultVisibilityIsPublic(): void
    {
        $course = new Course();
        $this->assertTrue($course->getVisibility() === CourseVisibilityEnum::PUBLIC, 'By default, a new course should be public.');
    }

    public function testSetVisibility(): void
    {
        $course = new Course();
        $course->setVisibility(CourseVisibilityEnum::RESTRICTED);
        $this->assertTrue($course->getVisibility() === CourseVisibilityEnum::RESTRICTED, 'Visibility should be restrictable.');

        $course->setVisibility(CourseVisibilityEnum::PUBLIC);
        $this->assertTrue($course->getVisibility() === CourseVisibilityEnum::PUBLIC, 'Visibility should be toggleable back to public.');
    }
}
