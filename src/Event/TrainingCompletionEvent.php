<?php

namespace App\Event;

use App\Entity\User;
use App\Entity\Course;
use Symfony\Contracts\EventDispatcher\Event;

class TrainingCompletionEvent extends Event
{
    public const NAME = 'training.completed';

    public function __construct(
        private User $user,
        private Course $course
    ) {
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getCourse(): Course
    {
        return $this->course;
    }
}
