<?php

declare(strict_types=1);

namespace App\Tests\EventSubscriber;

use App\Entity\User;
use App\Event\UserVerifiedEvent;
use App\EventSubscriber\RegistrationSubscriber;
use App\Service\BrevoApi;
use PHPUnit\Framework\TestCase;

class RegistrationSubscriberTest extends TestCase
{
    public function testOnUserVerifiedCallsBrevoApi(): void
    {
        $user = $this->createMock(User::class);
        $event = new UserVerifiedEvent($user);
        
        $brevoApi = $this->createMock(BrevoApi::class);
        $brevoApi->expects($this->once())
            ->method('addContactToOnboardedList')
            ->with($user);

        $subscriber = new RegistrationSubscriber($brevoApi);
        $subscriber->onUserVerified($event);
    }
}
