<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ExternalAccountRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ExternalAccountRepository::class)]
class ExternalAccount
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private string $platform = ''; // github, stackoverflow

    #[ORM\Column(length: 255)]
    private string $accountId = '';

    #[ORM\ManyToOne(inversedBy: 'externalAccounts')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    public function getId(): ?int { return $this->id; }
    public function getPlatform(): string { return $this->platform; }
    public function setPlatform(string $platform): static { $this->platform = $platform; return $this; }
    public function getAccountId(): string { return $this->accountId; }
    public function setAccountId(string $accountId): static { $this->accountId = $accountId; return $this; }
    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): static { $this->user = $user; return $this; }
}
