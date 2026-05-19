<?php

namespace App\Entity;

use App\Entity\Enum\PaymentStatusEnum;
use App\Entity\Trait\DateTimeAble;
use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Scheb\TwoFactorBundle\Model\Totp\TwoFactorInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ORM\Table(name: '`user`')]
#[UniqueEntity(fields: ['email'], message: 'There is already an account with this email')]
class User implements UserInterface, PasswordAuthenticatedUserInterface, TwoFactorInterface
{

    use DateTimeAble;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    #[Assert\NotBlank]
    private ?string $email = null;

    /**
     * @var list<string>
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string|null The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(type: 'boolean')]
    private bool $isVerified = false;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\NotBlank]
    private ?string $firstname = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\NotBlank]
    private ?string $lastname = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private ?string $fullname = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $lastConnectionAt = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $username = null;

    /**
     * @var Collection<int, Course>
     */
    #[ORM\ManyToMany(targetEntity: Course::class, mappedBy: 'users')]
    private Collection $courses;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = 'images/default-user.png';

    /**
     * @var Collection<int, Module>
     */
    #[ORM\OneToMany(mappedBy: 'author', targetEntity: Module::class, orphanRemoval: true)]
    private Collection $modules;

    /**
     * @var Collection<int, Lesson>
     */
    #[ORM\OneToMany(mappedBy: 'author', targetEntity: Lesson::class, orphanRemoval: true)]
    private Collection $lessons;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $bio = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $learningManifesto = null;

