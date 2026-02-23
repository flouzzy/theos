<?php

namespace App\Tests\Service;

use App\Entity\Enum\PaymentStatusEnum;
use App\Entity\User;
use App\Service\Payment;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class PaymentTest extends TestCase
{
    public function testValidatePayment(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $paymentService = new Payment($entityManager);

        $user = new User();
        // Default status is UNPAID
        $this->assertEquals(PaymentStatusEnum::UNPAID, $user->getPaymentStatus());

        $entityManager->expects($this->once())
            ->method('persist')
            ->with($user);

        $entityManager->expects($this->once())
            ->method('flush');

        $paymentService->validatePayment($user);

        $this->assertEquals(PaymentStatusEnum::PAID, $user->getPaymentStatus());
    }
}
