<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\StudyGroupRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StudyGroupRepository::class)]
class StudyGroup
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\Column(length: 255)]
    private string $topic;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $creator;

    #[ORM\ManyToMany(targetEntity: User::class)]
    private Collection $members;

    #[ORM\Column(options: ['default' => 10])]
    private int $maxMembers = 10;

    public function __construct() { $this->members = new ArrayCollection(); }
    
    public function getMaxMembers(): int { return $this->maxMembers; }
    public function setMaxMembers(int $maxMembers): static { $this->maxMembers = $maxMembers; return $this; }

    public function getId(): ?int { return $this->id; }
    public function getName(): string { return $this->name; }
    public function setName(string $name): static { $this->name = $name; return $this; }
    public function getTopic(): string { return $this->topic; }
    public function setTopic(string $topic): static { $this->topic = $topic; return $this; }
    public function getCreator(): User { return $this->creator; }
    public function setCreator(User $creator): static { $this->creator = $creator; return $this; }
    public function getMembers(): Collection { return $this->members; }
}
