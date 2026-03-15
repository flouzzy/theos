<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\CodeReviewRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CodeReviewRepository::class)]
class CodeReview
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: AssignmentSubmission::class)]
    #[ORM\JoinColumn(nullable: false)]
    private AssignmentSubmission $submission;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $reviewer;

    #[ORM\Column(type: 'text')]
    private string $comment;

    #[ORM\Column]
    private int $rating;

    public function getId(): ?int { return $this->id; }
    public function getSubmission(): AssignmentSubmission { return $this->submission; }
    public function setSubmission(AssignmentSubmission $submission): static { $this->submission = $submission; return $this; }
    public function getReviewer(): User { return $this->reviewer; }
    public function setReviewer(User $reviewer): static { $this->reviewer = $reviewer; return $this; }
    public function getComment(): string { return $this->comment; }
    public function setComment(string $comment): static { $this->comment = $comment; return $this; }
    public function getRating(): int { return $this->rating; }
    public function setRating(int $rating): static { $this->rating = $rating; return $this; }
}
