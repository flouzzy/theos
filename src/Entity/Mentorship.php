<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\MentorshipRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MentorshipRepository::class)]
class Mentorship
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $mentor;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $mentee;

    #[ORM\Column(length: 50)]
    private string $status = 'pending'; // pending, active, completed

    public function getId(): ?int { return $this->id; }
    public function getMentor(): User { return $this->mentor; }
    public function setMentor(User $mentor): static { $this->mentor = $mentor; return $this; }
    public function getMentee(): User { return $this->mentee; }
    public function setMentee(User $mentee): static { $this->mentee = $mentee; return $this; }
    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): static { $this->status = $status; return $this; }
}
