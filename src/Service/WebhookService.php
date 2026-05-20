<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

class WebhookService
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger,
        private string $appName
    ) {}

    public function sendDiscordNotification(string $url, string $message): void
    {
        try {
            $this->httpClient->request('POST', $url, [
                'json' => [
                    'content' => $message,
                    'username' => $this->appName,
                ],
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Discord Webhook Error: ' . $e->getMessage());
        }
    }

    public function sendSlackNotification(string $url, string $message): void
    {
        try {
            $this->httpClient->request('POST', $url, [
                'json' => [
                    'text' => $message,
                ],
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Slack Webhook Error: ' . $e->getMessage());
        }
    }
}
