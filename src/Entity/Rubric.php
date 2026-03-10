<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\RubricRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RubricRepository::class)]
class Rubric
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\OneToOne(mappedBy: 'rubricEntity', targetEntity: Assignment::class)]
    private ?Assignment $assignment = null;

    #[ORM\OneToMany(mappedBy: 'rubric', targetEntity: RubricCriterion::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $criteria;

    public function __construct()
    {
        $this->criteria = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getAssignment(): ?Assignment
    {
        return $this->assignment;
    }

    public function setAssignment(?Assignment $assignment): static
    {
        $this->assignment = $assignment;

        return $this;
    }

    /**
     * @return Collection<int, RubricCriterion>
     */
    public function getCriteria(): Collection
    {
        return $this->criteria;
    }

    public function addCriterion(RubricCriterion $criterion): static
    {
        if (!$this->criteria->contains($criterion)) {
            $this->criteria->add($criterion);
            $criterion->setRubric($this);
        }

        return $this;
    }

    public function removeCriterion(RubricCriterion $criterion): static
    {
        if ($this->criteria->removeElement($criterion)) {
            // set the owning side to null (unless already changed)
            if ($criterion->getRubric() === $this) {
                $criterion->setRubric(null);
            }
        }

        return $this;
    }
}
