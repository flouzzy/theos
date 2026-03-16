<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\StudyBuddyRequestRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StudyBuddyRequestRepository::class)]
class StudyBuddyRequest
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $sender;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $recipient;

    #[ORM\Column(length: 20)]
    private string $status = 'pending'; // pending, accepted, declined

    public function getId(): ?int { return $this->id; }
    public function getSender(): User { return $this->sender; }
    public function setSender(User $sender): static { $this->sender = $sender; return $this; }
    public function getRecipient(): User { return $this->recipient; }
    public function setRecipient(User $recipient): static { $this->recipient = $recipient; return $this; }
    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): static { $this->status = $status; return $this; }
}
