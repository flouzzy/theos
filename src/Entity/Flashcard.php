<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\FlashcardRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FlashcardRepository::class)]
class Flashcard
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'text')]
    private string $question;

    #[ORM\Column(type: 'text')]
    private string $answer;

    #[ORM\ManyToOne(inversedBy: 'cards')]
    #[ORM\JoinColumn(nullable: false)]
    private FlashcardDeck $deck;

    public function getId(): ?int { return $this->id; }
    public function getQuestion(): string { return $this->question; }
    public function setQuestion(string $question): static { $this->question = $question; return $this; }
    public function getAnswer(): string { return $this->answer; }
    public function setAnswer(string $answer): static { $this->answer = $answer; return $this; }
    public function getDeck(): FlashcardDeck { return $this->deck; }
    public function setDeck(FlashcardDeck $deck): static { $this->deck = $deck; return $this; }
}
