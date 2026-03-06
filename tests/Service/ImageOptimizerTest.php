<?php

namespace App\Tests\Service;

use App\Entity\User;
use App\Service\ImageOptimizer;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;

class ImageOptimizerTest extends TestCase
{
    private $logger;
    private $security;
    private $imageOptimizer;
    private $tempDir;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->security = $this->createMock(Security::class);

        $this->imageOptimizer = new ImageOptimizer($this->logger, $this->security);

        $this->tempDir = sys_get_temp_dir() . '/image_optimizer_test_' . uniqid();
        if (!is_dir($this->tempDir)) {
            mkdir($this->tempDir);
        }
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
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

    public function testResizeHandlesExceptionGracefully()
    {
        $this->markTestSkipped('Cannot test file read-only exception while running as root in Docker container.');
        // 1. Create a valid image file
        $filename = $this->tempDir . '/test.jpg';
        $image = imagecreatetruecolor(10, 10);
        imagejpeg($image, $filename);
        imagedestroy($image);

        // 2. Make it read-only to force save() to fail
        chmod($filename, 0444);

        // 3. Configure Security Expectation
        $user = $this->createMock(User::class);
        $user->method('getEmail')->willReturn('test@example.com');

        $this->security->method('getUser')->willReturn($user);

        // 4. Configure Logger Expectation
        // We expect an error to be logged.
        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                $this->stringContains('Failed to upload file'),
                $this->callback(function($context) {
                    return isset($context['user_email']) && $context['user_email'] === 'test@example.com';
                })
            );

        // 5. Call resize
        // This should not throw an exception/error if handled correctly.
        $this->imageOptimizer->resize($filename);
    }
}
