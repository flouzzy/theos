<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\AvatarFrameRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AvatarFrameRepository::class)]
class AvatarFrame
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\Column(length: 255, unique: true)]
    private string $identifier;

    #[ORM\Column(length: 255)]
    private string $cssClass;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $imagePath = null;

    #[ORM\Column(options: ['default' => 0])]
    private int $levelRequired = 0;

    /**
     * @var Collection<int, User>
     */
    #[ORM\ManyToMany(targetEntity: User::class, mappedBy: 'unlockedFrames')]
    private Collection $users;

    public function __construct()
    {
        $this->users = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getName(): string { return $this->name; }
    public function setName(string $name): static { $this->name = $name; return $this; }

    public function getIdentifier(): string { return $this->identifier; }
    public function setIdentifier(string $identifier): static { $this->identifier = $identifier; return $this; }

    public function getCssClass(): string { return $this->cssClass; }
    public function setCssClass(string $cssClass): static { $this->cssClass = $cssClass; return $this; }

    public function getImagePath(): ?string { return $this->imagePath; }
    public function setImagePath(?string $imagePath): static { $this->imagePath = $imagePath; return $this; }

    public function getLevelRequired(): int { return $this->levelRequired; }
    public function setLevelRequired(int $levelRequired): static { $this->levelRequired = $levelRequired; return $this; }

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
            $user->addUnlockedFrame($this);
        }

        return $this;
    }

    public function removeUser(User $user): static
    {
        if ($this->users->removeElement($user)) {
            $user->removeUnlockedFrame($this);
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
