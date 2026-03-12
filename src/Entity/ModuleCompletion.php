<?php

namespace App\Entity;

use App\Entity\Trait\DateTimeAble;
use App\Repository\ModuleCompletionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ModuleCompletionRepository::class)]
#[ORM\HasLifecycleCallbacks]
class ModuleCompletion
{
    use DateTimeAble;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'completions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Module $module = null;

    #[ORM\ManyToOne(inversedBy: 'moduleCompletions')]
    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\Column(nullable: true)]
    private ?bool $completed = false;

    #[ORM\Column(nullable: true)]
    private ?float $score = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getModule(): ?Module
    {
        return $this->module;
    }

    public function setModule(?Module $module): static
    {
        $this->module = $module;

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

    public function getScore(): ?float
    {
        return $this->score;
    }

    public function setScore(?float $score): static
    {
        $this->score = $score;

        return $this;
    }

    public function setCompleted(?bool $completed): static
    {
        $this->completed = $completed;

        return $this;
    }
}
