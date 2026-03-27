<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\TeamRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TeamRepository::class)]
class Team
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\Column(length: 255)]
    private string $company;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $owner;

    #[ORM\ManyToOne(targetEntity: Cohort::class)]
    private ?Cohort $cohort = null;

    #[ORM\Column(options: ['default' => 0])]
    private int $completedLessons = 0;

    /**
     * @var Collection<int, User>
     */
    #[ORM\ManyToMany(targetEntity: User::class, inversedBy: 'teams')]
    private Collection $members;

    public function __construct()
    {
        $this->members = new ArrayCollection();
    }

    public function getCohort(): ?Cohort { return $this->cohort; }
    public function setCohort(?Cohort $cohort): static { $this->cohort = $cohort; return $this; }
    public function getCompletedLessons(): int { return $this->completedLessons; }
    public function setCompletedLessons(int $completedLessons): static { $this->completedLessons = $completedLessons; return $this; }

    public function getId(): ?int { return $this->id; }
    public function getName(): string { return $this->name; }
    public function setName(string $name): static { $this->name = $name; return $this; }
    public function getCompany(): string { return $this->company; }
    public function setCompany(string $company): static { $this->company = $company; return $this; }
    public function getOwner(): User { return $this->owner; }
    public function setOwner(User $owner): static { $this->owner = $owner; return $this; }

    /**
     * @return Collection<int, User>
     */
    public function getMembers(): Collection
    {
        return $this->members;
    }

    public function addMember(User $user): static
    {
        if (!$this->members->contains($user)) {
            $this->members->add($user);
        }

        return $this;
    }

    public function removeMember(User $user): static
    {
        $this->members->removeElement($user);

        return $this;
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
