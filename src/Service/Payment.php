<?php

namespace App\Service;

use App\Entity\Enum\PaymentStatusEnum;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class Payment
{
    public function __construct(private EntityManagerInterface $entityManager) {}
    public function validatePayment(User $user): void
    {
        $user->setPaymentStatus(PaymentStatusEnum::PAID);
        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }
}
