<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ReferralRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReferralRepository::class)]
class Referral
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $referrer;

    #[ORM\OneToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $referee;

    #[ORM\Column(options: ['default' => false])]
    private bool $isCompleted = false;

    public function getId(): ?int { return $this->id; }
    public function getReferrer(): User { return $this->referrer; }
    public function setReferrer(User $referrer): static { $this->referrer = $referrer; return $this; }
    public function getReferee(): User { return $this->referee; }
    public function setReferee(User $referee): static { $this->referee = $referee; return $this; }
    public function isCompleted(): bool { return $this->isCompleted; }
    public function setIsCompleted(bool $isCompleted): static { $this->isCompleted = $isCompleted; return $this; }
}
