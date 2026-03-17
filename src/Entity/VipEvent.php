<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\VipEventRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: VipEventRepository::class)]
class VipEvent
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $date;

    #[ORM\ManyToOne(targetEntity: Cohort::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Cohort $cohort;

    #[ORM\Column]
    private int $minRank = 10;

    public function getId(): ?int { return $this->id; }
    public function getName(): string { return $this->name; }
    public function setName(string $name): static { $this->name = $name; return $this; }
    public function getDate(): \DateTimeImmutable { return $this->date; }
    public function setDate(\DateTimeImmutable $date): static { $this->date = $date; return $this; }
    public function getCohort(): Cohort { return $this->cohort; }
    public function setCohort(Cohort $cohort): static { $this->cohort = $cohort; return $this; }
    public function getMinRank(): int { return $this->minRank; }
    public function setMinRank(int $minRank): static { $this->minRank = $minRank; return $this; }
}
