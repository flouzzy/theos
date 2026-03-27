<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;

class RivalryService
{
    public function __construct(
        private UserRepository $userRepository
    ) {}

    public function getRivals(User $user): array
    {
        $xp = $user->getXp();
        return $this->userRepository->createQueryBuilder('u')
            ->where('u.xp BETWEEN :min AND :max')
            ->andWhere('u.id != :id')
            ->setParameter('min', $xp * 0.9)
            ->setParameter('max', $xp * 1.1)
            ->setParameter('id', $user->getId())
            ->setMaxResults(3)
            ->getQuery()
            ->getResult();
    }
}
