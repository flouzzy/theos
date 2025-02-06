<?php

namespace App\Event;

use App\Entity\Lesson;
use Symfony\Contracts\EventDispatcher\Event;

final class LessonCompleteEvent extends Event
{

    public function __construct(private Lesson $lesson, private bool $completed = false)
    {
    }

    public function getLesson(): Lesson
    {

        return $this->lesson;
    }

    public function getCompleted(): bool
    {

        return $this->completed;
    }
}
