<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ProofOfWorkRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProofOfWorkRepository::class)]
class ProofOfWork
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $title;

    #[ORM\Column(length: 255)]
    private string $link;

    #[ORM\Column(length: 64, unique: true)]
    private string $verificationHash;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    public function __construct() { $this->verificationHash = bin2hex(random_bytes(32)); }

    public function getId(): ?int { return $this->id; }
    public function getTitle(): string { return $this->title; }
    public function setTitle(string $title): static { $this->title = $title; return $this; }
    public function getLink(): string { return $this->link; }
    public function setLink(string $link): static { $this->link = $link; return $this; }
    public function getVerificationHash(): string { return $this->verificationHash; }
    public function getUser(): User { return $this->user; }
    public function setUser(User $user): static { $this->user = $user; return $this; }
}
