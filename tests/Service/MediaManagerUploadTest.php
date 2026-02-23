<?php

namespace App\Tests\Service;

use App\Service\ImageOptimizer;
use App\Service\MediaManager;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use App\Entity\User;

class MediaManagerUploadTest extends TestCase
{
    private $targetDirectory;
    private $slugger;
    private $imageOptimizer;
    private $security;
    private $logger;
    private $httpClient;
    private $mediaManager;

    protected function setUp(): void
    {
        $this->targetDirectory = __DIR__ . '/public/uploads'; // Mock path for testing logic
        $this->slugger = $this->createMock(SluggerInterface::class);
        $this->imageOptimizer = $this->createMock(ImageOptimizer::class);
        $this->security = $this->createMock(Security::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->httpClient = $this->createMock(HttpClientInterface::class);

        $this->mediaManager = new MediaManager(
            $this->targetDirectory,
            $this->slugger,
            $this->imageOptimizer,
            $this->security,
            $this->logger,
            $this->httpClient
        );
    }

    public function testUploadSuccess()
    {
        $originalName = 'test-image.jpg';
        $safeFilename = 'test-image';
        $extension = 'jpg';
        $expectedPathPart = 'uploads/course'; // 'course' is default mediaType

        $file = $this->createMock(UploadedFile::class);
        $file->method('getClientOriginalName')->willReturn($originalName);
        $file->method('guessExtension')->willReturn($extension);

        // Mock slugger behavior
        $this->slugger->expects($this->once())
            ->method('slug')
            ->with('test-image') // pathinfo filename
            ->willReturn(new \Symfony\Component\String\UnicodeString($safeFilename));

        // Expect move to be called
        $file->expects($this->once())
            ->method('move')
            ->with(
                $this->stringContains($this->targetDirectory . '/course'),
                $this->stringContains($safeFilename)
            );

        // Expect resize to be called
        $this->imageOptimizer->expects($this->once())
            ->method('resize')
            ->with(
                $this->stringContains($this->targetDirectory . '/course'),
                []
            );

        $result = $this->mediaManager->upload($file);

        $this->assertStringContainsString($expectedPathPart, $result);
        $this->assertStringEndsWith('.jpg', $result);
    }

    public function testUploadHandlesFileExceptionWithUser()
    {
        $originalName = 'error-image.jpg';
        $file = $this->createMock(UploadedFile::class);
        $file->method('getClientOriginalName')->willReturn($originalName);
        $file->method('guessExtension')->willReturn('jpg');

        $this->slugger->method('slug')->willReturn(new \Symfony\Component\String\UnicodeString('error-image'));

        $exceptionMessage = 'Upload failed';
        $file->method('move')->willThrowException(new FileException($exceptionMessage));

        $user = $this->createMock(User::class);
        $user->method('getEmail')->willReturn('user@example.com');
        $this->security->method('getUser')->willReturn($user);

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                $this->stringContains('Failed to upload file'),
                $this->callback(function ($context) use ($exceptionMessage) {
                    return $context['user_email'] === 'user@example.com' &&
                           $context['error_message'] === $exceptionMessage;
                })
            );

        $result = $this->mediaManager->upload($file);

        // Ensure result is returned even on failure (as per current implementation)
        $this->assertNotNull($result);
        $this->assertStringContainsString('uploads/course', $result);
    }

    public function testUploadHandlesFileExceptionWithoutUser()
    {
        $originalName = 'anon-error.jpg';
        $file = $this->createMock(UploadedFile::class);
        $file->method('getClientOriginalName')->willReturn($originalName);
        $file->method('guessExtension')->willReturn('jpg');

        $this->slugger->method('slug')->willReturn(new \Symfony\Component\String\UnicodeString('anon-error'));

        $file->method('move')->willThrowException(new FileException('Anon upload failed'));

        $this->security->method('getUser')->willReturn(null);

        // Expect error log with "anonymous" or handled null user
        $this->logger->expects($this->once())
            ->method('error');

        // This call will likely crash if the bug is present
        $result = $this->mediaManager->upload($file);

        $this->assertNotNull($result);
    }
}
