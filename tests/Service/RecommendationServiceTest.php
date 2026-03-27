<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Lesson;
use App\Repository\LessonRepository;
use App\Service\RecommendationService;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class RecommendationServiceTest extends TestCase
{
    public function testUpdateLessonEmbedding(): void
    {
        $lesson = new Lesson();
        $lesson->setTitle('Test Lesson');
        $lesson->setDescription('Test Description');
        $lesson->setContent('<p>Test Content</p>');

        $mockClient = $this->createMock(Client::class);
        $mockResponse = new Response(200, [], json_encode([
            'embedding' => [
                'values' => [0.1, 0.2, 0.3]
            ]
        ]));

        $mockClient->expects($this->once())
            ->method('post')
            ->willReturn($mockResponse);

        $lessonRepository = $this->createMock(LessonRepository::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $logger = $this->createMock(LoggerInterface::class);

        $service = new RecommendationService('fake_key', $lessonRepository, $entityManager, $logger, $mockClient);
        $service->updateLessonEmbedding($lesson);

        $this->assertEquals([0.1, 0.2, 0.3], $lesson->getEmbeddings());
    }

    public function testGetRecommendations(): void
    {
        $lesson1 = $this->createMock(Lesson::class);
        $lesson1->method('getId')->willReturn(1);
        $lesson1->method('getEmbeddings')->willReturn([1.0, 0.0, 0.0]);

        $lesson2 = $this->createMock(Lesson::class);
        $lesson2->method('getId')->willReturn(2);
        $lesson2->method('getEmbeddings')->willReturn([0.9, 0.1, 0.0]);

        $lesson3 = $this->createMock(Lesson::class);
        $lesson3->method('getId')->willReturn(3);
        $lesson3->method('getEmbeddings')->willReturn([0.0, 1.0, 0.0]);

        $lessonRepository = $this->createMock(LessonRepository::class);
        $lessonRepository->method('findAll')->willReturn([$lesson1, $lesson2, $lesson3]);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $mockClient = $this->createMock(Client::class);
        $logger = $this->createMock(LoggerInterface::class);

        $service = new RecommendationService('fake_key', $lessonRepository, $entityManager, $logger, $mockClient);
        $recommendations = $service->getRecommendations($lesson1, 1);

        $this->assertCount(1, $recommendations);
        $this->assertSame($lesson2, $recommendations[0]);
    }
}
