<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\PeerReviewScoreRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PeerReviewScoreRepository::class)]
class PeerReviewScore
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'smallint')]
    private ?int $score = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?RubricCriterion $criterion = null;

    #[ORM\ManyToOne(inversedBy: 'scores')]
    #[ORM\JoinColumn(nullable: false)]
    private ?PeerReview $peerReview = null;

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

    public function getCriterion(): ?RubricCriterion
    {
        return $this->criterion;
    }

    public function setCriterion(?RubricCriterion $criterion): static
    {
        $this->criterion = $criterion;

        return $this;
    }

    public function getPeerReview(): ?PeerReview
    {
        return $this->peerReview;
    }

    public function setPeerReview(?PeerReview $peerReview): static
    {
        $this->peerReview = $peerReview;

        return $this;
    }
}
