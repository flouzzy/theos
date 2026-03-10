<?php

namespace App\Entity;

use App\Entity\Trait\DateTimeAble;
use App\Repository\AssignmentSubmissionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AssignmentSubmissionRepository::class)]
#[ORM\HasLifecycleCallbacks]
class AssignmentSubmission
{
    use DateTimeAble;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $content = null;

    #[ORM\Column(length: 255)]
    private ?string $status = 'submitted';

    #[ORM\ManyToOne(inversedBy: 'submissions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Assignment $assignment = null;

    #[ORM\ManyToOne(inversedBy: 'assignmentSubmissions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\OneToMany(mappedBy: 'submission', targetEntity: PeerReview::class)]
    private Collection $reviews;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $filePath = null;

    public function __construct()
    {
        $this->reviews = new ArrayCollection();
    }

    public function getFilePath(): ?string
    {
        return $this->filePath;
    }

    public function setFilePath(?string $filePath): static
    {
        $this->filePath = $filePath;

        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getAssignment(): ?Assignment
    {
        return $this->assignment;
    }

    public function setAssignment(?Assignment $assignment): static
    {
        $this->assignment = $assignment;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return Collection<int, PeerReview>
     */
    public function getReviews(): Collection
    {
        return $this->reviews;
    }

    public function addReview(PeerReview $review): static
    {
        if (!$this->reviews->contains($review)) {
            $this->reviews->add($review);
            $review->setSubmission($this);
        }

        return $this;
    }

    public function removeReview(PeerReview $review): static
    {
        if ($this->reviews->removeElement($review)) {
            // set the owning side to null (unless already changed)
            if ($review->getSubmission() === $this) {
                $review->setSubmission(null);
            }
        }

        return $this;
    }
}
