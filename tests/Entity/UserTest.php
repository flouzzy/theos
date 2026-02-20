<?php

namespace App\Tests\Entity;

use App\Entity\Course;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function testSubscribeToCourse(): void
    {
        $user = new User();
        $course = new Course();

        $user->subscribeToCourse($course);

        $this->assertTrue($user->getCourses()->contains($course), 'User should contain the course');
        $this->assertTrue($course->getUsers()->contains($user), 'Course should contain the user');
    }

    public function testSubscribeToCourseIdempotency(): void
    {
        $user = new User();
        $course = new Course();

        $user->subscribeToCourse($course);
        $user->subscribeToCourse($course);

        $this->assertCount(1, $user->getCourses(), 'User should have only 1 course');
        $this->assertCount(1, $course->getUsers(), 'Course should have only 1 user');
    }

    public function testUnsubscribeFromCourse(): void
    {
        $user = new User();
        $course = new Course();

        $user->subscribeToCourse($course);
        $user->unsubscribeFromCourse($course);

        $this->assertFalse($user->getCourses()->contains($course), 'User should not contain the course');
        $this->assertFalse($course->getUsers()->contains($user), 'Course should not contain the user');
    }
}
