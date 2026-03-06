<?php

declare(strict_types=1);

namespace App\Twig\Components;

use App\Entity\Course;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('CourseCard')]
final class CourseCard
{
    public Course $course;
    public bool $isSubscribed = false;
}
