<?php

namespace App\Tests\Service;

use App\Service\ImageOptimizer;
use App\Service\MediaManager;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class MediaManagerSecurityTest extends TestCase
{
    private $targetDirectory;
    private $baseDirectory;
    private $mediaManager;
    private $httpClient;

    protected function setUp(): void
    {
        $this->baseDirectory = sys_get_temp_dir() . '/media_test_' . uniqid();
        $this->targetDirectory = $this->baseDirectory . '/public/uploads';
        mkdir($this->targetDirectory, 0777, true);

        $slugger = $this->createMock(SluggerInterface::class);
        $imageOptimizer = $this->createMock(ImageOptimizer::class);
        $security = $this->createMock(Security::class);
        $logger = $this->createMock(LoggerInterface::class);
        $this->httpClient = $this->createMock(HttpClientInterface::class);

        $this->mediaManager = new MediaManager(
            $this->targetDirectory,
            $slugger,
            $imageOptimizer,
            $security,
            $logger,
            $this->httpClient
        );
    }

    protected function tearDown(): void
    {
        // Recursively remove base directory
        $this->removeDirectory($this->baseDirectory);
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

    public function testArbitraryFileWriteIsBlocked()
    {
        $maliciousFilename = 'shell.php';
        $url = 'file://' . sys_get_temp_dir() . '/' . $maliciousFilename;

        // The MediaManager should check protocol before calling HttpClient
        // So HttpClient should NOT be called.
        $this->httpClient->expects($this->never())->method('request');

        $result = $this->mediaManager->downloadFileByUrl($url);

        $this->assertFalse($result, 'The download should fail for file:// protocol.');
    }

    public function testNonImageContentIsBlocked()
    {
        $url = 'http://example.com/file.txt';
        $content = 'This is text.';

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(200);
        $response->method('getContent')->willReturn($content);

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with('GET', $url, $this->anything())
            ->willReturn($response);

        $result = $this->mediaManager->downloadFileByUrl($url);
        $this->assertFalse($result, 'The download should fail for non-image content.');
    }

    public function testValidImageDownload()
    {
        $url = 'http://example.com/image.jpg';
        $content = base64_decode('/9j/4AAQSkZJRgABAQEAYABgAAD/2wBDAAgGBgcGBQgHBwcJCQgKDBQNDAsLDBkSEw8UHRofHh0aHBwgJC4nICIsIxwcKDcpLDAxNDQ0Hyc5PTgyPC4zNDL/2wBDAQkJCQwLDBgNDRgyIRwhMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjL/wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAf/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/8QAFAEBAAAAAAAAAAAAAAAAAAAAAP/EABQRAQAAAAAAAAAAAAAAAAAAAAD/2gAMAwEAAhEDEQA/AL+AD//Z');

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(200);
        $response->method('getContent')->willReturn($content);

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with('GET', $url, $this->anything())
            ->willReturn($response);

        $result = $this->mediaManager->downloadFileByUrl($url);

        $this->assertNotFalse($result, 'The download should succeed for valid image.');

        $files = scandir($this->targetDirectory);
        $files = array_diff($files, ['.', '..']);
        $this->assertCount(1, $files);
        $downloadedFile = reset($files);
        $this->assertStringEndsWith('.jpg', $downloadedFile);
    }
}
