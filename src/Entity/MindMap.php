<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\MindMapRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MindMapRepository::class)]
class MindMap
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(targetEntity: Module::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Module $module;

    #[ORM\Column(type: 'json')]
    private array $data = [];

    public function getId(): ?int { return $this->id; }
    public function getModule(): Module { return $this->module; }
    public function setModule(Module $module): static { $this->module = $module; return $this; }
    public function getData(): array { return $this->data; }
    public function setData(array $data): static { $this->data = $data; return $this; }
}
