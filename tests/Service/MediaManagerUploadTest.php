<?php

namespace App\Tests\Service;

use App\Service\ImageOptimizer;
use App\Service\MediaManager;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class MediaManagerUploadTest extends TestCase
{
    private $targetDirectory;
    private $mediaManager;

    protected function setUp(): void
    {
        $this->targetDirectory = sys_get_temp_dir() . '/media_upload_test_' . uniqid();
        mkdir($this->targetDirectory . '/public', 0777, true);

        $slugger = new AsciiSlugger();
        $imageOptimizer = $this->createMock(ImageOptimizer::class);
        $security = $this->createMock(Security::class);
        $logger = $this->createMock(LoggerInterface::class);
        $httpClient = $this->createMock(HttpClientInterface::class);

        $this->mediaManager = new MediaManager(
            $this->targetDirectory . '/public',
            $slugger,
            $imageOptimizer,
            $security,
            $logger,
            $httpClient
        );
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->targetDirectory);
    }

    private function removeDirectory($dir)
    {
        if (!is_dir($dir)) {
            return;
        }
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            (is_dir("$dir/$file")) ? $this->removeDirectory("$dir/$file") : unlink("$dir/$file");
        }
        rmdir($dir);
    }

    public function testUploadPhpFileBlocked()
    {
        $file = $this->createMock(UploadedFile::class);
        $file->method('getClientOriginalName')->willReturn('evil.php');
        $file->method('guessExtension')->willReturn('php');

        $this->expectException(FileException::class);
        $this->expectExceptionMessage('Invalid file extension: php');

        $this->mediaManager->upload($file);
    }

    public function testUploadValidImage()
    {
        $file = $this->createMock(UploadedFile::class);
        $file->method('getClientOriginalName')->willReturn('image.jpg');
        $file->method('guessExtension')->willReturn('jpg');

        // We expect move to be called
        $file->expects($this->once())->method('move');

        $result = $this->mediaManager->upload($file);

        $this->assertStringEndsWith('.jpg', $result);
    }
}
