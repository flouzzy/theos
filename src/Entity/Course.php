<?php

namespace App\Entity;

use App\Entity\Trait\DateTimeAble;
use App\Repository\CourseRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\String\Slugger\AsciiSlugger;

#[ORM\Entity(repositoryClass: CourseRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Course
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


    #[ORM\ManyToMany(targetEntity: User::class, inversedBy: 'courses')]
    private Collection $users;

    #[ORM\ManyToMany(targetEntity: Module::class, mappedBy: 'courses')]
    private Collection $modules;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = 'images/default-course.png';

    #[ORM\ManyToOne(inversedBy: 'authorCourses')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $author = null;

    #[ORM\Column(length: 255)]
    private ?string $slug = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $status = 'draft';

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    private ?int $itemOrder = null;

    #[ORM\OneToMany(mappedBy: 'course', targetEntity: CourseCompletion::class)]
    private Collection $completions;

    #[ORM\ManyToMany(targetEntity: Cohort::class, mappedBy: 'courses')]
    private Collection $cohorts;

    #[ORM\ManyToMany(targetEntity: Skill::class, mappedBy: 'courses')]
    private Collection $skills;

    public function __construct()
    {
        $this->users = new ArrayCollection();
        $this->modules = new ArrayCollection();
        $this->completions = new ArrayCollection();
        $this->cohorts = new ArrayCollection();
        $this->skills = new ArrayCollection();
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


    public function isUserSubscribed(User $user): bool
    {
        return $this->users->contains($user);
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

    /**
     * @return Collection<int, Module>
     */
    public function getModules(): Collection
    {
        return $this->modules;
    }

    /**
     * @return Collection<int, Lesson>
     */
    public function getLessons(): Collection
    {
        $lessons = new ArrayCollection();
        foreach ($this->modules as $module) {
            foreach ($module->getLessons() as $lesson) {
                if (!$lessons->contains($lesson)) {
                    $lessons->add($lesson);
                }
            }
        }

        return $lessons;
    }

    public function addModule(Module $module): static
    {
        if (!$this->modules->contains($module)) {
            $this->modules->add($module);
            $module->addCourse($this);
        }

        return $this;
    }

    public function removeModule(Module $module): static
    {
        if ($this->modules->removeElement($module)) {
            $module->removeCourse($this);
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

    public function setStatus(?string $status): static
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
     * @return Collection<int, CourseCompletion>
     */
    public function getCompletions(): Collection
    {
        return $this->completions;
    }

    public function addCompletion(CourseCompletion $completion): static
    {
        if (!$this->completions->contains($completion)) {
            $this->completions->add($completion);
            $completion->setCourse($this);
        }

        return $this;
    }

    public function removeCompletion(CourseCompletion $completion): static
    {
        if ($this->completions->removeElement($completion)) {
            // set the owning side to null (unless already changed)
            if ($completion->getCourse() === $this) {
                $completion->setCourse(null);
            }
        }

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
            $cohort->addCourse($this);
        }

        return $this;
    }

    public function removeCohort(Cohort $cohort): static
    {
        if ($this->cohorts->removeElement($cohort)) {
            $cohort->removeCourse($this);
        }

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
            $skill->addCourse($this);
        }

        return $this;
    }

    public function removeSkill(Skill $skill): static
    {
        if ($this->skills->removeElement($skill)) {
            $skill->removeCourse($this);
        }

        return $this;
    }
}
