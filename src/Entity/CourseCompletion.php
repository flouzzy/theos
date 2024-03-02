<?php

namespace App\Entity;

use App\Repository\CourseCompletionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CourseCompletionRepository::class)]
class CourseCompletion
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'completions')]
    private ?Course $course = null;

    #[ORM\ManyToOne(inversedBy: 'courseCompletions')]
    private ?User $user = null;

    #[ORM\Column(nullable: true)]
    private ?bool $completed = false;

    #[ORM\Column(type: Types::FLOAT, nullable: true)]
    private ?int $percentage = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCourse(): ?Course
    {
        return $this->course;
    }

    public function setCourse(?Course $course): static
    {
        $this->course = $course;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function isCompleted(): ?bool
    {
        return $this->completed;
    }

    public function setCompleted(?bool $completed): static
    {
        $this->completed = $completed;

        return $this;
    }

    public function getPercentage(): ?int
    {
        return $this->percentage;
    }

    public function setPercentage(?int $percentage): static
    {
        $this->percentage = $percentage;

        return $this;
    }
}
