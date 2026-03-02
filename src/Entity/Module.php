<?php

namespace App\Entity;

use App\Entity\Trait\DateTimeAble;
use App\Repository\ModuleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\String\Slugger\AsciiSlugger;

#[ORM\Entity(repositoryClass: ModuleRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Module
{
    use DateTimeAble;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\ManyToMany(targetEntity: Course::class, inversedBy: 'modules')]
    private Collection $courses;

    #[ORM\OneToMany(mappedBy: 'module', targetEntity: Lesson::class, orphanRemoval: true)]
    private Collection $lessons;

    #[ORM\ManyToOne(inversedBy: 'modules')]
    private ?User $author = null;

    #[ORM\Column(length: 255)]
    private ?string $slug = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $status = 'draft';

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    private ?int $itemOrder = null;

    #[ORM\OneToMany(mappedBy: 'module', targetEntity: ModuleCompletion::class, orphanRemoval: true)]
    private Collection $completions;

    public function __construct()
    {
        $this->courses = new ArrayCollection();
        $this->lessons = new ArrayCollection();
        $this->completions = new ArrayCollection();
    }

    #[ORM\PrePersist]
    public function computeSlug(): void
    {
        // Slug
        $slugger = new AsciiSlugger();
        $this->slug = $slugger->slug(substr($this->title, 0, 20))->lower();
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

    /**
     * @return Collection<int, Course>
     */
    public function getCourses(): Collection
    {
        return $this->courses;
    }

    public function getCourse(): ?Course
    {
        return $this->courses->first() ?: null;
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
            $lesson->setModule($this);
        }

        return $this;
    }

    public function removeLesson(Lesson $lesson): static
    {
        if ($this->lessons->removeElement($lesson)) {
            // set the owning side to null (unless already changed)
            if ($lesson->getModule() === $this) {
                $lesson->setModule(null);
            }
        }

        return $this;
    }

    public function getAuthor(): ?User
    {
        return $this->author;
    }

    public function setAuthor(?User $author): static
    {
        $this->author = $author;

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;

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

    public function getItemOrder(): ?int
    {
        return $this->itemOrder;
    }

    public function setItemOrder(?int $itemOrder): static
    {
        $this->itemOrder = $itemOrder;

        return $this;
    }

    /**
     * @return Collection<int, ModuleCompletion>
     */
    public function getCompletions(): Collection
    {
        return $this->completions;
    }

    public function addCompletion(ModuleCompletion $completion): static
    {
        if (!$this->completions->contains($completion)) {
            $this->completions->add($completion);
            $completion->setModule($this);
        }

        return $this;
    }

    public function removeCompletion(ModuleCompletion $completion): static
    {
        if ($this->completions->removeElement($completion)) {
            // set the owning side to null (unless already changed)
            if ($completion->getModule() === $this) {
                $completion->setModule(null);
            }
        }

        return $this;
    }
}
