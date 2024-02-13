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

    // public function prePersist(User $user, PrePersistEventArgs $event): void {}

    public function postPersist(User $user, PostPersistEventArgs $event): void
    {
        // $entity = $event->getObject();
        $this->brevoApi->addOrUpdateContact($user);
    }


    // the entity listener methods receive two arguments:
    // the entity instance and the lifecycle event
    public function postUpdate(User $user, PostUpdateEventArgs $event): void
    {
        // ... do something to notify the changes
        $this->brevoApi->addOrUpdateContact($user);
    }
}
