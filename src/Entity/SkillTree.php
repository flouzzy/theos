<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\SkillTreeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SkillTreeRepository::class)]
class SkillTree
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\OneToMany(mappedBy: 'tree', targetEntity: SkillNode::class, cascade: ['persist', 'remove'])]
    private Collection $nodes;

    public function __construct()
    {
        $this->nodes = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }
    public function getNodes(): Collection { return $this->nodes; }
}
