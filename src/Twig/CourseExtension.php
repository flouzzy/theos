<?php

// src/Twig/CourseExtension.php
namespace App\Twig;

use App\Entity\Course;
use App\Entity\User;
use App\Service\CourseCompletionCalculator;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class CourseExtension extends AbstractExtension
{

    public function __construct(private CourseCompletionCalculator $completionCalculator)
    {
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('completion_percentage', [$this, 'calculateCompletionPercentage']),
        ];
        // Utilisez getFunctions() pour une fonction Twig
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
