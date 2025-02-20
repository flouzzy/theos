<?php

namespace App\Entity;

use App\Entity\Trait\DateTimeAble;
use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ORM\Table(name: '`user`')]
#[UniqueEntity(fields: ['email'], message: 'There is already an account with this email')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    use DateTimeAble;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    #[Assert\NotBlank]
    private ?string $email = null;

    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(type: 'boolean')]
    private $isVerified = false;

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

    #[ORM\ManyToMany(targetEntity: Course::class, mappedBy: 'users')]
    private Collection $courses;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = 'images/default-user.png';

    #[ORM\OneToMany(mappedBy: 'author', targetEntity: Module::class, orphanRemoval: true)]
    private Collection $modules;

    #[ORM\OneToMany(mappedBy: 'author', targetEntity: Lesson::class, orphanRemoval: true)]
    private Collection $lessons;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $bio = null;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Note::class, orphanRemoval: true)]
    private Collection $notes;

    #[ORM\OneToMany(mappedBy: 'author', targetEntity: Course::class, orphanRemoval: true)]
    private Collection $authorCourses;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Completion::class, orphanRemoval: true)]
    private Collection $completions;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: CourseCompletion::class)]
    private Collection $courseCompletions;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: ModuleCompletion::class)]
    private Collection $moduleCompletions;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Notification::class)]
    private Collection $notifications;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $birthDate = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $address = null;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Comment::class, orphanRemoval: true)]
    private Collection $comments;

    #[ORM\ManyToMany(targetEntity: Badge::class, mappedBy: 'users')]
    private Collection $badges;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    private ?int $score = null;

    #[ORM\ManyToMany(targetEntity: Cohort::class, inversedBy: 'users')]
    private Collection $cohorts;


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
    }

    public function __toString()
    {
        return $this->fullname;
    }

    #[ORM\PreUpdate]
    public function updateUserDetails(): void
    {
        if ($this->firstname || $this->lastname) {
            $this->fullname = $this->lastname . ' ' . $this->firstname;
        }
    }

    #[ORM\PrePersist]
    public function setUserDetails(): void
    {
        $details = explode(' ', $this->fullname);
        $this->lastname = $this->lastname ?? ($details[0] ?? '');
        $this->firstname = $this->firstname ?? ($details[1] ?? '');
        $this->fullname = $this->fullname ?? $this->lastname . ' ' . $this->firstname;

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
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
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
            $authorCourse->setUser($this);
        }

        return $this;
    }

    public function removeAuthorCourse(Course $authorCourse): static
    {
        if ($this->authorCourses->removeElement($authorCourse)) {
            // set the owning side to null (unless already changed)
            if ($authorCourse->getUser() === $this) {
                $authorCourse->setUser(null);
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
        }

        return $this;
    }
}
