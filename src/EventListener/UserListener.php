<?php

namespace App\EventListener;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\BrevoApi;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Events;

#[AsEntityListener(event: Events::postUpdate, method: 'postUpdate', entity: User::class)]
#[AsEntityListener(event: Events::postPersist, method: 'postPersist', entity: User::class)]
class UserListener
{

    public function __construct(private UserRepository $userRepository, private BrevoApi $brevoApi)
    {
    }

    public function postPersist(User $user, PostPersistEventArgs $event): void
    {
        $this->brevoApi->addOrUpdateContact($user);
    }

    public function postUpdate(User $user, PostUpdateEventArgs $event): void
    {
        $this->brevoApi->addOrUpdateContact($user);
    }
}
