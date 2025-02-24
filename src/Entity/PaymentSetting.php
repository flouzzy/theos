<?php

namespace App\Entity;

use App\Entity\Trait\DateTimeAble;
use App\Repository\PaymentSettingRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PaymentSettingRepository::class)]
#[ORM\HasLifecycleCallbacks]
class PaymentSetting
{

    use DateTimeAble;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $rib = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $checkOrder = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $receptionAddress = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $note = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRib(): ?string
    {
        return $this->rib;
    }

    public function setRib(?string $rib): static
    {
        $this->rib = $rib;

        return $this;
    }

    public function getCheckOrder(): ?string
    {
        return $this->checkOrder;
    }

    public function setCheckOrder(?string $checkOrder): static
    {
        $this->checkOrder = $checkOrder;

        return $this;
    }

    public function getReceptionAddress(): ?string
    {
        return $this->receptionAddress;
    }

    public function setReceptionAddress(?string $receptionAddress): static
    {
        $this->receptionAddress = $receptionAddress;

        return $this;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(?string $note): static
    {
        $this->note = $note;

        return $this;
    }
}
