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

class MediaManagerSSRFTest extends TestCase
{
    private $targetDirectory;
    private $mediaManager;
    private $httpClient;

    protected function setUp(): void
    {
        $this->targetDirectory = sys_get_temp_dir() . '/media_test_' . uniqid();
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

    public function testPrivateIpIsBlocked()
    {
        $url = 'http://192.168.1.5/image.jpg';

        // Expectation: The request should NOT be made to a private IP
        $this->httpClient->expects($this->never())
            ->method('request');

        $result = $this->mediaManager->downloadFileByUrl($url);
        $this->assertFalse($result, 'Download from private IP should return false');
    }

    public function testLocalhostIsBlocked()
    {
        $url = 'http://localhost/image.jpg';

        // Expectation: The request should NOT be made to localhost
        $this->httpClient->expects($this->never())
            ->method('request');

        $result = $this->mediaManager->downloadFileByUrl($url);
        $this->assertFalse($result, 'Download from localhost should return false');
    }

    public function testLoopbackIpIsBlocked()
    {
        $url = 'http://127.0.0.1/image.jpg';

        // Expectation: The request should NOT be made to loopback IP
        $this->httpClient->expects($this->never())
            ->method('request');

        $result = $this->mediaManager->downloadFileByUrl($url);
        $this->assertFalse($result, 'Download from 127.0.0.1 should return false');
    }

    public function testPublicIpIsAllowed()
    {
        $url = 'http://8.8.8.8/image.jpg';
        $content = 'image-content';

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(200);
        $response->method('getContent')->willReturn($content);

        // We expect a request to be made.
        // Note: The implementation details might change (resolve option),
        // but for now we just verify it IS called.
        $this->httpClient->expects($this->once())
            ->method('request')
            ->willReturn($response);

        // Mock finfo to return valid image type for the fake content
        // Since we can't easily mock native finfo in the class without refactoring,
        // we might fail at the finfo check inside downloadFileByUrl.
        // However, the test here is mainly about whether HttpClient::request IS CALLED.
        // Whether it returns false later due to mime type is secondary for SSRF check.

        $this->mediaManager->downloadFileByUrl($url);
    }
}
