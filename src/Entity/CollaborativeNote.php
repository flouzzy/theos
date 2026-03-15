<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\CollaborativeNoteRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CollaborativeNoteRepository::class)]
class CollaborativeNote
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $title;

    #[ORM\Column(length: 255)]
    private string $url;

    #[ORM\ManyToOne(targetEntity: Cohort::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Cohort $cohort;

    public function getId(): ?int { return $this->id; }
    public function getTitle(): string { return $this->title; }
    public function setTitle(string $title): static { $this->title = $title; return $this; }
    public function getUrl(): string { return $this->url; }
    public function setUrl(string $url): static { $this->url = $url; return $this; }
    public function getCohort(): Cohort { return $this->cohort; }
    public function setCohort(Cohort $cohort): static { $this->cohort = $cohort; return $this; }
}
