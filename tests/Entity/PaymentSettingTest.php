<?php

namespace App\Tests\Entity;

use App\Entity\PaymentSetting;
use PHPUnit\Framework\TestCase;

class PaymentSettingTest extends TestCase
{
    public function testPaymentSettingEntity(): void
    {
        $paymentSetting = new PaymentSetting();

        $paymentSetting->setRib('FR76 1234 5678 9012 3456 7890 123');
        $this->assertSame('FR76 1234 5678 9012 3456 7890 123', $paymentSetting->getRib());

        $paymentSetting->setCheckOrder('My Company');
        $this->assertSame('My Company', $paymentSetting->getCheckOrder());

        $paymentSetting->setReceptionAddress('123 Main St');
        $this->assertSame('123 Main St', $paymentSetting->getReceptionAddress());

        $paymentSetting->setNote('Pay quickly');
        $this->assertSame('Pay quickly', $paymentSetting->getNote());

        $paymentSetting->setPricing(100);
        $this->assertSame(100, $paymentSetting->getPricing());
    }

    public function testDateTimeAbleTrait(): void
    {
        $paymentSetting = new PaymentSetting();
        
        $this->assertNull($paymentSetting->getCreatedAt());
        $this->assertNull($paymentSetting->getUpdatedAt());

        $paymentSetting->setDateTimeValue();

        $this->assertInstanceOf(\DateTimeImmutable::class, $paymentSetting->getCreatedAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $paymentSetting->getUpdatedAt());
        
        $createdAt = $paymentSetting->getCreatedAt();
        $updatedAt = $paymentSetting->getUpdatedAt();

        // Wait a bit to ensure updatedAt changes if we call it again
        usleep(1000);
        $paymentSetting->setDateTimeValue();

        $this->assertSame($createdAt, $paymentSetting->getCreatedAt());
        $this->assertNotSame($updatedAt, $paymentSetting->getUpdatedAt());
    }
}
