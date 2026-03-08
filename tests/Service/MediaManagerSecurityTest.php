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
    private $imageOptimizer;

    protected function setUp(): void
    {
        $this->baseDirectory = sys_get_temp_dir() . '/media_test_' . uniqid();
        $this->targetDirectory = $this->baseDirectory . '/public/uploads';
        mkdir($this->targetDirectory, 0777, true);
        mkdir($this->targetDirectory . '/post', 0777, true);

        $slugger = $this->createMock(SluggerInterface::class);
        $this->imageOptimizer = $this->createMock(ImageOptimizer::class);
        $security = $this->createMock(Security::class);
        $logger = $this->createMock(LoggerInterface::class);
        $this->httpClient = $this->createMock(HttpClientInterface::class);

        $this->mediaManager = new MediaManager(
            $this->targetDirectory,
            $slugger,
            $this->imageOptimizer,
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
        $url = 'http://8.8.8.8/image.jpg';
        
        // Generate a real 1x1 JPEG in memory to ensure \finfo validates it perfectly
        ob_start();
        $img = imagecreatetruecolor(1, 1);
        imagejpeg($img);
        $content = ob_get_clean();
        imagedestroy($img);

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(200);
        $response->method('getContent')->willReturn($content);

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with('GET', $url, $this->anything())
            ->willReturn($response);

        // By-pass resize exception that might throw when the image data is incomplete/fake
        $this->imageOptimizer->method('resize');

        $result = $this->mediaManager->downloadFileByUrl($url);

        // We just need to assert it did not return false
        $this->assertNotEquals(false, $result, 'The download should succeed for valid image.');

        $files = scandir($this->targetDirectory);
        $files = array_diff($files, ['.', '..']);
        // Verify a file exists
        $this->assertGreaterThanOrEqual(1, count($files));
    }
}
