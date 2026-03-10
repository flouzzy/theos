<?php

declare(strict_types=1);

namespace App\Message;

final class GenerateLessonEmbeddingMessage
{
    public function __construct(
        private int $lessonId,
    ) {
    }

    public function getLessonId(): int
    {
        return $this->lessonId;
    }
}
