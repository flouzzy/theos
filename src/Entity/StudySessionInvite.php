<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\StudySessionInviteRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StudySessionInviteRepository::class)]
class StudySessionInvite
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 64, unique: true)]
    private string $inviteToken;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $sender;

    #[ORM\Column(options: ['default' => false])]
    private bool $isAccepted = false;

    public function __construct()
    {
        $this->inviteToken = bin2hex(random_bytes(32));
    }

    public function getId(): ?int { return $this->id; }
    public function getInviteToken(): string { return $this->inviteToken; }
    public function getSender(): User { return $this->sender; }
    public function setSender(User $sender): static { $this->sender = $sender; return $this; }
    public function isAccepted(): bool { return $this->isAccepted; }
    public function setIsAccepted(bool $isAccepted): static { $this->isAccepted = $isAccepted; return $this; }
}
