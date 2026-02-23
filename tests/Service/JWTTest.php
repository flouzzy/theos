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

    public function testGenerateAndCheck(): void
    {
        $header = ['typ' => 'JWT', 'alg' => 'HS256'];
        $payload = ['user_id' => 123];
        $secret = 'test_secret';

        // validity > 0 will add iat and exp
        $token = $this->jwt->generate($header, $payload, $secret, 10800);

        $this->assertTrue($this->jwt->isValid($token));
        $this->assertTrue($this->jwt->check($token, $secret));
    }

    public function testCheckFailsWithWrongSecret(): void
    {
        $header = ['typ' => 'JWT', 'alg' => 'HS256'];
        $payload = ['user_id' => 123];
        $secret = 'test_secret';

        $token = $this->jwt->generate($header, $payload, $secret);

        $this->assertFalse($this->jwt->check($token, 'wrong_secret'));
    }

    public function testCheckFailsWithModifiedToken(): void
    {
        $header = ['typ' => 'JWT', 'alg' => 'HS256'];
        $payload = ['user_id' => 123];
        $secret = 'test_secret';

        $token = $this->jwt->generate($header, $payload, $secret);

        // Modify the payload part of the token
        $parts = explode('.', $token);
        $payloadDecoded = json_decode(base64_decode($parts[1]), true);
        $payloadDecoded['user_id'] = 456;
        $parts[1] = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(json_encode($payloadDecoded)));
        $modifiedToken = implode('.', $parts);

        $this->assertFalse($this->jwt->check($modifiedToken, $secret));
    }

    public function testIsExpired(): void
    {
        $header = ['typ' => 'JWT', 'alg' => 'HS256'];
        $payload = ['user_id' => 123];
        $secret = 'test_secret';

        // Token valid for 10 seconds
        $token = $this->jwt->generate($header, $payload, $secret, 10);
        $this->assertFalse($this->jwt->isExpired($token));

        // Create an already expired token
        $payload['exp'] = time() - 10;
        $tokenExpired = $this->jwt->generate($header, $payload, $secret, 0);
        $this->assertTrue($this->jwt->isExpired($tokenExpired));
    }
}
