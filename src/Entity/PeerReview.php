<?php

namespace App\Entity;

use App\Entity\Trait\DateTimeAble;
use App\Repository\PeerReviewRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PeerReviewRepository::class)]
#[ORM\UniqueConstraint(name: 'unique_peer_review', columns: ['submission_id', 'reviewer_id'])]
#[ORM\HasLifecycleCallbacks]
class PeerReview
{
    use DateTimeAble;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::SMALLINT)]
    private ?int $score = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $feedback = null;

    #[ORM\ManyToOne(inversedBy: 'reviews')]
    #[ORM\JoinColumn(nullable: false)]
    private ?AssignmentSubmission $submission = null;

    #[ORM\ManyToOne(inversedBy: 'peerReviews')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $reviewer = null;

    #[ORM\OneToMany(mappedBy: 'peerReview', targetEntity: PeerReviewScore::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $scores;

    public function __construct()
    {
        $this->scores = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getScore(): ?int
    {
        return $this->score;
    }

    public function setScore(int $score): static
    {
        $this->score = $score;

        return $this;
    }

    public function getFeedback(): ?string
    {
        return $this->feedback;
    }

    public function setFeedback(?string $feedback): static
    {
        $this->feedback = $feedback;

        return $this;
    }

    public function getSubmission(): ?AssignmentSubmission
    {
        return $this->submission;
    }

    public function setSubmission(?AssignmentSubmission $submission): static
    {
        $this->submission = $submission;

        return $this;
    }

    public function getReviewer(): ?User
    {
        return $this->reviewer;
    }

    public function setReviewer(?User $reviewer): static
    {
        $this->reviewer = $reviewer;

        return $this;
    }

    /**
     * @return Collection<int, PeerReviewScore>
     */
    public function getScores(): Collection
    {
        return $this->scores;
    }

    public function addScore(PeerReviewScore $score): static
    {
        if (!$this->scores->contains($score)) {
            $this->scores->add($score);
            $score->setPeerReview($this);
        }

        return $this;
    }

    public function removeScore(PeerReviewScore $score): static
    {
        if ($this->scores->removeElement($score)) {
            // set the owning side to null (unless already changed)
            if ($score->getPeerReview() === $this) {
                $score->setPeerReview(null);
            }
        }

        return $this;
    }
}
