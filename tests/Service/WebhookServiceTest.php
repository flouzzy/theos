<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\WebhookService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class WebhookServiceTest extends TestCase
{
    /** @var HttpClientInterface&MockObject */
    private HttpClientInterface $httpClient;

    /** @var LoggerInterface&MockObject */
    private LoggerInterface $logger;

    private WebhookService $webhookService;

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(HttpClientInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->webhookService = new WebhookService($this->httpClient, $this->logger);
    }

    public function testSendDiscordNotificationSuccess(): void
    {
        $url = 'https://discord.com/api/webhooks/test';
        $message = 'Test discord message';

        $responseMock = $this->createMock(ResponseInterface::class);

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with('POST', $url, [
                'json' => [
                    'content' => $message,
                    'username' => 'Le Rocher Académie',
                ],
            ])
            ->willReturn($responseMock);

        $this->logger->expects($this->never())
            ->method('error');

        $this->webhookService->sendDiscordNotification($url, $message);
    }

    public function testSendDiscordNotificationException(): void
    {
        $url = 'https://discord.com/api/webhooks/test';
        $message = 'Test discord message';
        $errorMessage = 'Network error';

        $this->httpClient->expects($this->once())
            ->method('request')
            ->willThrowException(new \Exception($errorMessage));

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Discord Webhook Error: ' . $errorMessage);

        $this->webhookService->sendDiscordNotification($url, $message);
    }

    public function testSendSlackNotificationSuccess(): void
    {
        $url = 'https://hooks.slack.com/services/test';
        $message = 'Test slack message';

        $responseMock = $this->createMock(ResponseInterface::class);

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with('POST', $url, [
                'json' => [
                    'text' => $message,
                ],
            ])
            ->willReturn($responseMock);

        $this->logger->expects($this->never())
            ->method('error');

        $this->webhookService->sendSlackNotification($url, $message);
    }

    public function testSendSlackNotificationException(): void
    {
        $url = 'https://hooks.slack.com/services/test';
        $message = 'Test slack message';
        $errorMessage = 'Network error';

        $this->httpClient->expects($this->once())
            ->method('request')
            ->willThrowException(new \Exception($errorMessage));

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Slack Webhook Error: ' . $errorMessage);

        $this->webhookService->sendSlackNotification($url, $message);
    }
}
