<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SmokeTest extends WebTestCase
{
    /**
     * @dataProvider providePublicUrls
     */
    public function testPublicPagesRequest(string $url): void
    {
        $client = static::createClient();
        $client->request('GET', $url);

        $this->assertResponseIsSuccessful();
    }

    public static function providePublicUrls(): \Generator
    {
        yield 'Home' => ['/'];
        yield 'Login' => ['/login'];
        yield 'Register' => ['/register'];
        yield 'Courses' => ['/courses/'];
        yield 'Offline' => ['/offline'];
        yield 'CGU' => ['/cgu'];
        yield 'Mentions Légales' => ['/mentions-legales'];
    }
}
