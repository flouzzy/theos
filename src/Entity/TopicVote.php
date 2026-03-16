<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\TopicVoteRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TopicVoteRepository::class)]
class TopicVote
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'votes')]
    #[ORM\JoinColumn(nullable: false)]
    private Topic $topic;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    public function getId(): ?int { return $this->id; }
    public function getTopic(): Topic { return $this->topic; }
    public function setTopic(Topic $topic): static { $this->topic = $topic; return $this; }
    public function getUser(): User { return $this->user; }
    public function setUser(User $user): static { $this->user = $user; return $this; }
}
