<?php

namespace App\Entity;

use App\Entity\Trait\DateTimeAble;
use App\Entity\Trait\SlugAble;
use App\Repository\CohortRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CohortRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Cohort
{
    use DateTimeAble, SlugAble;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = 'images/default-cohort.png';

    #[ORM\ManyToMany(targetEntity: Course::class, inversedBy: 'cohorts')]
    private Collection $courses;

    #[ORM\Column(type: Types::SMALLINT)]
    private ?int $year = null;

    #[ORM\ManyToMany(targetEntity: User::class, mappedBy: 'cohorts')]
    private Collection $users;

    #[ORM\Column(length: 255, options: ['default' => 'draft'])]
    private ?string $status = 'draft';

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $startAt = null;

    #[ORM\ManyToOne(inversedBy: 'cohorts')]
    private ?Calendar $calendar = null;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    private ?Conversation $conversation = null;

    #[ORM\Column(length: 64, nullable: true, unique: true)]
    private ?string $invitationToken = null;

    #[ORM\Column(length: 7, nullable: true)]
    private ?string $brandColor = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $logoPath = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $discordWebhookUrl = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $slackWebhookUrl = null;

    public function __construct()
    {
        $this->courses = new ArrayCollection();

        $this->year = (new \DateTime('now'))->format('Y');
        $this->users = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->title;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): static
    {
        $this->image = $image;

        return $this;
    }

    /**
     * @return Collection<int, Course>
     */
    public function getCourses(): Collection
    {
        return $this->courses;
    }

    public function addCourse(Course $course): static
    {
        if (!$this->courses->contains($course)) {
            $this->courses->add($course);
        }

        return $this;
    }

    public function removeCourse(Course $course): static
    {
        $this->courses->removeElement($course);

        return $this;
    }

    public function getYear(): ?int
    {
        return $this->year;
    }

    public function setYear(int $year): static
    {
        $this->year = $year;

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function addUser(User $user): static
    {
        if (!$this->users->contains($user)) {
            $this->users->add($user);
        }

        return $this;
    }

    public function removeUser(User $user): static
    {
        $this->users->removeElement($user);

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

    public function getStartAt(): ?\DateTimeImmutable
    {
        return $this->startAt;
    }

    public function setStartAt(?\DateTimeImmutable $startAt): static
    {
        $this->startAt = $startAt;

        return $this;
    }

    public function getCalendar(): ?Calendar
    {
        return $this->calendar;
    }

    public function setCalendar(?Calendar $calendar): static
    {
        $this->calendar = $calendar;

        return $this;
    }

    public function getConversation(): ?Conversation
    {
        return $this->conversation;
    }

    public function setConversation(?Conversation $conversation): static
    {
        $this->conversation = $conversation;

        return $this;
    }

    public function getInvitationToken(): ?string
    {
        return $this->invitationToken;
    }

    public function setInvitationToken(?string $invitationToken): static
    {
        $this->invitationToken = $invitationToken;

        return $this;
    }

    public function getBrandColor(): ?string
    {
        return $this->brandColor;
    }

    public function setBrandColor(?string $brandColor): static
    {
        $this->brandColor = $brandColor;

        return $this;
    }

    public function getLogoPath(): ?string
    {
        return $this->logoPath;
    }

    public function setLogoPath(?string $logoPath): static
    {
        $this->logoPath = $logoPath;

        return $this;
    }

    public function getDiscordWebhookUrl(): ?string
    {
        return $this->discordWebhookUrl;
    }

    public function setDiscordWebhookUrl(?string $discordWebhookUrl): static
    {
        $this->discordWebhookUrl = $discordWebhookUrl;

        return $this;
    }

    public function getSlackWebhookUrl(): ?string
    {
        return $this->slackWebhookUrl;
    }

    public function setSlackWebhookUrl(?string $slackWebhookUrl): static
    {
        $this->slackWebhookUrl = $slackWebhookUrl;

        return $this;
    }

    #[ORM\PrePersist]
    public function generateInvitationToken(): void
    {
        if (null === $this->invitationToken) {
            $this->invitationToken = bin2hex(random_bytes(16));
        }
    }
}
