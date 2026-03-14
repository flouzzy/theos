<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Trait\DateTimeAble;
use App\Repository\BehindTheScenesRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BehindTheScenesRepository::class)]
class BehindTheScenes
{
    use DateTimeAble;

    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $title;

    #[ORM\Column(type: 'text')]
    private string $content;

    #[ORM\Column]
    private int $minXpRequired = 0;

    public function getId(): ?int { return $this->id; }
    public function getTitle(): string { return $this->title; }
    public function setTitle(string $title): static { $this->title = $title; return $this; }
    public function getContent(): string { return $this->content; }
    public function setContent(string $content): static { $this->content = $content; return $this; }
    public function getMinXpRequired(): int { return $this->minXpRequired; }
    public function setMinXpRequired(int $minXpRequired): static { $this->minXpRequired = $minXpRequired; return $this; }
}
