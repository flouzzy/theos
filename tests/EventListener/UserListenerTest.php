<?php

namespace App\Tests\EventListener;

use App\Entity\User;
use App\EventListener\UserListener;
use App\Repository\UserRepository;
use App\Service\BrevoApi;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class UserListenerTest extends TestCase
{
    private UserRepository&MockObject $userRepository;
    private BrevoApi&MockObject $brevoApi;
    private UserListener $listener;

    protected function setUp(): void
    {
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->brevoApi = $this->createMock(BrevoApi::class);
        $this->listener = new UserListener($this->userRepository, $this->brevoApi);
    }

    public function testPostPersist(): void
    {
        $user = $this->createMock(User::class);

        $this->brevoApi->expects($this->once())
            ->method('addOrUpdateContact')
            ->with($user);

        $em = $this->createMock(\Doctrine\ORM\EntityManagerInterface::class);
        $event = new PostPersistEventArgs($user, $em);

        $this->listener->postPersist($user, $event);
    }

    public function testPostUpdate(): void
    {
        $user = $this->createMock(User::class);

        $this->brevoApi->expects($this->once())
            ->method('addOrUpdateContact')
            ->with($user);

        $em = $this->createMock(\Doctrine\ORM\EntityManagerInterface::class);
        $event = new PostUpdateEventArgs($user, $em);

        $this->listener->postUpdate($user, $event);
    }
}
