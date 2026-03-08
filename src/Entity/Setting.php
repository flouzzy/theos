<?php

namespace App\Entity;

use App\Entity\Trait\DateTimeAble;
use App\Repository\SettingRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SettingRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Setting
{
    use DateTimeAble;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $brevoListOnboarded = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $brevoListAlumni = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBrevoListOnboarded(): ?string
    {
        return $this->brevoListOnboarded;
    }

    public function setBrevoListOnboarded(?string $brevoListOnboarded): static
    {
        $this->brevoListOnboarded = $brevoListOnboarded;

        return $this;
    }

    public function getBrevoListAlumni(): ?string
    {
        return $this->brevoListAlumni;
    }

    public function setBrevoListAlumni(?string $brevoListAlumni): static
    {
        $this->brevoListAlumni = $brevoListAlumni;

        return $this;
    }
}
