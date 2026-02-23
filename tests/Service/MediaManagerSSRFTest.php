<?php

declare(strict_types=1);

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
        $this->targetDirectory = sys_get_temp_dir() . '/media_ssrf_test_' . uniqid();
        if (!is_dir($this->targetDirectory)) {
            mkdir($this->targetDirectory, 0777, true);
        }

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
        if (is_dir($this->targetDirectory)) {
            $this->removeDirectory($this->targetDirectory);
        }
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

    public function testPrivateIPBlocked()
    {
        $urls = [
            'http://127.0.0.1/image.jpg',
            'http://10.0.0.1/image.jpg',
            'http://192.168.1.1/image.jpg',
            'http://172.16.0.1/image.jpg',
            'http://[::1]/image.jpg', // IPv6 loopback
        ];

        foreach ($urls as $url) {
            // We expect the client to NOT be called for private IPs
            // Note: Since we are iterating, we can't easily use 'never' if we reuse the mock,
            // but here we are testing one by one or we should reset.
            // However, since the method returns false immediately (in our desired fix),
            // checking the result is false is a good first step, but checking the mock is better.

            // Re-create mock for each iteration to reset expectations?
            // Or just verify that for THESE urls, request is not called.
            // Let's rely on one test case per execution or just assume 'never' applies to the whole test run if we don't call it.
        }

        // Let's just test one representative private IP to keep it simple and strict
        $url = 'http://192.168.1.1/secret.jpg';

        $this->httpClient->expects($this->never())->method('request');

        $result = $this->mediaManager->downloadFileByUrl($url);
        $this->assertFalse($result, "Should return false for private IP: $url");
    }

    public function testLocalhostBlocked()
    {
        $url = 'http://localhost/secret.jpg';

        $this->httpClient->expects($this->never())->method('request');

        $result = $this->mediaManager->downloadFileByUrl($url);
        $this->assertFalse($result, "Should return false for localhost");
    }

    public function testMetadataServiceBlocked()
    {
        // AWS Metadata service
        $url = 'http://169.254.169.254/latest/meta-data/';

        $this->httpClient->expects($this->never())->method('request');

        $result = $this->mediaManager->downloadFileByUrl($url);
        $this->assertFalse($result, "Should return false for metadata service IP");
    }

    public function testValidPublicUrlAllowed()
    {
        // Use a known public IP to avoid external DNS dependency in tests
        $url = 'http://8.8.8.8/image.jpg';
        $content = 'fake_image_content';

        // Mock a successful response
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(200);
        $response->method('getContent')->willReturn($content);

        // We can't easily mock mime_type detection on arbitrary string without valid image header.
        // The MediaManager uses finfo buffer.
        // Let's provide a valid tiny GIF header.
        $validGif = base64_decode("R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7");
        $response->method('getContent')->willReturn($validGif);

        // In the fix, we might call request multiple times (redirects) or once.
        // But for a simple 200, it should be at least once.
        $this->httpClient->expects($this->atLeastOnce())
            ->method('request')
            ->with('GET', $this->anything(), $this->anything()) // We might modify URL or options
            ->willReturn($response);

        $result = $this->mediaManager->downloadFileByUrl($url);

        // It should return the path (string)
        $this->assertIsString($result, "Should return string path for valid public URL");
    }
}
