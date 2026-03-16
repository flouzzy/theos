<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\DailyWinRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DailyWinRepository::class)]
class DailyWin
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\Column(type: Types::TEXT)]
    private string $summary;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $date;

    public function getId(): ?int { return $this->id; }
    public function getUser(): User { return $this->user; }
    public function setUser(User $user): static { $this->user = $user; return $this; }
    public function getSummary(): string { return $this->summary; }
    public function setSummary(string $summary): static { $this->summary = $summary; return $this; }
    public function getDate(): \DateTimeImmutable { return $this->date; }
    public function setDate(\DateTimeImmutable $date): static { $this->date = $date; return $this; }
}
