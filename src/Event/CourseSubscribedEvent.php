<?php

namespace App\Event;

use App\Entity\Course;
use App\Entity\User;
use Symfony\Contracts\EventDispatcher\Event;

class CourseSubscribedEvent extends Event
{
    public function __construct(
        private Course $course,
        private User $user
    ) {}

    public function getCourse(): Course
    {
        return $this->course;
    }

    public function getUser(): User
    {
        return $this->user;
    }
}
