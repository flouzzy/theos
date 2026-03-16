<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\TriviaQuestionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TriviaQuestionRepository::class)]
class TriviaQuestion
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT)]
    private string $question;

    #[ORM\Column(type: Types::JSON)]
    private array $options = [];

    #[ORM\Column(length: 1)]
    private string $correctAnswer;

    public function getId(): ?int { return $this->id; }

    public function getQuestion(): string { return $this->question; }
    public function setQuestion(string $question): static { $this->question = $question; return $this; }

    public function getOptions(): array { return $this->options; }
    public function setOptions(array $options): static { $this->options = $options; return $this; }

    public function getCorrectAnswer(): string { return $this->correctAnswer; }
    public function setCorrectAnswer(string $correctAnswer): static { $this->correctAnswer = $correctAnswer; return $this; }
}
