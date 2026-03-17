<?php

namespace App\Twig\Components;

use App\Entity\Lesson;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\Attribute\LiveProp;

#[AsLiveComponent('Transcript')]
class Transcript
{
    use DefaultActionTrait;

    #[LiveProp]
    public ?Lesson $lesson = null;

    /**
     * Parse the transcript text into an array of [timestamp, text]
     * Format expected: [00:00] Text here
     * 
     * @return array<int, array{timestamp: string, text: string}>
     */
    public function getParsedTranscript(): array
    {
        if (!$this->lesson || !$this->lesson->getTranscript()) {
            return [];
        }

        $lines = explode("\n", $this->lesson->getTranscript());
        $parsed = [];

        foreach ($lines as $line) {
            if (preg_match('/^\[(\d{2}:\d{2})\]\s*(.*)$/', trim($line), $matches)) {
                $parsed[] = [
                    'timestamp' => $matches[1],
                    'text' => $matches[2]
                ];
            }
        }

        return $parsed;
    }
}
