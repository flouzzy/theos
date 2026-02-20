<?php

namespace App\Event;

use App\Entity\Lesson;
use App\Entity\User;
use Symfony\Contracts\EventDispatcher\Event;

final class LessonCompleteEvent extends Event
{
    public function __construct(
        private Lesson $lesson,
        private User $user,
        private bool $completed = false,
        private bool $previouslyCompleted = false
    ) {
    }

    public function getLesson(): Lesson
    {
        return $this->lesson;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getCompleted(): bool
    {
        return $this->completed;
    }

    public function isPreviouslyCompleted(): bool
    {
        return $this->previouslyCompleted;
    }
}
