<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\WebhookService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class WebhookServiceTest extends TestCase
{
    private HttpClientInterface&MockObject $httpClient;
    private LoggerInterface&MockObject $logger;
    private WebhookService $webhookService;

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(HttpClientInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->webhookService = new WebhookService($this->httpClient, $this->logger);
    }

    public function testSendDiscordNotificationSuccess(): void
    {
        $url = 'https://discord.com/api/webhooks/123/abc';
        $message = 'Test message';

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with('POST', $url, [
                'json' => [
                    'content' => $message,
                    'username' => 'Le Rocher Académie',
                ],
            ]);

        $this->logger->expects($this->never())->method('error');

        $this->webhookService->sendDiscordNotification($url, $message);
    }

    public function testSendDiscordNotificationError(): void
    {
        $url = 'https://discord.com/api/webhooks/123/abc';
        $message = 'Test message';

        $this->httpClient->expects($this->once())
            ->method('request')
            ->willThrowException(new \Exception('Connection failed'));

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Discord Webhook Error: Connection failed');

        $this->webhookService->sendDiscordNotification($url, $message);
    }

    public function testSendSlackNotificationSuccess(): void
    {
        $url = 'https://hooks.slack.com/services/T00000000/B00000000/XXXXXXXXXXXXXXXXXXXXXXXX';
        $message = 'Test message';

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with('POST', $url, [
                'json' => [
                    'text' => $message,
                ],
            ]);

        $this->logger->expects($this->never())->method('error');

        $this->webhookService->sendSlackNotification($url, $message);
    }

    public function testSendSlackNotificationError(): void
    {
        $url = 'https://hooks.slack.com/services/T00000000/B00000000/XXXXXXXXXXXXXXXXXXXXXXXX';
        $message = 'Test message';

        $this->httpClient->expects($this->once())
            ->method('request')
            ->willThrowException(new \Exception('Connection failed'));

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Slack Webhook Error: Connection failed');

        $this->webhookService->sendSlackNotification($url, $message);
    }
}
