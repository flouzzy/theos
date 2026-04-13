<?php

namespace App\Tests\Unit\Twig\Components;

use App\Entity\Lesson;
use App\Twig\Components\Transcript;
use PHPUnit\Framework\TestCase;

class TranscriptTest extends TestCase
{
    private Transcript $transcriptComponent;

    protected function setUp(): void
    {
        $this->transcriptComponent = new Transcript();
    }

    public function testReturnsEmptyArrayWhenLessonIsNull(): void
    {
        $this->transcriptComponent->lesson = null;
        $this->assertEmpty($this->transcriptComponent->getParsedTranscript());
    }

    public function testReturnsEmptyArrayWhenTranscriptIsNull(): void
    {
        $lesson = $this->createMock(Lesson::class);
        $lesson->method('getTranscript')->willReturn(null);

        $this->transcriptComponent->lesson = $lesson;
        $this->assertEmpty($this->transcriptComponent->getParsedTranscript());
    }

    public function testReturnsEmptyArrayWhenTranscriptIsEmpty(): void
    {
        $lesson = $this->createMock(Lesson::class);
        $lesson->method('getTranscript')->willReturn('');

        $this->transcriptComponent->lesson = $lesson;
        $this->assertEmpty($this->transcriptComponent->getParsedTranscript());
    }

    public function testParsesValidTranscriptLines(): void
    {
        $transcriptText = "[00:00] Introduction\n[01:30] Second part\n[12:45] Conclusion";
        $lesson = $this->createMock(Lesson::class);
        $lesson->method('getTranscript')->willReturn($transcriptText);

        $this->transcriptComponent->lesson = $lesson;
        $result = $this->transcriptComponent->getParsedTranscript();

        $expected = [
            ['timestamp' => '00:00', 'text' => 'Introduction'],
            ['timestamp' => '01:30', 'text' => 'Second part'],
            ['timestamp' => '12:45', 'text' => 'Conclusion'],
        ];

        $this->assertSame($expected, $result);
    }

    public function testSkipsInvalidTranscriptLines(): void
    {
        $transcriptText = "[00:00] Valid line\nInvalid line\n[01:00] Another valid\n[abc] Invalid format";
        $lesson = $this->createMock(Lesson::class);
        $lesson->method('getTranscript')->willReturn($transcriptText);

        $this->transcriptComponent->lesson = $lesson;
        $result = $this->transcriptComponent->getParsedTranscript();

        $expected = [
            ['timestamp' => '00:00', 'text' => 'Valid line'],
            ['timestamp' => '01:00', 'text' => 'Another valid'],
        ];

        $this->assertSame($expected, $result);
    }

    public function testHandlesExtraWhitespace(): void
    {
        $transcriptText = "  [00:00]   Spaced text  \n\n[02:00] End   ";
        $lesson = $this->createMock(Lesson::class);
        $lesson->method('getTranscript')->willReturn($transcriptText);

        $this->transcriptComponent->lesson = $lesson;
        $result = $this->transcriptComponent->getParsedTranscript();

        $expected = [
            ['timestamp' => '00:00', 'text' => 'Spaced text'],
            ['timestamp' => '02:00', 'text' => 'End'],
        ];

        $this->assertSame($expected, $result);
    }

    public function testHandlesWindowsLineEndings(): void
    {
        $transcriptText = "[00:00] Line 1\r\n[00:10] Line 2";
        $lesson = $this->createMock(Lesson::class);
        $lesson->method('getTranscript')->willReturn($transcriptText);

        $this->transcriptComponent->lesson = $lesson;
        $result = $this->transcriptComponent->getParsedTranscript();

        $expected = [
            ['timestamp' => '00:00', 'text' => 'Line 1'],
            ['timestamp' => '00:10', 'text' => 'Line 2'],
        ];

        $this->assertSame($expected, $result);
    }
}
