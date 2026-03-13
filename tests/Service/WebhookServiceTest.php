<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\WebhookService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class WebhookServiceTest extends TestCase
{
    private HttpClientInterface $httpClient;
    private LoggerInterface $logger;
    private WebhookService $webhookService;

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(HttpClientInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->webhookService = new WebhookService(
            $this->httpClient,
            $this->logger
        );
    }

    public function testSendDiscordNotification(): void
    {
        $url = 'https://discord.com/api/webhooks/test';
        $message = 'Test Discord Message';

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with('POST', $url, [
                'json' => [
                    'content' => $message,
                    'username' => 'Le Rocher Académie',
                ],
            ])
            ->willReturn($this->createMock(ResponseInterface::class));

        $this->logger->expects($this->never())
            ->method('error');

        $this->webhookService->sendDiscordNotification($url, $message);
    }

    public function testSendDiscordNotificationError(): void
    {
        $url = 'https://discord.com/api/webhooks/test';
        $message = 'Test Discord Message';
        $exceptionMessage = 'Connection timeout';

        $this->httpClient->expects($this->once())
            ->method('request')
            ->willThrowException(new \Exception($exceptionMessage));

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Discord Webhook Error: ' . $exceptionMessage);

        $this->webhookService->sendDiscordNotification($url, $message);
    }

    public function testSendSlackNotification(): void
    {
        $url = 'https://hooks.slack.com/services/test';
        $message = 'Test Slack Message';

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with('POST', $url, [
                'json' => [
                    'text' => $message,
                ],
            ])
            ->willReturn($this->createMock(ResponseInterface::class));

        $this->logger->expects($this->never())
            ->method('error');

        $this->webhookService->sendSlackNotification($url, $message);
    }

    public function testSendSlackNotificationError(): void
    {
        $url = 'https://hooks.slack.com/services/test';
        $message = 'Test Slack Message';
        $exceptionMessage = 'Invalid payload';

        $this->httpClient->expects($this->once())
            ->method('request')
            ->willThrowException(new \Exception($exceptionMessage));

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Slack Webhook Error: ' . $exceptionMessage);

        $this->webhookService->sendSlackNotification($url, $message);
    }
}
