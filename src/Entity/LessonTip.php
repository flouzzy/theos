<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\LessonTipRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LessonTipRepository::class)]
class LessonTip
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'text')]
    private string $content;

    #[ORM\ManyToOne(targetEntity: Lesson::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Lesson $lesson;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $author;

    public function getId(): ?int { return $this->id; }
    public function getContent(): string { return $this->content; }
    public function setContent(string $content): static { $this->content = $content; return $this; }
    public function getLesson(): Lesson { return $this->lesson; }
    public function setLesson(Lesson $lesson): static { $this->lesson = $lesson; return $this; }
    public function getAuthor(): User { return $this->author; }
    public function setAuthor(User $author): static { $this->author = $author; return $this; }
}
