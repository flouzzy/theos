<?php

declare(strict_types=1);

namespace App\Event;

use App\Entity\Cohort;
use App\Entity\Course;
use Symfony\Contracts\EventDispatcher\Event;

final class CohortContentUnlockedEvent extends Event
{
    public function __construct(
        private Cohort $cohort,
        private Course $course,
    ) {
    }

    public function getCohort(): Cohort
    {
        return $this->cohort;
    }

    public function getCourse(): Course
    {
        return $this->course;
    }
}
