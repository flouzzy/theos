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
    private LoggerInterface&MockObject $logger;
    private BrevoApi $brevoApi;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $settingRepository = $this->createMock(SettingRepository::class);

        $this->brevoApi = new BrevoApi(
            'fake_api_key',
            'Fake Name',
            'fake@example.com',
            'fake_subject',
            '1,2',
            'test',
            $this->logger,
            $settingRepository
        );
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
