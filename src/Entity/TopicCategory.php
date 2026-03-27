<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\TopicCategoryRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TopicCategoryRepository::class)]
class TopicCategory
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $title;

    #[ORM\ManyToOne(targetEntity: Cohort::class)]
    private ?Cohort $restrictedToCohort = null;

    public function getId(): ?int { return $this->id; }
    public function getTitle(): string { return $this->title; }
    public function setTitle(string $title): static { $this->title = $title; return $this; }
    public function getRestrictedToCohort(): ?Cohort { return $this->restrictedToCohort; }
    public function setRestrictedToCohort(?Cohort $restrictedToCohort): static { $this->restrictedToCohort = $restrictedToCohort; return $this; }
}
