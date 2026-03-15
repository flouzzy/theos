<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\PomodoroRoomRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PomodoroRoomRepository::class)]
class PomodoroRoom
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\OneToOne(targetEntity: Conversation::class, cascade: ['persist', 'remove'])]
    private Conversation $conversation;

    public function getId(): ?int { return $this->id; }
    public function getName(): string { return $this->name; }
    public function setName(string $name): static { $this->name = $name; return $this; }
    public function getConversation(): Conversation { return $this->conversation; }
    public function setConversation(Conversation $conversation): static { $this->conversation = $conversation; return $this; }
}
