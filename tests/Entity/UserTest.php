<?php

namespace App\Tests\Entity;

use App\Entity\Enum\PaymentStatusEnum;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function testPaymentStatus(): void
    {
        $user = new User();

        // Check default value
        $this->assertSame(PaymentStatusEnum::UNPAID, $user->getPaymentStatus());
        $this->assertFalse($user->isPaid());

        // Set status to PAID
        $user->setPaymentStatus(PaymentStatusEnum::PAID);
        $this->assertSame(PaymentStatusEnum::PAID, $user->getPaymentStatus());
        $this->assertTrue($user->isPaid());

        // Set status to IN_PROGRESS
        $user->setPaymentStatus(PaymentStatusEnum::IN_PROGRESS);
        $this->assertSame(PaymentStatusEnum::IN_PROGRESS, $user->getPaymentStatus());
        $this->assertFalse($user->isPaid());
    }
}
