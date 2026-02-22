<?php

namespace App\Tests\Entity;

use App\Entity\User;
use PHPUnit\Framework\TestCase;

class UserXpTest extends TestCase
{
    public function testXp(): void
    {
        $user = new User();
        $this->assertEquals(0, $user->getXp());

        $user->setXp(10);
        $this->assertEquals(10, $user->getXp());

        $user->addXp(5);
        $this->assertEquals(15, $user->getXp());
    }

    public function testStreak(): void
    {
        $user = new User();
        $this->assertEquals(0, $user->getStreak());

        $user->setStreak(5);
        $this->assertEquals(5, $user->getStreak());
    }

    public function testLastStreakDate(): void
    {
        $user = new User();
        $this->assertNull($user->getLastStreakDate());

        $date = new \DateTimeImmutable();
        $user->setLastStreakDate($date);
        $this->assertSame($date, $user->getLastStreakDate());
    }
}
