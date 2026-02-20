<?php

// src/Twig/CompletionExtension.php
namespace App\Twig;

use App\Entity\Course;
use App\Entity\User;
use App\Service\CompletionCalculator;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class CompletionExtension extends AbstractExtension
{

    public function __construct(private CompletionCalculator $completionCalculator)
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('completion_percentage', [$this, 'calculateCompletionPercentage']),
        ];
    }

    public function calculateCompletionPercentage(Course $course, User $user): float
    {
        return $this->completionCalculator->calculateCompletionPercentage($course, $user);
    }
}
