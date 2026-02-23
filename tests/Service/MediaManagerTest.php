<?php

namespace App\Tests\Service;

use App\Service\ImageOptimizer;
use App\Service\MediaManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Filesystem\Exception\IOException;

class MediaManagerTest extends TestCase
{
    private SluggerInterface&MockObject $slugger;
    private ImageOptimizer&MockObject $imageOptimizer;
    private Security&MockObject $security;
    private LoggerInterface&MockObject $logger;
    private HttpClientInterface&MockObject $httpClient;
    private Filesystem&MockObject $filesystem;
    private MediaManager $mediaManager;

    protected function setUp(): void
    {
        $this->slugger = $this->createMock(SluggerInterface::class);
        $this->imageOptimizer = $this->createMock(ImageOptimizer::class);
        $this->security = $this->createMock(Security::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->httpClient = $this->createMock(HttpClientInterface::class);
        $this->filesystem = $this->createMock(Filesystem::class);

        $this->mediaManager = new MediaManager(
            '/tmp/uploads',
            $this->slugger,
            $this->imageOptimizer,
            $this->security,
            $this->logger,
            $this->httpClient,
            $this->filesystem
        );
    }

    public function testDeleteFileRemovesFile(): void
    {
        $fileName = 'test.jpg';
        $mediaType = 'post';
        $expectedPath = '/tmp/uploads/post/test.jpg';

        $this->filesystem->expects($this->once())
            ->method('remove')
            ->with($expectedPath);

        $this->mediaManager->deleteFile($fileName, $mediaType);
    }

    public function testDeleteFileHandlesException(): void
    {
        $fileName = 'test.jpg';
        $mediaType = 'post';
        $expectedPath = '/tmp/uploads/post/test.jpg';

        $this->filesystem->expects($this->once())
            ->method('remove')
            ->with($expectedPath)
            ->willThrowException(new IOException('Failed to remove file'));

        // If the code catches the exception correctly, this should not throw
        $this->mediaManager->deleteFile($fileName, $mediaType);
    }
}
