<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Trait\DateTimeAble;
use App\Repository\RaffleEntryRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RaffleEntryRepository::class)]
#[ORM\HasLifecycleCallbacks]
class RaffleEntry
{
    use DateTimeAble;

    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\Column]
    private \DateTimeImmutable $raffleDate;

    public function __construct(User $user)
    {
        $this->user = $user;
        $this->raffleDate = new \DateTimeImmutable('today');
    }

    public function getId(): ?int { return $this->id; }
    public function getUser(): User { return $this->user; }
}
