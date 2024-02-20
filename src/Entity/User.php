<?php

namespace App\Entity;

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
    private ?string $lastname = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private ?string $fullname = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

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


    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->courses = new ArrayCollection();
        $this->modules = new ArrayCollection();
        $this->lessons = new ArrayCollection();
        $this->notes = new ArrayCollection();
        $this->authorCourses = new ArrayCollection();
    }

    #[ORM\PreUpdate]
    public function updateUserDetails(): void
    {
        if ($this->firstname || $this->lastname) {
            $this->fullname = $this->firstname . ' ' . $this->lastname;
        }
    }

    #[ORM\PrePersist]
    public function setUserDetails(): void
    {
        $details = explode(' ', $this->fullname);
        $this->firstname = $this->firstname ?? ($details[0] ?? '');
        $this->lastname = $this->lastname ?? ($details[1] ?? '');
        $this->fullname = $this->fullname ?? $this->firstname . ' ' . $this->lastname;

        // Slug
        $slugger = new AsciiSlugger();
        $this->username = $slugger->slug($this->fullname)->lower();
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

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

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
}
