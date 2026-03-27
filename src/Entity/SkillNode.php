<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\SkillNodeRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SkillNodeRepository::class)]
class SkillNode
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private string $title;

    #[ORM\ManyToOne(targetEntity: SkillTree::class, inversedBy: 'nodes')]
    #[ORM\JoinColumn(nullable: false)]
    private SkillTree $tree;

    #[ORM\ManyToOne(targetEntity: self::class)]
    private ?self $prerequisite = null;

    #[ORM\Column]
    private int $xpRequired = 0;

    public function getId(): ?int { return $this->id; }
    public function getTitle(): string { return $this->title; }
    public function setTitle(string $title): static { $this->title = $title; return $this; }
    public function getTree(): SkillTree { return $this->tree; }
    public function setTree(SkillTree $tree): static { $this->tree = $tree; return $this; }
    public function getPrerequisite(): ?self { return $this->prerequisite; }
    public function setPrerequisite(?self $prerequisite): static { $this->prerequisite = $prerequisite; return $this; }
    public function getXpRequired(): int { return $this->xpRequired; }
    public function setXpRequired(int $xpRequired): static { $this->xpRequired = $xpRequired; return $this; }
}
