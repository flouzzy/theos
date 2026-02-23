<?php

namespace App\Tests\Service;

use App\Service\JWT;
use PHPUnit\Framework\TestCase;

class JWTTest extends TestCase
{
    private JWT $jwt;

    protected function setUp(): void
    {
        $this->jwt = new JWT();
    }

    public function testGenerate(): void
    {
        $header = ['typ' => 'JWT', 'alg' => 'HS256'];
        $payload = ['user_id' => 123];
        $secret = 'test_secret';

        $token = $this->jwt->generate($header, $payload, $secret);

        $this->assertIsString($token);
        $parts = explode('.', $token);
        $this->assertCount(3, $parts);

        // Verify content
        $extractedHeader = $this->jwt->getHeader($token);
        $extractedPayload = $this->jwt->getPayload($token);

        $this->assertEquals($header, $extractedHeader);
        $this->assertEquals($payload['user_id'], $extractedPayload['user_id']);
        $this->assertArrayHasKey('iat', $extractedPayload);
        $this->assertArrayHasKey('exp', $extractedPayload);
    }

    public function testIsValid(): void
    {
        // Valid token structure
        $validToken = 'header.payload.signature';
        $this->assertTrue($this->jwt->isValid($validToken));

        // Invalid token structure
        $invalidToken1 = 'header.payload';
        $this->assertFalse($this->jwt->isValid($invalidToken1));

        $invalidToken2 = 'header.payload.signature.extra';
        $this->assertFalse($this->jwt->isValid($invalidToken2));

        // Invalid characters
        $invalidToken3 = 'header!.payload.signature';
        $this->assertFalse($this->jwt->isValid($invalidToken3));
    }

    public function testGetHeader(): void
    {
        $header = ['typ' => 'JWT', 'alg' => 'HS256'];
        $payload = ['user_id' => 123];
        $secret = 'test_secret';

        $token = $this->jwt->generate($header, $payload, $secret);

        $extractedHeader = $this->jwt->getHeader($token);
        $this->assertEquals($header, $extractedHeader);
    }

    public function testGetPayload(): void
    {
        $header = ['typ' => 'JWT', 'alg' => 'HS256'];
        $payload = ['user_id' => 123];
        $secret = 'test_secret';

        $token = $this->jwt->generate($header, $payload, $secret);

        $extractedPayload = $this->jwt->getPayload($token);
        $this->assertEquals($payload['user_id'], $extractedPayload['user_id']);
        $this->assertArrayHasKey('iat', $extractedPayload);
        $this->assertArrayHasKey('exp', $extractedPayload);
    }

    public function testIsExpired(): void
    {
        $header = ['typ' => 'JWT', 'alg' => 'HS256'];
        $payload = ['user_id' => 123];
        $secret = 'test_secret';

        // Valid token (long expiration)
        $tokenValid = $this->jwt->generate($header, $payload, $secret, 3600);
        $this->assertFalse($this->jwt->isExpired($tokenValid));

        // Expired token (negative validity)
        $tokenExpired = $this->jwt->generate($header, $payload, $secret, -3600);
        $this->assertTrue($this->jwt->isExpired($tokenExpired));
    }

    public function testCheck(): void
    {
        $header = ['typ' => 'JWT', 'alg' => 'HS256'];
        $payload = ['user_id' => 123];
        $secret = 'test_secret';

        $token = $this->jwt->generate($header, $payload, $secret);

        // Correct secret
        $this->assertTrue($this->jwt->check($token, $secret));

        // Incorrect secret
        $this->assertFalse($this->jwt->check($token, 'wrong_secret'));

        // Modified token
        $parts = explode('.', $token);

        // Generate another token to get a valid payload part but different content
        $token2 = $this->jwt->generate($header, ['user_id' => 999], $secret);
        $parts2 = explode('.', $token2);

        // Mix parts: header1 + payload2 + signature1
        // Since payload changed, signature1 is invalid for it.
        $tamperedToken = $parts[0] . '.' . $parts2[1] . '.' . $parts[2];

        $this->assertFalse($this->jwt->check($tamperedToken, $secret));
    }
}