    /**
     * @var Collection<int, Note>
     */
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Note::class, orphanRemoval: true)]
    private Collection $notes;

    /**
     * @var Collection<int, Course>
     */
    #[ORM\OneToMany(mappedBy: 'author', targetEntity: Course::class, orphanRemoval: true)]
    private Collection $authorCourses;

    /**
     * @var Collection<int, Completion>
     */
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Completion::class, orphanRemoval: true)]
    private Collection $completions;

    /**
     * @var Collection<int, CourseCompletion>
     */
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: CourseCompletion::class, orphanRemoval: true)]
    private Collection $courseCompletions;

    /**
     * @var Collection<int, ModuleCompletion>
     */
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: ModuleCompletion::class, orphanRemoval: true)]
    private Collection $moduleCompletions;

    /**
     * @var Collection<int, Notification>
     */
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Notification::class, orphanRemoval: true)]
    private Collection $notifications;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $birthDate = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $address = null;

    /**
     * @var Collection<int, Comment>
     */
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Comment::class, orphanRemoval: true)]
    private Collection $comments;

    /**
     * @var Collection<int, Badge>
     */
    #[ORM\ManyToMany(targetEntity: Badge::class, mappedBy: 'users')]
    private Collection $badges;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    private ?int $score = null;

    /**
     * @var Collection<int, Cohort>
     */
    #[ORM\ManyToMany(targetEntity: Cohort::class, inversedBy: 'users')]
    private Collection $cohorts;

    #[ORM\ManyToOne(targetEntity: Cohort::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Cohort $currentCohort = null;

    /**
     * @var Collection<int, Comment>
     */
    #[ORM\ManyToMany(targetEntity: Comment::class, mappedBy: 'likes')]
    private Collection $likedComments;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    private int $xp = 0;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    private int $streak = 0;

    #[ORM\Column(length: 100, options: ['default' => 'Bronze'])]
    private string $tier = 'Bronze';

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $lastStreakDate = null;

    #[ORM\Column(length: 50, options: ['default' => 'UTC'])]
    private string $timezone = 'UTC';

    /**
     * @var Collection<int, Skill>
     */
    #[ORM\ManyToMany(targetEntity: Skill::class, mappedBy: 'users')]
    private Collection $skills;

    /**
     * @var Collection<int, PortfolioProject>
     */
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: PortfolioProject::class, orphanRemoval: true)]
    private Collection $portfolioProjects;

    /**
     * @var Collection<int, AssignmentSubmission>
     */
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: AssignmentSubmission::class, orphanRemoval: true)]
    private Collection $assignmentSubmissions;

    /**
     * @var Collection<int, PeerReview>
     */
    #[ORM\OneToMany(mappedBy: 'reviewer', targetEntity: PeerReview::class, orphanRemoval: true)]
    private Collection $peerReviews;

    #[ORM\Column(type: 'string', enumType: PaymentStatusEnum::class, options: ['default' => PaymentStatusEnum::UNPAID])]
    private PaymentStatusEnum $paymentStatus = PaymentStatusEnum::UNPAID;

    #[ORM\Column(nullable: true)]
    private ?string $googleAuthenticatorSecret = null;

    /**
     * @var Collection<int, Evaluation>
     */
    #[ORM\OneToMany(targetEntity: Evaluation::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $evaluations;

    /**
     * @var Collection<int, ChatMessage>
     */
    #[ORM\OneToMany(mappedBy: 'author', targetEntity: ChatMessage::class, orphanRemoval: true)]
    private Collection $chatMessages;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $stripeCustomerId = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $subscriptionId = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $subscriptionStatus = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $subscriptionPlan = null;

    #[ORM\ManyToOne(inversedBy: 'members')]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?Team $team = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $jwtSecret = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $googleId = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $coverPhoto = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $linkedinId = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $websiteUrl = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $githubUrl = null;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $isProfilePublic = false;

    #[ORM\Column(length: 7, options: ['default' => '#000000'])]
    private string $confettiColor = '#000000';

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $customGoal = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $rssFeedUrl = null;

    #[ORM\Column(length: 20, options: ['default' => 'light'])]
    private string $theme = 'light';

    /**
     * @var Collection<int, XpTransaction>
     */
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: XpTransaction::class, orphanRemoval: true)]
    private Collection $xpTransactions;

    /**
     * @var Collection<int, PushSubscription>
     */
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: PushSubscription::class, orphanRemoval: true)]
    private Collection $pushSubscriptions;

    /**
     * @var Collection<int, ExternalAccount>
     */
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: ExternalAccount::class, orphanRemoval: true)]
    private Collection $externalAccounts;

    #[ORM\Column(length: 128, nullable: true, unique: true)]
    private ?string $loginToken = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $loginTokenExpiresAt = null;

    /**
     * @var Collection<int, Playlist>
     */
    #[ORM\OneToMany(mappedBy: 'owner', targetEntity: Playlist::class, orphanRemoval: true)]
    private Collection $playlists;


    #[ORM\OneToMany(mappedBy: 'receiver', targetEntity: SkillEndorsement::class, orphanRemoval: true)]
    private Collection $receivedEndorsements;

    #[ORM\ManyToMany(targetEntity: Team::class, mappedBy: 'members')]
    private Collection $teams;

    /**
     * @var Collection<int, Bonus>
     */
    #[ORM\ManyToMany(targetEntity: Bonus::class)]
    #[ORM\JoinTable(name: 'user_unlocked_bonuses')]
    private Collection $unlockedBonuses;

    /**
     * @var Collection<int, AvatarFrame>
     */
    #[ORM\ManyToMany(targetEntity: AvatarFrame::class, inversedBy: 'users')]
    #[ORM\JoinTable(name: 'user_unlocked_frames')]
    private Collection $unlockedFrames;

    #[ORM\ManyToOne]
    private ?AvatarFrame $activeFrame = null;

    public function __construct()
    {
        $this->courses = new ArrayCollection();
        $this->modules = new ArrayCollection();
        $this->lessons = new ArrayCollection();
        $this->notes = new ArrayCollection();
        $this->authorCourses = new ArrayCollection();
        $this->completions = new ArrayCollection();
        $this->courseCompletions = new ArrayCollection();
        $this->moduleCompletions = new ArrayCollection();
        $this->notifications = new ArrayCollection();
        $this->comments = new ArrayCollection();
        $this->badges = new ArrayCollection();
        $this->cohorts = new ArrayCollection();
        $this->likedComments = new ArrayCollection();
        $this->skills = new ArrayCollection();
        $this->portfolioProjects = new ArrayCollection();
        $this->assignmentSubmissions = new ArrayCollection();
        $this->peerReviews = new ArrayCollection();
        $this->evaluations = new ArrayCollection();
        $this->chatMessages = new ArrayCollection();
        $this->xpTransactions = new ArrayCollection();
        $this->pushSubscriptions = new ArrayCollection();
        $this->playlists = new ArrayCollection();
        $this->externalAccounts = new ArrayCollection();
        $this->receivedEndorsements = new ArrayCollection();
        $this->teams = new ArrayCollection();
        $this->unlockedBonuses = new ArrayCollection();
        $this->unlockedFrames = new ArrayCollection();
    }

    public function __toString()
    {
        return (string) $this->fullname;
    }

    #[ORM\PreUpdate]
    public function updateUserDetails(): void
    {
        if ($this->firstname || $this->lastname) {
            $this->fullname = $this->lastname . ' ' . $this->firstname;
        }
    }

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $emailNotifications = true;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $pushNotifications = true;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $lessonReminders = true;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $weeklySummary = false;


    #[ORM\PrePersist]
    public function setUserDetails(): void
    {
        if ($this->fullname) {
            $details = preg_split('/\s+/', trim($this->fullname));
            if (false !== $details) {
                $this->lastname = $this->lastname ?? $details[0];
                $this->firstname = $this->firstname ?? ($details[1] ?? '');
            }
        } else {
            $this->fullname = trim($this->lastname . ' ' . $this->firstname);
        }

        // Slug
        $slugger = new AsciiSlugger();
        $this->username = $slugger->slug($this->fullname . '-' . uniqid())->lower();
    }

    public function subscribeToCourse(Course $course): self
    {
        // Vérifiez si l'utilisateur n'est pas déjà abonné au cours
        if (!$this->courses->contains($course)) {
            $this->courses->add($course);
            $course->addUser($this);
        }

        return $this;
    }

    public function unsubscribeFromCourse(Course $course): self
    {
        // Vérifiez si l'utilisateur est abonné au cours
        if ($this->courses->contains($course)) {
            $this->courses->removeElement($course);
            $course->removeUser($this);
        }

        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        if (!in_array('ROLE_USER', $roles, true)) {
            $roles[] = 'ROLE_USER';
        }

        return $roles;
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = array_values(array_unique($roles));

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return (string) $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(bool $isVerified): static
    {
        $this->isVerified = $isVerified;

        return $this;
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(string $firstname): static
    {
        $this->firstname = $firstname;

        return $this;
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function setLastname(string $lastname): static
    {
        $this->lastname = $lastname;

        return $this;
    }

    public function getLastConnectionAt(): ?\DateTimeImmutable
    {
        return $this->lastConnectionAt;
    }

    public function setLastConnectionAt(?\DateTimeImmutable $lastConnectionAt): static
    {
        $this->lastConnectionAt = $lastConnectionAt;

        return $this;
    }

    public function getFullname(): ?string
    {
        return $this->fullname;
    }

    public function setFullname(string $fullname): static
    {
        $this->fullname = $fullname;

        return $this;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): static
    {
        $this->username = $username;

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
            $course->addUser($this);
        }

        return $this;
    }

    public function removeCourse(Course $course): static
    {
        if ($this->courses->removeElement($course)) {
            $course->removeUser($this);
        }

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
     * @return Collection<int, Module>
     */
    public function getModules(): Collection
    {
        return $this->modules;
    }

    public function addModule(Module $module): static
    {
        if (!$this->modules->contains($module)) {
            $this->modules->add($module);
            $module->setAuthor($this);
        }

        return $this;
    }

    public function removeModule(Module $module): static
    {
        if ($this->modules->removeElement($module)) {
            // set the owning side to null (unless already changed)
            if ($module->getAuthor() === $this) {
                $module->setAuthor(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Lesson>
     */
    public function getLessons(): Collection
    {
        return $this->lessons;
    }

    public function addLesson(Lesson $lesson): static
    {
        if (!$this->lessons->contains($lesson)) {
            $this->lessons->add($lesson);
            $lesson->setAuthor($this);
        }

        return $this;
    }

    public function removeLesson(Lesson $lesson): static
    {
        if ($this->lessons->removeElement($lesson)) {
            // set the owning side to null (unless already changed)
            if ($lesson->getAuthor() === $this) {
                $lesson->setAuthor(null);
            }
        }

        return $this;
    }

    public function getBio(): ?string
    {
        return $this->bio;
    }

    public function setBio(?string $bio): static
    {
        $this->bio = $bio;

        return $this;
    }

    /**
     * @return Collection<int, Note>
     */
    public function getNotes(): Collection
    {
        return $this->notes;
    }

    public function addNote(Note $note): static
    {
        if (!$this->notes->contains($note)) {
            $this->notes->add($note);
            $note->setUser($this);
        }

        return $this;
    }

    public function removeNote(Note $note): static
    {
        if ($this->notes->removeElement($note)) {
            // set the owning side to null (unless already changed)
            if ($note->getUser() === $this) {
                $note->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Course>
     */
    public function getAuthorCourses(): Collection
    {
        return $this->authorCourses;
    }

    public function addAuthorCourse(Course $authorCourse): static
    {
        if (!$this->authorCourses->contains($authorCourse)) {
            $this->authorCourses->add($authorCourse);
            $authorCourse->setAuthor($this);
        }

        return $this;
    }

    public function removeAuthorCourse(Course $authorCourse): static
    {
        if ($this->authorCourses->removeElement($authorCourse)) {
            // set the owning side to null (unless already changed)
            if ($authorCourse->getAuthor() === $this) {
                $authorCourse->setAuthor(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Completion>
     */
    public function getCompletions(): Collection
    {
        return $this->completions;
    }

    public function addCompletion(Completion $completion): static
    {
        if (!$this->completions->contains($completion)) {
            $this->completions->add($completion);
            $completion->setUser($this);
        }

        return $this;
    }

    public function removeCompletion(Completion $completion): static
    {
        if ($this->completions->removeElement($completion)) {
            // set the owning side to null (unless already changed)
            if ($completion->getUser() === $this) {
                $completion->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, CourseCompletion>
     */
    public function getCourseCompletions(): Collection
    {
        return $this->courseCompletions;
    }

    public function addCourseCompletion(CourseCompletion $courseCompletion): static
    {
        if (!$this->courseCompletions->contains($courseCompletion)) {
            $this->courseCompletions->add($courseCompletion);
            $courseCompletion->setUser($this);
        }

        return $this;
    }

    public function removeCourseCompletion(CourseCompletion $courseCompletion): static
    {
        if ($this->courseCompletions->removeElement($courseCompletion)) {
            // set the owning side to null (unless already changed)
            if ($courseCompletion->getUser() === $this) {
                $courseCompletion->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ModuleCompletion>
     */
    public function getModuleCompletions(): Collection
    {
        return $this->moduleCompletions;
    }

    public function addModuleCompletion(ModuleCompletion $moduleCompletion): static
    {
        if (!$this->moduleCompletions->contains($moduleCompletion)) {
            $this->moduleCompletions->add($moduleCompletion);
            $moduleCompletion->setUser($this);
        }

        return $this;
    }

    public function removeModuleCompletion(ModuleCompletion $moduleCompletion): static
    {
        if ($this->moduleCompletions->removeElement($moduleCompletion)) {
            // set the owning side to null (unless already changed)
            if ($moduleCompletion->getUser() === $this) {
                $moduleCompletion->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Notification>
     */
    public function getNotifications(): Collection
    {
        return $this->notifications;
    }

    public function addNotification(Notification $notification): static
    {
        if (!$this->notifications->contains($notification)) {
            $this->notifications->add($notification);
            $notification->setUser($this);
        }

        return $this;
    }

    public function removeNotification(Notification $notification): static
    {
        if ($this->notifications->removeElement($notification)) {
            // set the owning side to null (unless already changed)
            if ($notification->getUser() === $this) {
                $notification->setUser(null);
            }
        }

        return $this;
    }

    public function getBirthDate(): ?\DateTimeImmutable
    {
        return $this->birthDate;
    }

    public function setBirthDate(?\DateTimeImmutable $birthDate): static
    {
        $this->birthDate = $birthDate;

        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): static
    {
        $this->address = $address;

        return $this;
    }

    /**
     * @return Collection<int, Comment>
     */
    public function getComments(): Collection
    {
        return $this->comments;
    }

    public function addComment(Comment $comment): static
    {
        if (!$this->comments->contains($comment)) {
            $this->comments->add($comment);
            $comment->setUser($this);
        }

        return $this;
    }

    public function removeComment(Comment $comment): static
    {
        if ($this->comments->removeElement($comment)) {
            // set the owning side to null (unless already changed)
            if ($comment->getUser() === $this) {
                $comment->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Badge>
     */
    public function getBadges(): Collection
    {
        return $this->badges;
    }

    public function addBadge(Badge $badge): static
    {
        if (!$this->badges->contains($badge)) {
            $this->badges->add($badge);
            $badge->addUser($this);
        }

        return $this;
    }

    public function removeBadge(Badge $badge): static
    {
        if ($this->badges->removeElement($badge)) {
            $badge->removeUser($this);
        }

        return $this;
    }

    public function getScore(): ?int
    {
        return $this->score;
    }

    public function setScore(?int $score): static
    {
        $this->score = $score;

        return $this;
    }

    /**
     * @return Collection<int, Cohort>
     */
    public function getCohorts(): Collection
    {
        return $this->cohorts;
    }

    public function addCohort(Cohort $cohort): static
    {
        if (!$this->cohorts->contains($cohort)) {
            $this->cohorts->add($cohort);
            $cohort->addUser($this);
        }

        return $this;
    }

    public function removeCohort(Cohort $cohort): static
    {
        if ($this->cohorts->removeElement($cohort)) {
            $cohort->removeUser($this);
            if ($this->currentCohort === $cohort) {
                $this->currentCohort = null;
            }
        }

        return $this;
    }

    public function getCurrentCohort(): ?Cohort
    {
        return $this->currentCohort;
    }

    public function setCurrentCohort(?Cohort $currentCohort): static
    {
        $this->currentCohort = $currentCohort;

        return $this;
    }

    public function getPaymentStatus(): PaymentStatusEnum
    {
        return $this->paymentStatus;
    }

    public function setPaymentStatus(PaymentStatusEnum $paymentStatus): static
    {
        $this->paymentStatus = $paymentStatus;

        return $this;
    }

    public function isPaid(): bool
    {
        return $this->paymentStatus === PaymentStatusEnum::PAID;
    }

    /**
     * @return Collection<int, Comment>
     */
    public function getLikedComments(): Collection
    {
        return $this->likedComments;
    }

    public function addLikedComment(Comment $likedComment): static
    {
        if (!$this->likedComments->contains($likedComment)) {
            $this->likedComments->add($likedComment);
            $likedComment->addLike($this);
        }

        return $this;
    }

    public function removeLikedComment(Comment $likedComment): static
    {
        if ($this->likedComments->removeElement($likedComment)) {
            $likedComment->removeLike($this);
        }

        return $this;
    }

    public function isTotpAuthenticationEnabled(): bool
    {
        return null !== $this->googleAuthenticatorSecret;
    }

    public function getTotpAuthenticationUsername(): string
    {
        return (string) $this->email;
    }

    public function getTotpAuthenticationConfiguration(): ?\Scheb\TwoFactorBundle\Model\Totp\TotpConfigurationInterface
    {
        if ($this->googleAuthenticatorSecret) {
            return new \Scheb\TwoFactorBundle\Model\Totp\TotpConfiguration($this->googleAuthenticatorSecret, \Scheb\TwoFactorBundle\Model\Totp\TotpConfiguration::ALGORITHM_SHA1, 30, 6);
        }
        return null;
    }

    public function getGoogleAuthenticatorSecret(): ?string
    {
        return $this->googleAuthenticatorSecret;
    }

    public function setGoogleAuthenticatorSecret(?string $googleAuthenticatorSecret): static
    {
        $this->googleAuthenticatorSecret = $googleAuthenticatorSecret;

        return $this;
    }

    public function getXp(): int
    {
        return $this->xp;
    }

    public function setXp(int $xp): static
    {
        $this->xp = $xp;

        return $this;
    }

    public function addXp(int $xp): static
    {
        $this->xp += $xp;

        return $this;
    }

    public function getStreak(): int
    {
        return $this->streak;
    }

    public function setStreak(int $streak): static
    {
        $this->streak = $streak;

        return $this;
    }

    public function getTier(): string
    {
        return $this->tier;
    }

    public function setTier(string $tier): static
    {
        $this->tier = $tier;

        return $this;
    }

    public function getLastStreakDate(): ?\DateTimeImmutable
    {
        return $this->lastStreakDate;
    }

    public function setLastStreakDate(?\DateTimeImmutable $lastStreakDate): static
    {
        $this->lastStreakDate = $lastStreakDate;

        return $this;
    }

    public function getTimezone(): string
    {
        return $this->timezone;
    }

    public function setTimezone(string $timezone): static
    {
        $this->timezone = $timezone;

        return $this;
    }

    /**
     * @return Collection<int, Skill>
     */
    public function getSkills(): Collection
    {
        return $this->skills;
    }

    public function addSkill(Skill $skill): static
    {
        if (!$this->skills->contains($skill)) {
            $this->skills->add($skill);
            $skill->addUser($this);
        }

        return $this;
    }

    public function removeSkill(Skill $skill): static
    {
        if ($this->skills->removeElement($skill)) {
            $skill->removeUser($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, PortfolioProject>
     */
    public function getPortfolioProjects(): Collection
    {
        return $this->portfolioProjects;
    }

    public function addPortfolioProject(PortfolioProject $portfolioProject): static
    {
        if (!$this->portfolioProjects->contains($portfolioProject)) {
            $this->portfolioProjects->add($portfolioProject);
            $portfolioProject->setUser($this);
        }

        return $this;
    }

    public function removePortfolioProject(PortfolioProject $portfolioProject): static
    {
        if ($this->portfolioProjects->removeElement($portfolioProject)) {
            // set the owning side to null (unless already changed)
            if ($portfolioProject->getUser() === $this) {
                $portfolioProject->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, AssignmentSubmission>
     */
    public function getAssignmentSubmissions(): Collection
    {
        return $this->assignmentSubmissions;
    }

    public function addAssignmentSubmission(AssignmentSubmission $assignmentSubmission): static
    {
        if (!$this->assignmentSubmissions->contains($assignmentSubmission)) {
            $this->assignmentSubmissions->add($assignmentSubmission);
            $assignmentSubmission->setUser($this);
        }

        return $this;
    }

    public function removeAssignmentSubmission(AssignmentSubmission $assignmentSubmission): static
    {
        if ($this->assignmentSubmissions->removeElement($assignmentSubmission)) {
            // set the owning side to null (unless already changed)
            if ($assignmentSubmission->getUser() === $this) {
                $assignmentSubmission->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, PeerReview>
     */
    public function getPeerReviews(): Collection
    {
        return $this->peerReviews;
    }

    public function addPeerReview(PeerReview $peerReview): static
    {
        if (!$this->peerReviews->contains($peerReview)) {
            $this->peerReviews->add($peerReview);
            $peerReview->setReviewer($this);
        }

        return $this;
    }

    public function removePeerReview(PeerReview $peerReview): static
    {
        if ($this->peerReviews->removeElement($peerReview)) {
            // set the owning side to null (unless already changed)
            if ($peerReview->getReviewer() === $this) {
                $peerReview->setReviewer(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Evaluation>
     */
    public function getEvaluations(): Collection
    {
        return $this->evaluations;
    }

    public function addEvaluation(Evaluation $evaluation): static
    {
        if (!$this->evaluations->contains($evaluation)) {
            $this->evaluations->add($evaluation);
            $evaluation->setUser($this);
        }

        return $this;
    }

    public function removeEvaluation(Evaluation $evaluation): static
    {
        if ($this->evaluations->removeElement($evaluation)) {
            // set the owning side to null (unless already changed)
            if ($evaluation->getUser() === $this) {
                $evaluation->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ChatMessage>
     */
    public function getChatMessages(): Collection
    {
        return $this->chatMessages;
    }

    public function addChatMessage(ChatMessage $chatMessage): static
    {
        if (!$this->chatMessages->contains($chatMessage)) {
            $this->chatMessages->add($chatMessage);
            $chatMessage->setAuthor($this);
        }

        return $this;
    }

    public function removeChatMessage(ChatMessage $chatMessage): static
    {
        if ($this->chatMessages->removeElement($chatMessage)) {
            // set the owning side to null (unless already changed)
            if ($chatMessage->getAuthor() === $this) {
                $chatMessage->setAuthor(null);
            }
        }

        return $this;
    }

    public function getStripeCustomerId(): ?string
    {
        return $this->stripeCustomerId;
    }

    public function setStripeCustomerId(?string $stripeCustomerId): static
    {
        $this->stripeCustomerId = $stripeCustomerId;

        return $this;
    }

    public function getSubscriptionId(): ?string
    {
        return $this->subscriptionId;
    }

    public function setSubscriptionId(?string $subscriptionId): static
    {
        $this->subscriptionId = $subscriptionId;

        return $this;
    }

    public function getSubscriptionStatus(): ?string
    {
        return $this->subscriptionStatus;
    }

    public function setSubscriptionStatus(?string $subscriptionStatus): static
    {
        $this->subscriptionStatus = $subscriptionStatus;

        return $this;
    }

    public function getSubscriptionPlan(): ?string
    {
        return $this->subscriptionPlan;
    }

    public function setSubscriptionPlan(?string $subscriptionPlan): static
    {
        $this->subscriptionPlan = $subscriptionPlan;

        return $this;
    }

    public function getTeam(): ?Team
    {
        return $this->team;
    }

    public function setTeam(?Team $team): static
    {
        $this->team = $team;

        return $this;
    }

    public function getJwtSecret(): ?string
    {
        return $this->jwtSecret;
    }

    public function setJwtSecret(?string $jwtSecret): static
    {
        $this->jwtSecret = $jwtSecret;

        return $this;
    }

    public function getGoogleId(): ?string
    {
        return $this->googleId;
    }

    public function setGoogleId(?string $googleId): static
    {
        $this->googleId = $googleId;

        return $this;
    }

    public function getCoverPhoto(): ?string
    {
        return $this->coverPhoto;
    }

    public function setCoverPhoto(?string $coverPhoto): static
    {
        $this->coverPhoto = $coverPhoto;

        return $this;
    }

    public function getLinkedinId(): ?string
    {
        return $this->linkedinId;
    }

    public function setLinkedinId(?string $linkedinId): static
    {
        $this->linkedinId = $linkedinId;

        return $this;
    }

    public function isEmailNotifications(): bool
    {
        return $this->emailNotifications;
    }

    public function setEmailNotifications(bool $emailNotifications): static
    {
        $this->emailNotifications = $emailNotifications;

        return $this;
    }

    public function isPushNotifications(): bool
    {
        return $this->pushNotifications;
    }

    public function setPushNotifications(bool $pushNotifications): static
    {
        $this->pushNotifications = $pushNotifications;

        return $this;
    }

    public function isLessonReminders(): bool
    {
        return $this->lessonReminders;
    }

    public function setLessonReminders(bool $lessonReminders): static
    {
        $this->lessonReminders = $lessonReminders;

        return $this;
    }

    public function isWeeklySummary(): bool
    {
        return $this->weeklySummary;
    }

    public function setWeeklySummary(bool $weeklySummary): static
    {
        $this->weeklySummary = $weeklySummary;

        return $this;
    }

    #[ORM\Column(options: ['default' => 0])]
    private int $weeklyGoalHours = 0;

    #[ORM\Column(options: ['default' => false])]
    private bool $isBootcampMode = false;

    public function isBootcampMode(): bool { return $this->isBootcampMode; }
    public function setIsBootcampMode(bool $isBootcampMode): static { $this->isBootcampMode = $isBootcampMode; return $this; }

    #[ORM\Column(options: ['default' => false])]
    private bool $isAlumni = false;

    public function isAlumni(): bool { return $this->isAlumni; }
    public function setIsAlumni(bool $isAlumni): static { $this->isAlumni = $isAlumni; return $this; }


    public function getTheme(): string
    {
        return $this->theme;
    }

    public function setTheme(string $theme): static
    {
        $this->theme = $theme;

        return $this;
    }

    #[ORM\Column(options: ['default' => 0])]
    private int $quizCombo = 0;

    public function getQuizCombo(): int
    {
        return $this->quizCombo;
    }

    public function setQuizCombo(int $quizCombo): static
    {
        $this->quizCombo = $quizCombo;

        return $this;
    }

    /**
     * @return Collection<int, XpTransaction>
     */
    public function getXpTransactions(): Collection
    {
        return $this->xpTransactions;
    }

    public function addXpTransaction(XpTransaction $xpTransaction): static
    {
        if (!$this->xpTransactions->contains($xpTransaction)) {
            $this->xpTransactions->add($xpTransaction);
            $xpTransaction->setUser($this);
        }

        return $this;
    }

    public function removeXpTransaction(XpTransaction $xpTransaction): static
    {
        if ($this->xpTransactions->removeElement($xpTransaction)) {
            // set the owning side to null (unless already changed)
            if ($xpTransaction->getUser() === $this) {
                $xpTransaction->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, PushSubscription>
     */
    public function getPushSubscriptions(): Collection
    {
        return $this->pushSubscriptions;
    }

    public function addPushSubscription(PushSubscription $pushSubscription): static
    {
        if (!$this->pushSubscriptions->contains($pushSubscription)) {
            $this->pushSubscriptions->add($pushSubscription);
            $pushSubscription->setUser($this);
        }

        return $this;
    }

    public function removePushSubscription(PushSubscription $pushSubscription): static
    {
        if ($this->pushSubscriptions->removeElement($pushSubscription)) {
            // set the owning side to null (unless already changed)
            if ($pushSubscription->getUser() === $this) {
                $pushSubscription->setUser(null);
            }
        }

        return $this;
    }


    /**
     * @return Collection<int, ExternalAccount>
     */
    public function getExternalAccounts(): Collection
    {
        return $this->externalAccounts;
    }

    public function addExternalAccount(ExternalAccount $externalAccount): static
    {
        if (!$this->externalAccounts->contains($externalAccount)) {
            $this->externalAccounts->add($externalAccount);
            $externalAccount->setUser($this);
        }

        return $this;
    }

    public function removeExternalAccount(ExternalAccount $externalAccount): static
    {
        if ($this->externalAccounts->removeElement($externalAccount)) {
            // set the owning side to null (unless already changed)
            if ($externalAccount->getUser() === $this) {
                $externalAccount->setUser(null);
            }
        }

        return $this;
    }
    public function getLoginToken(): ?string
    {
        return $this->loginToken;
    }

    public function setLoginToken(?string $loginToken): static
    {
        $this->loginToken = $loginToken;

        return $this;
    }

    public function getLoginTokenExpiresAt(): ?\DateTimeImmutable
    {
        return $this->loginTokenExpiresAt;
    }

    public function setLoginTokenExpiresAt(?\DateTimeImmutable $loginTokenExpiresAt): static
    {
        $this->loginTokenExpiresAt = $loginTokenExpiresAt;

        return $this;
    }

    public function isLoginTokenValid(): bool
    {
        return $this->loginToken && $this->loginTokenExpiresAt > new \DateTimeImmutable();
    }

    #[ORM\Column(options: ['default' => 0])]
    private int $coins = 0;

    public function getCoins(): int
    {
        return $this->coins;
    }

    public function setCoins(int $coins): static
    {
        $this->coins = $coins;

        return $this;
    }

    /**
     * @return Collection<int, Bonus>
     */
    public function getUnlockedBonuses(): Collection
    {
        return $this->unlockedBonuses;
    }

    public function addUnlockedBonus(Bonus $bonus): static
    {
        if (!$this->unlockedBonuses->contains($bonus)) {
            $this->unlockedBonuses->add($bonus);
        }

        return $this;
    }

    public function removeUnlockedBonus(Bonus $bonus): static
    {
        $this->unlockedBonuses->removeElement($bonus);

        return $this;
    }

    /**
     * @return Collection<int, AvatarFrame>
     */
    public function getUnlockedFrames(): Collection
    {
        return $this->unlockedFrames;
    }

    public function addUnlockedFrame(AvatarFrame $unlockedFrame): static
    {
        if (!$this->unlockedFrames->contains($unlockedFrame)) {
            $this->unlockedFrames->add($unlockedFrame);
        }

        return $this;
    }

    public function removeUnlockedFrame(AvatarFrame $unlockedFrame): static
    {
        $this->unlockedFrames->removeElement($unlockedFrame);

        return $this;
    }

    public function getActiveFrame(): ?AvatarFrame
    {
        return $this->activeFrame;
    }

    public function setActiveFrame(?AvatarFrame $activeFrame): static
    {
        $this->activeFrame = $activeFrame;

        return $this;
    }

    public function getConfettiColor(): string
    {
        return $this->confettiColor;
    }

    public function setConfettiColor(string $confettiColor): static
    {
        $this->confettiColor = $confettiColor;

        return $this;
    }
    /**
     * @return Collection<int, Playlist>
     */
    public function getPlaylists(): Collection
    {
        return $this->playlists;
    }

    public function addPlaylist(Playlist $playlist): static
    {
        if (!$this->playlists->contains($playlist)) {
            $this->playlists->add($playlist);
            $playlist->setOwner($this);
        }

        return $this;
    }

    public function removePlaylist(Playlist $playlist): static
    {
        if ($this->playlists->removeElement($playlist)) {
            // set the owning side to null (unless already changed)
            if ($playlist->getOwner() === $this) {
                $playlist->setOwner(null);
            }
        }

        return $this;
    }

    public function getLearningManifesto(): ?string { return $this->learningManifesto; }
    public function setLearningManifesto(?string $learningManifesto): static { $this->learningManifesto = $learningManifesto; return $this; }
    public function getWebsiteUrl(): ?string { return $this->websiteUrl; }
    public function setWebsiteUrl(?string $websiteUrl): static { $this->websiteUrl = $websiteUrl; return $this; }
    public function getGithubUrl(): ?string { return $this->githubUrl; }
    public function setGithubUrl(?string $githubUrl): static { $this->githubUrl = $githubUrl; return $this; }
    public function isProfilePublic(): bool { return $this->isProfilePublic; }
    public function setIsProfilePublic(bool $isProfilePublic): static { $this->isProfilePublic = $isProfilePublic; return $this; }

    public function getCustomGoal(): ?string { return $this->customGoal; }
    public function setCustomGoal(?string $customGoal): static { $this->customGoal = $customGoal; return $this; }

    public function getRssFeedUrl(): ?string { return $this->rssFeedUrl; }
    public function setRssFeedUrl(?string $rssFeedUrl): static { $this->rssFeedUrl = $rssFeedUrl; return $this; }
}
