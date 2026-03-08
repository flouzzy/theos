<?php

namespace App\Tests\Unit;

use App\Entity\Lesson;
use App\Service\GeminiAudioService;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

class GeminiAudioServiceTest extends TestCase
{
    private $entityManager;
    private $client;
    private $audioDir;
    private $service;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->client = $this->createMock(Client::class);
        $this->audioDir = sys_get_temp_dir() . '/academie_tests_audio';
        if (!is_dir($this->audioDir)) {
            mkdir($this->audioDir, 0777, true);
        }

        // We use a anonymous class to override createProcess
        $this->service = new class('test_api_key', $this->entityManager, $this->audioDir, $this->client) extends GeminiAudioService {
            public $mockProcess;
            public $mockProbeProcess;
            protected function createProcess(array $command): Process
            {
                if ($command[0] === 'ffmpeg') {
                    return $this->mockProcess;
                }
                return $this->mockProbeProcess;
            }
        };
    }

    public function testGenerateAudio(): void
    {
        $lesson = $this->createMock(Lesson::class);
        $lesson->method('getContent')->willReturn('<p>Hello world</p>');
        $lesson->method('getSlug')->willReturn('hello-world');

        $apiResponse = [
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            [
                                'inlineData' => [
                                    'data' => base64_encode('fake_pcm_data')
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $this->client->method('post')->willReturn(new Response(200, [], json_encode($apiResponse)));

        $mockProcess = $this->createMock(Process::class);
        $mockProcess->method('mustRun')->willReturn($mockProcess);
        $this->service->mockProcess = $mockProcess;

        $mockProbeProcess = $this->createMock(Process::class);
        $mockProbeProcess->method('mustRun')->willReturn($mockProbeProcess);
        $mockProbeProcess->method('getOutput')->willReturn('120');
        $this->service->mockProbeProcess = $mockProbeProcess;

        $lesson->expects($this->once())->method('setAudioPath')->with($this->stringContains('uploads/audio/lessons/hello-world'));
        $lesson->method('getAudioPath')->willReturn('uploads/audio/lessons/hello-world-fake.mp3');
        $lesson->expects($this->once())->method('setAudioDuration')->with(120);
        $this->entityManager->expects($this->once())->method('flush');

        $result = $this->service->generateAudio($lesson);

        $this->assertStringContainsString('uploads/audio/lessons/hello-world', $result);
    }

    protected function tearDown(): void
    {
        // Cleanup test audio dir
        $files = glob($this->audioDir . '/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        if (is_dir($this->audioDir)) {
            rmdir($this->audioDir);
        }
    }
}
