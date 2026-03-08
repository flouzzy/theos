<?php

namespace App\Message;

class GenerateLessonAudioMessage
{
    public function __construct(
        private int $lessonId,
        private ?string $voiceName = 'Charon',
        private ?string $directorNotes = null
    ) {
    }

    public function getLessonId(): int
    {
        return $this->lessonId;
    }

    public function getVoiceName(): ?string
    {
        return $this->voiceName;
    }

    public function getDirectorNotes(): ?string
    {
        return $this->directorNotes;
    }
}
