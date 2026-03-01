<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\BrevoApi;
use Brevo\Client\Api\TransactionalEmailsApi;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class BrevoApiTest extends TestCase
{
    public function testSendEmailThrowsExceptionLogsError(): void
    {
        $parameterBag = $this->createMock(ParameterBagInterface::class);
        $logger = $this->createMock(LoggerInterface::class);

        $parameterBag->method('get')
            ->willReturnMap([
                ['brevo_api_key', 'test_api_key'],
                ['brevo_subject', 'Default Subject'],
                ['brevo_from_name', 'Test Sender'],
                ['brevo_from_email', 'sender@example.com'],
            ]);

        $brevoApi = new BrevoApi($parameterBag, $logger);

        $exceptionMessage = 'Test exception from TransactionalEmailsApi';

        $mockApiEmail = $this->createMock(TransactionalEmailsApi::class);
        $mockApiEmail->expects($this->once())
            ->method('sendTransacEmail')
            ->willThrowException(new \Exception($exceptionMessage));

        $reflection = new \ReflectionClass(BrevoApi::class);
        $apiEmailProperty = $reflection->getProperty('apiEmail');
        $apiEmailProperty->setAccessible(true);
        $apiEmailProperty->setValue($brevoApi, $mockApiEmail);

        $logger->expects($this->once())
            ->method('error')
            ->with('Exception when calling TransactionalEmailsApi->sendTransacEmail: ' . $exceptionMessage);

        $brevoApi->sendEmail([['email' => 'recipient@example.com']], ['content' => 'Test Content']);
    }
}
