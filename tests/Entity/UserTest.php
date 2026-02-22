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

    public function testXpMethods(): void
    {
        $user = new User();

        $this->assertEquals(0, $user->getXp(), 'Initial XP should be 0');

        $user->setXp(100);
        $this->assertEquals(100, $user->getXp(), 'XP should be set to 100');

        $user->addXp(50);
        $this->assertEquals(150, $user->getXp(), 'XP should be 150 after adding 50');
    }

    public function testStreakMethods(): void
    {
        $user = new User();

        $this->assertEquals(0, $user->getStreak(), 'Initial streak should be 0');

        $user->setStreak(5);
        $this->assertEquals(5, $user->getStreak(), 'Streak should be set to 5');
    }

    public function testLastStreakDateMethods(): void
    {
        $user = new User();

        $this->assertNull($user->getLastStreakDate(), 'Initial last streak date should be null');

        $date = new \DateTimeImmutable();
        $user->setLastStreakDate($date);
        $this->assertEquals($date, $user->getLastStreakDate(), 'Last streak date should be set correctly');
    }
}
