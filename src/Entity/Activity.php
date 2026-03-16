<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Trait\DateTimeAble;
use App\Repository\ActivityRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ActivityRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Activity
{
    use DateTimeAble;

    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\Column(length: 255)]
    private string $action; // badge_earned, lesson_completed

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $targetName = null;

    public function getId(): ?int { return $this->id; }
    public function getUser(): User { return $this->user; }
    public function setUser(User $user): static { $this->user = $user; return $this; }
    public function getAction(): string { return $this->action; }
    public function setAction(string $action): static { $this->action = $action; return $this; }
    public function getTargetName(): ?string { return $this->targetName; }
    public function setTargetName(?string $targetName): static { $this->targetName = $targetName; return $this; }
}
