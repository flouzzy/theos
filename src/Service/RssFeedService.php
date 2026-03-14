<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class RssFeedService
{
    public function __construct(
        private HttpClientInterface $httpClient
    ) {}

    public function getLatestPosts(string $url, int $limit = 3): array
    {
        try {
            $response = $this->httpClient->request('GET', $url);
            $xml = new \SimpleXMLElement($response->getContent());
            $posts = [];
            
            foreach ($xml->channel->item as $item) {
                if (count($posts) >= $limit) break;
                $posts[] = [
                    'title' => (string)$item->title,
                    'link' => (string)$item->link,
                    'pubDate' => (string)$item->pubDate,
                ];
            }
            return $posts;
        } catch (\Exception $e) {
            return [];
        }
    }
}
