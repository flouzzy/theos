<?php

namespace App\Tests\Service;

use App\Service\JWT;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for JWT Service.
 * Covers generation, validation, expiration, and signature verification.
 */
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
        $secret = 'secret';

        $token = $this->jwt->generate($header, $payload, $secret);

        $this->assertMatchesRegularExpression('/^[a-zA-Z0-9\-\_\=]+\.[a-zA-Z0-9\-\_\=]+\.[a-zA-Z0-9\-\_\=]+$/', $token);
    }

    public function testIsValid(): void
    {
        $validToken = 'header.payload.signature';
        $this->assertTrue($this->jwt->isValid($validToken));

        $invalidToken = 'header.payload';
        $this->assertFalse($this->jwt->isValid($invalidToken));

        $invalidChars = 'header.payload.sign@ture';
        $this->assertFalse($this->jwt->isValid($invalidChars));
    }

    public function testGetHeaderAndPayload(): void
    {
        $header = ['typ' => 'JWT', 'alg' => 'HS256'];
        $payload = ['user_id' => 123];
        $secret = 'secret';

        $token = $this->jwt->generate($header, $payload, $secret);

        $retrievedHeader = $this->jwt->getHeader($token);
        $this->assertEquals($header, $retrievedHeader);

        $retrievedPayload = $this->jwt->getPayload($token);
        $this->assertEquals($payload['user_id'], $retrievedPayload['user_id']);
        $this->assertArrayHasKey('iat', $retrievedPayload);
        $this->assertArrayHasKey('exp', $retrievedPayload);
    }

    public function testIsExpired(): void
    {
        $header = ['typ' => 'JWT', 'alg' => 'HS256'];
        $payload = ['user_id' => 123];
        $secret = 'secret';

        // Token valid for 1 hour
        $token = $this->jwt->generate($header, $payload, $secret, 3600);
        $this->assertFalse($this->jwt->isExpired($token));

        // Token expired 1 hour ago
        // Manually set exp to past and validity 0 to prevent overwrite
        $expiredPayload = $payload;
        $expiredPayload['exp'] = time() - 3600;
        $expiredToken = $this->jwt->generate($header, $expiredPayload, $secret, 0);
        $this->assertTrue($this->jwt->isExpired($expiredToken));
    }

    public function testCheck(): void
    {
        $header = ['typ' => 'JWT', 'alg' => 'HS256'];
        $payload = ['user_id' => 123];
        $secret = 'secret';

        $token = $this->jwt->generate($header, $payload, $secret);

        $this->assertTrue($this->jwt->check($token, $secret));
        $this->assertFalse($this->jwt->check($token, 'wrong_secret'));
    }

    public function testUrlSafeBase64Bug(): void
    {
        // This payload produces '~~~' which becomes 'fn5+' in standard base64
        // The generate method replaces '+' with '-' -> 'fn5-'
        // The getPayload method decodes 'fn5-' which fails or corrupts data if not reversed
        $payload = ['a' => '~~~'];
        $header = ['typ' => 'JWT', 'alg' => 'HS256'];
        $secret = 'secret';

        $token = $this->jwt->generate($header, $payload, $secret);

        $retrievedPayload = $this->jwt->getPayload($token);

        $this->assertEquals($payload['a'], $retrievedPayload['a']);
    }
}
