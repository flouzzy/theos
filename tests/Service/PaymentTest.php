<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Enum\PaymentStatusEnum;
use App\Entity\User;
use App\Service\Payment;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PaymentTest extends TestCase
{
    /** @var EntityManagerInterface&MockObject */
    private EntityManagerInterface $entityManager;
    private Payment $payment;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->payment = new Payment($this->entityManager);
    }

    public function testValidatePayment(): void
    {
        $user = new User();
        $user->setPaymentStatus(PaymentStatusEnum::UNPAID);

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($user);

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->payment->validatePayment($user);

        $this->assertSame(PaymentStatusEnum::PAID, $user->getPaymentStatus());
    }
}
