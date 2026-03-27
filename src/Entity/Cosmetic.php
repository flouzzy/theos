<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\CosmeticRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CosmeticRepository::class)]
class Cosmetic
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\Column(length: 50)]
    private string $type; // avatar_frame, background, badge

    #[ORM\Column]
    private int $price;

    public function getId(): ?int { return $this->id; }
    public function getName(): string { return $this->name; }
    public function setName(string $name): static { $this->name = $name; return $this; }
    public function getType(): string { return $this->type; }
    public function setType(string $type): static { $this->type = $type; return $this; }
    public function getPrice(): int { return $this->price; }
    public function setPrice(int $price): static { $this->price = $price; return $this; }
}
