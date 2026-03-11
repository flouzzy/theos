<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Lesson;
use App\Entity\Module;
use App\Entity\Quiz;
use App\Service\QuizGeneratorService;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

class QuizGeneratorServiceTest extends TestCase
{
    public function testGenerateQuiz(): void
    {
        $lesson = $this->createMock(Lesson::class);
        $lesson->method('getTitle')->willReturn('Test Lesson');
        $lesson->method('getContent')->willReturn('Test Content');
        $lesson->method('getTranscript')->willReturn(null);
        
        $module = $this->createMock(Module::class);
        $lesson->method('getModule')->willReturn($module);

        $mockClient = $this->createMock(Client::class);
        $quizData = [
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            [
                                'text' => json_encode([
                                    'title' => 'AI Generated Quiz',
                                    'questions' => [
                                        [
                                            'text' => 'What is testing?',
                                            'options' => [
                                                ['text' => 'A way to find bugs', 'isCorrect' => true],
                                                ['text' => 'A waste of time', 'isCorrect' => false],
                                            ]
                                        ]
                                    ]
                                ])
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $mockResponse = new Response(200, [], json_encode($quizData));

        $mockClient->expects($this->once())
            ->method('post')
            ->willReturn($mockResponse);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->atLeastOnce())
            ->method('persist');
        $entityManager->expects($this->once())
            ->method('flush');

        $service = new QuizGeneratorService('fake_key', $entityManager, $mockClient);
        $quiz = $service->generateQuiz($lesson);

        $this->assertInstanceOf(Quiz::class, $quiz);
        $this->assertEquals('AI Generated Quiz', $quiz->getTitle());
        $this->assertCount(1, $quiz->getQuestions());
    }
}
