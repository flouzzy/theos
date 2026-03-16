<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\SubtitleRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SubtitleRepository::class)]
class Subtitle
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 5)]
    private string $language;

    #[ORM\Column(type: Types::TEXT)]
    private string $content;

    #[ORM\ManyToOne(targetEntity: Lesson::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Lesson $lesson;

    public function getId(): ?int { return $this->id; }
    public function getLanguage(): string { return $this->language; }
    public function setLanguage(string $language): static { $this->language = $language; return $this; }
    public function getContent(): string { return $this->content; }
    public function setContent(string $content): static { $this->content = $content; return $this; }
    public function getLesson(): Lesson { return $this->lesson; }
    public function setLesson(Lesson $lesson): static { $this->lesson = $lesson; return $this; }
}
