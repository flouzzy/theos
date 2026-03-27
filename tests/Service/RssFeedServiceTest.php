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
    private HttpClientInterface&MockObject $httpClient;
    private RssFeedService $rssFeedService;

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(HttpClientInterface::class);
        $this->rssFeedService = new RssFeedService($this->httpClient);
    }

    public function testGetLatestPostsReturnsPosts(): void
    {
        $url = 'https://example.com/feed.xml';

        $xmlContent = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0">
    <channel>
        <item>
            <title>Post 1</title>
            <link>https://example.com/post-1</link>
            <pubDate>Mon, 01 Jan 2024 00:00:00 +0000</pubDate>
        </item>
        <item>
            <title>Post 2</title>
            <link>https://example.com/post-2</link>
            <pubDate>Tue, 02 Jan 2024 00:00:00 +0000</pubDate>
        </item>
        <item>
            <title>Post 3</title>
            <link>https://example.com/post-3</link>
            <pubDate>Wed, 03 Jan 2024 00:00:00 +0000</pubDate>
        </item>
        <item>
            <title>Post 4</title>
            <link>https://example.com/post-4</link>
            <pubDate>Thu, 04 Jan 2024 00:00:00 +0000</pubDate>
        </item>
    </channel>
</rss>
XML;

        $responseMock = $this->createMock(ResponseInterface::class);
        $responseMock->expects($this->once())
            ->method('getContent')
            ->willReturn($xmlContent);

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with('GET', $url)
            ->willReturn($responseMock);

        $posts = $this->rssFeedService->getLatestPosts($url, 2);

        $this->assertCount(2, $posts);

        $this->assertEquals('Post 1', $posts[0]['title']);
        $this->assertEquals('https://example.com/post-1', $posts[0]['link']);
        $this->assertEquals('Mon, 01 Jan 2024 00:00:00 +0000', $posts[0]['pubDate']);

        $this->assertEquals('Post 2', $posts[1]['title']);
        $this->assertEquals('https://example.com/post-2', $posts[1]['link']);
        $this->assertEquals('Tue, 02 Jan 2024 00:00:00 +0000', $posts[1]['pubDate']);
    }

    public function testGetLatestPostsHandlesFewerPostsThanLimit(): void
    {
        $url = 'https://example.com/feed.xml';

        $xmlContent = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0">
    <channel>
        <item>
            <title>Single Post</title>
            <link>https://example.com/single-post</link>
            <pubDate>Fri, 05 Jan 2024 00:00:00 +0000</pubDate>
        </item>
    </channel>
</rss>
XML;

        $responseMock = $this->createMock(ResponseInterface::class);
        $responseMock->expects($this->once())
            ->method('getContent')
            ->willReturn($xmlContent);

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with('GET', $url)
            ->willReturn($responseMock);

        $posts = $this->rssFeedService->getLatestPosts($url, 5);

        $this->assertCount(1, $posts);
        $this->assertEquals('Single Post', $posts[0]['title']);
    }

    public function testGetLatestPostsReturnsEmptyArrayOnException(): void
    {
        $url = 'https://example.com/feed.xml';

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with('GET', $url)
            ->willThrowException(new \Exception('Connection failed'));

        libxml_use_internal_errors(true);
        $posts = $this->rssFeedService->getLatestPosts($url);
        libxml_use_internal_errors(false);

        $this->assertIsArray($posts);
        $this->assertEmpty($posts);
    }

    public function testGetLatestPostsReturnsEmptyArrayOnInvalidXml(): void
    {
        $url = 'https://example.com/feed.xml';

        $xmlContent = "Not valid XML";

        $responseMock = $this->createMock(ResponseInterface::class);
        $responseMock->expects($this->once())
            ->method('getContent')
            ->willReturn($xmlContent);

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with('GET', $url)
            ->willReturn($responseMock);

        libxml_use_internal_errors(true);
        $posts = $this->rssFeedService->getLatestPosts($url);
        libxml_use_internal_errors(false);

        $this->assertIsArray($posts);
        $this->assertEmpty($posts);
    }
}
