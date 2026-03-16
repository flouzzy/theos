<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\CohortChallengeRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CohortChallengeRepository::class)]
class CohortChallenge
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $title;

    #[ORM\Column]
    private int $targetValue;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $startDate;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $endDate;

    #[ORM\ManyToOne(targetEntity: Cohort::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Cohort $cohort;

    public function getId(): ?int { return $this->id; }
    public function getTitle(): string { return $this->title; }
    public function setTitle(string $title): static { $this->title = $title; return $this; }
    public function getTargetValue(): int { return $this->targetValue; }
    public function setTargetValue(int $targetValue): static { $this->targetValue = $targetValue; return $this; }
    public function getStartDate(): \DateTimeImmutable { return $this->startDate; }
    public function setStartDate(\DateTimeImmutable $startDate): static { $this->startDate = $startDate; return $this; }
    public function getEndDate(): \DateTimeImmutable { return $this->endDate; }
    public function setEndDate(\DateTimeImmutable $endDate): static { $this->endDate = $endDate; return $this; }
    public function getCohort(): Cohort { return $this->cohort; }
    public function setCohort(Cohort $cohort): static { $this->cohort = $cohort; return $this; }
}
