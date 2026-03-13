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
        $url = 'https://discord.com/api/webhooks/123/abc';
        $message = 'Test message';

        $response = $this->createMock(ResponseInterface::class);

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with('POST', $url, [
                'json' => [
                    'content' => $message,
                    'username' => 'Le Rocher Académie',
                ],
            ])
            ->willReturn($response);

        $this->logger->expects($this->never())
            ->method('error');

        $this->webhookService->sendDiscordNotification($url, $message);
    }

    public function testSendDiscordNotificationErrorHandling(): void
    {
        $url = 'https://discord.com/api/webhooks/123/abc';
        $message = 'Test message';
        $exceptionMessage = 'Network connection failed';

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with('POST', $url, [
                'json' => [
                    'content' => $message,
                    'username' => 'Le Rocher Académie',
                ],
            ])
            ->willThrowException(new \Exception($exceptionMessage));

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Discord Webhook Error: ' . $exceptionMessage);

        $this->webhookService->sendDiscordNotification($url, $message);
    }

    public function testSendSlackNotificationSuccess(): void
    {
        $url = 'https://hooks.slack.com/services/T00000000/B00000000/XXXXXXXXXXXXXXXXXXXXXXXX';
        $message = 'Test message';

        $response = $this->createMock(ResponseInterface::class);

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with('POST', $url, [
                'json' => [
                    'text' => $message,
                ],
            ])
            ->willReturn($response);

        $this->logger->expects($this->never())
            ->method('error');

        $this->webhookService->sendSlackNotification($url, $message);
    }

    public function testSendSlackNotificationErrorHandling(): void
    {
        $url = 'https://hooks.slack.com/services/T00000000/B00000000/XXXXXXXXXXXXXXXXXXXXXXXX';
        $message = 'Test message';
        $exceptionMessage = 'DNS resolution failed';

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with('POST', $url, [
                'json' => [
                    'text' => $message,
                ],
            ])
            ->willThrowException(new \Exception($exceptionMessage));

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Slack Webhook Error: ' . $exceptionMessage);

        $this->webhookService->sendSlackNotification($url, $message);
    }
}
