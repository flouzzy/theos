<?php

namespace App\Tests\EventSubscriber;

use App\Entity\User;
use App\Event\UserUpdatedEvent;
use App\EventSubscriber\UserUpdatedSubscriber;
use App\Service\BrevoApi;
use PHPUnit\Framework\TestCase;

class UserUpdatedSubscriberTest extends TestCase
{
    public function testOnUserUpdated()
    {
        $user = new User();
        $user->setFirstname('John');
        $user->setLastname('Doe');

        $brevoApi = $this->createMock(BrevoApi::class);
        $brevoApi->expects($this->once())
            ->method('addOrUpdateContact')
            ->with($user);

        $subscriber = new UserUpdatedSubscriber($brevoApi);
        $event = new UserUpdatedEvent($user);

        $subscriber->onUserUpdated($event);

        $this->assertEquals('John Doe', $user->getFullname());
    }
}
