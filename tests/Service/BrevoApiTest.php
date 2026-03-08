<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\BrevoApi;
use Brevo\Client\Api\TransactionalEmailsApi;
use Brevo\Client\Model\SendSmtpEmail;
use Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use App\Repository\SettingRepository;

class BrevoApiTest extends TestCase
{
    private ParameterBagInterface&MockObject $parameterBag;
    private LoggerInterface&MockObject $logger;
    private BrevoApi $brevoApi;

    protected function setUp(): void
    {
        $this->parameterBag = $this->createMock(ParameterBagInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->parameterBag->method('get')
            ->willReturnCallback(function (string $key) {
                return match ($key) {
                    'brevo_api_key' => 'fake_api_key',
                    'brevo_subject' => 'fake_subject',
                    'brevo_from_name' => 'Fake Name',
                    'brevo_from_email' => 'fake@example.com',
                    default => null,
                };
            });

        $settingRepository = $this->createMock(SettingRepository::class);

        $this->brevoApi = new BrevoApi($this->parameterBag, $this->logger, $settingRepository);
    }

    public function testSendEmailExceptionIsCaughtAndLogged(): void
    {
        $mockApiEmail = $this->createMock(TransactionalEmailsApi::class);

        $exceptionMessage = 'API Timeout';
        $mockApiEmail->expects($this->once())
            ->method('sendTransacEmail')
            ->with($this->isInstanceOf(SendSmtpEmail::class))
            ->willThrowException(new Exception($exceptionMessage));

        $reflection = new \ReflectionClass(BrevoApi::class);
        $property = $reflection->getProperty('apiEmail');
        $property->setAccessible(true);
        $property->setValue($this->brevoApi, $mockApiEmail);

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Exception when calling TransactionalEmailsApi->sendTransacEmail: ' . $exceptionMessage);

        $tos = [['email' => 'test@example.com', 'name' => 'Test User']];
        $params = ['content' => 'Test content'];

        $this->brevoApi->sendEmail($tos, $params);
    }
}
