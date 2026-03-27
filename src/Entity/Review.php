<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ReviewRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReviewRepository::class)]
class Review
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT)]
    private string $comment;

    #[ORM\Column(type: Types::SMALLINT)]
    private int $rating;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $author;

    #[ORM\ManyToOne(targetEntity: Course::class, inversedBy: 'reviews')]
    #[ORM\JoinColumn(nullable: false)]
    private Course $course;

    public function getId(): ?int { return $this->id; }
    public function getComment(): string { return $this->comment; }
    public function setComment(string $comment): static { $this->comment = $comment; return $this; }
    public function getRating(): int { return $this->rating; }
    public function setRating(int $rating): static { $this->rating = $rating; return $this; }
    public function getAuthor(): User { return $this->author; }
    public function setAuthor(User $author): static { $this->author = $author; return $this; }
    public function getCourse(): Course { return $this->course; }
    public function setCourse(Course $course): static { $this->course = $course; return $this; }
}
