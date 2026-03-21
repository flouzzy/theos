<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\RssFeedService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class RssFeedServiceTest extends TestCase
{
    /** @var HttpClientInterface&MockObject */
    private HttpClientInterface $httpClient;
    private RssFeedService $rssFeedService;

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(HttpClientInterface::class);
        $this->rssFeedService = new RssFeedService($this->httpClient);
    }

    public function testGetLatestPostsReturnsPosts(): void
    {
        $url = 'https://example.com/rss';

        $responseMock = $this->createMock(ResponseInterface::class);
        $responseMock->expects($this->once())
            ->method('getContent')
            ->willReturn('<?xml version="1.0" encoding="UTF-8"?><rss version="2.0"><channel><item><title>Test Post 1</title><link>https://example.com/1</link><pubDate>Wed, 01 Jan 2025 00:00:00 +0000</pubDate></item></channel></rss>');

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with('GET', $url)
            ->willReturn($responseMock);

        $posts = $this->rssFeedService->getLatestPosts($url);

        $this->assertCount(1, $posts);
        $this->assertEquals('Test Post 1', $posts[0]['title']);
        $this->assertEquals('https://example.com/1', $posts[0]['link']);
        $this->assertEquals('Wed, 01 Jan 2025 00:00:00 +0000', $posts[0]['pubDate']);
    }

    public function testGetLatestPostsReturnsEmptyArrayOnError(): void
    {
        $url = 'https://example.com/rss';

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with('GET', $url)
            ->willThrowException(new \Exception('Connection error'));

        $posts = $this->rssFeedService->getLatestPosts($url);

        $this->assertIsArray($posts);
        $this->assertEmpty($posts);
    }
}
