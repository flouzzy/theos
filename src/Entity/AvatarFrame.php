<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\AvatarFrameRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AvatarFrameRepository::class)]
class AvatarFrame
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\Column(length: 255)]
    private string $imagePath;

    #[ORM\Column(options: ['default' => 0])]
    private int $levelRequired = 0;

    public function getId(): ?int { return $this->id; }
    public function getName(): string { return $this->name; }
    public function setName(string $name): static { $this->name = $name; return $this; }
    public function getImagePath(): string { return $this->imagePath; }
    public function setImagePath(string $imagePath): static { $this->imagePath = $imagePath; return $this; }
    public function getLevelRequired(): int { return $this->levelRequired; }
    public function setLevelRequired(int $levelRequired): static { $this->levelRequired = $levelRequired; return $this; }
}
