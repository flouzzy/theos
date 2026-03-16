<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\GlossaryTermRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GlossaryTermRepository::class)]
class GlossaryTerm
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, unique: true)]
    private string $term;

    #[ORM\Column(type: 'text')]
    private string $definition;

    #[ORM\Column(options: ['default' => false])]
    private bool $isApproved = false;

    public function getId(): ?int { return $this->id; }
    public function getTerm(): string { return $this->term; }
    public function setTerm(string $term): static { $this->term = $term; return $this; }
    public function getDefinition(): string { return $this->definition; }
    public function setDefinition(string $definition): static { $this->definition = $definition; return $this; }
    public function isApproved(): bool { return $this->isApproved; }
    public function setIsApproved(bool $isApproved): static { $this->isApproved = $isApproved; return $this; }
}
