<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\HelpfulVoteRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: HelpfulVoteRepository::class)]
class HelpfulVote
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $voter;

    #[ORM\ManyToOne(targetEntity: Comment::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Comment $comment;

    public function getId(): ?int { return $this->id; }
    public function getVoter(): User { return $this->voter; }
    public function setVoter(User $voter): static { $this->voter = $voter; return $this; }
    public function getComment(): Comment { return $this->comment; }
    public function setComment(Comment $comment): static { $this->comment = $comment; return $this; }
}
