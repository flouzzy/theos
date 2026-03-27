<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\User;
use App\Service\InfographicService;
use PHPUnit\Framework\TestCase;

class InfographicServiceTest extends TestCase
{
    public function testGenerateBase64ReturnsValidImageString(): void
    {
        // Arrange
        $user = new User();
        $user->setFullname('Jane Doe');
        $user->setXp(1200);

        $service = new InfographicService();

        // Act
        $base64String = $service->generateBase64($user);

        // Assert
        $this->assertIsString($base64String);
        $this->assertNotEmpty($base64String);

        // Verify it's a valid base64 string
        $decoded = base64_decode($base64String, true);
        $this->assertNotFalse($decoded, 'The returned string is not valid base64.');

        // Verify the decoded string starts with the PNG signature
        $pngSignature = "\x89PNG\r\n\x1a\n";
        $this->assertStringStartsWith($pngSignature, $decoded, 'The decoded string is not a valid PNG image.');

        // Use getimagesizefromstring to verify image dimensions
        $imageInfo = getimagesizefromstring($decoded);
        $this->assertIsArray($imageInfo);
        $this->assertEquals(800, $imageInfo[0], 'Width should be 800');
        $this->assertEquals(400, $imageInfo[1], 'Height should be 400');
        $this->assertEquals(IMAGETYPE_PNG, $imageInfo[2], 'Image type should be PNG');
    }
}
