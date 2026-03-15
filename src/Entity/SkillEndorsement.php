<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\SkillEndorsementRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SkillEndorsementRepository::class)]
class SkillEndorsement
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $giver;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'receivedEndorsements')]
    #[ORM\JoinColumn(nullable: false)]
    private User $receiver;

    #[ORM\ManyToOne(targetEntity: Skill::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Skill $skill;

    public function getId(): ?int { return $this->id; }
    public function getGiver(): User { return $this->giver; }
    public function setGiver(User $giver): static { $this->giver = $giver; return $this; }
    public function getReceiver(): User { return $this->receiver; }
    public function setReceiver(User $receiver): static { $this->receiver = $receiver; return $this; }
    public function getSkill(): Skill { return $this->skill; }
    public function setSkill(Skill $skill): static { $this->skill = $skill; return $this; }
}
