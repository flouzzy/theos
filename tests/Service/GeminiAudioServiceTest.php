<?php

namespace App\Tests\Service;

use App\Entity\Lesson;
use App\Service\GeminiAudioService;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class TestGeminiAudioService extends GeminiAudioService
{
    public ?Process $mockedProcess = null;

    protected function createProcess(array $command): Process
    {
        return $this->mockedProcess ?? parent::createProcess($command);
    }
}

class GeminiAudioServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject $entityManager;
    private Client&MockObject $client;
    private string $audioDir;
    private TestGeminiAudioService $service;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->client = $this->createMock(Client::class);
        $this->audioDir = sys_get_temp_dir() . '/test_audio';

        if (!is_dir($this->audioDir)) {
            mkdir($this->audioDir, 0777, true);
        }

        $this->service = new TestGeminiAudioService(
            'test_api_key',
            $this->entityManager,
            $this->audioDir,
            $this->client
        );
    }

    protected function tearDown(): void
    {
        // Clean up any test mp3 files
        $files = glob($this->audioDir . '/*.mp3');
        foreach ($files as $file) {
            unlink($file);
        }
    }

    public function testGenerateAudioSuccess(): void
    {
        $lesson = new Lesson();
        $lesson->setTitle('Test Lesson');
        $lesson->setSlug('test-lesson');
        $lesson->setContent('<p>Hello world.</p>');

        // Mock HTTP response
        $base64Data = base64_encode('fake_pcm_data');
        $responseBody = json_encode([
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            [
                                'inlineData' => [
                                    'data' => $base64Data
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ]);

        $stream = $this->createMock(StreamInterface::class);
        $stream->method('getContents')->willReturn($responseBody);
        $response = $this->createMock(\Psr\Http\Message\ResponseInterface::class);
        $response->method('getBody')->willReturn($stream);

        $this->client->expects($this->once())
            ->method('post')
            ->willReturn($response);

        // Mock Process for ffmpeg and ffprobe
        $mockProcess = $this->createMock(Process::class);
        $mockProcess->method('mustRun')->willReturnSelf();
        $mockProcess->method('getOutput')->willReturn('120');

        $this->service->mockedProcess = $mockProcess;

        $this->entityManager->expects($this->once())->method('flush');

        $resultPath = $this->service->generateAudio($lesson);

        $this->assertStringStartsWith('uploads/audio/lessons/test-lesson-', $resultPath);
        $this->assertStringEndsWith('.mp3', $resultPath);
        $this->assertEquals(120, $lesson->getAudioDuration());
        $this->assertEquals($resultPath, $lesson->getAudioPath());
    }

    public function testGenerateAudioMissingData(): void
    {
        $lesson = new Lesson();
        $lesson->setContent('No data');

        $responseBody = json_encode(['candidates' => []]);
        $stream = $this->createMock(StreamInterface::class);
        $stream->method('getContents')->willReturn($responseBody);
        $response = $this->createMock(\Psr\Http\Message\ResponseInterface::class);
        $response->method('getBody')->willReturn($stream);

        $this->client->expects($this->once())
            ->method('post')
            ->willReturn($response);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Aucune donnée audio reçue de Gemini.');

        $this->service->generateAudio($lesson);
    }

    public function testGenerateAudioFfmpegFails(): void
    {
        $lesson = new Lesson();
        $lesson->setSlug('ffmpeg-fail');
        $lesson->setContent('Fail data');

        $base64Data = base64_encode('fake_pcm_data');
        $responseBody = json_encode([
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            [
                                'inlineData' => [
                                    'data' => $base64Data
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ]);

        $stream = $this->createMock(StreamInterface::class);
        $stream->method('getContents')->willReturn($responseBody);
        $response = $this->createMock(\Psr\Http\Message\ResponseInterface::class);
        $response->method('getBody')->willReturn($stream);

        $this->client->expects($this->once())
            ->method('post')
            ->willReturn($response);

        $mockProcess = $this->createMock(Process::class);

        $exception = new ProcessFailedException($this->createMock(Process::class));
        $mockProcess->method('mustRun')->willThrowException($exception);

        $this->service->mockedProcess = $mockProcess;

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('La conversion ffmpeg a échoué');

        $this->service->generateAudio($lesson);
    }
}
