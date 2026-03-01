<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\User;
use App\Service\BrevoApi;
use Brevo\Client\Api\ContactsApi;
use Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class BrevoApiTest extends TestCase
{
    private ParameterBagInterface&MockObject $parameterBag;
    private LoggerInterface&MockObject $logger;
    private BrevoApi $brevoApi;

    protected function setUp(): void
    {
        $this->parameterBag = $this->createMock(ParameterBagInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->parameterBag->method('get')->willReturnMap([
            ['brevo_api_key', 'dummy_api_key'],
            ['kernel.environment', 'prod'],
            ['brevo_list_id', '1,2'],
        ]);

        $this->brevoApi = new BrevoApi($this->parameterBag, $this->logger);
    }

    public function testAddOrUpdateContactException(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setFirstname('John');
        $user->setLastname('Doe');

        $mockContactsApi = $this->createMock(ContactsApi::class);
        $exceptionMessage = 'API rate limit exceeded';
        $mockContactsApi->expects($this->once())
            ->method('createContact')
            ->willThrowException(new Exception($exceptionMessage));

        // Use reflection to set the private mockContactsApi property in BrevoApi
        $reflection = new \ReflectionClass($this->brevoApi);
        $property = $reflection->getProperty('apiContact');
        $property->setAccessible(true);
        $property->setValue($this->brevoApi, $mockContactsApi);

        // Expect the logger to log the exact error message
        $this->logger->expects($this->once())
            ->method('error')
            ->with('Exception when calling ContactsApi->createContact: ' . $exceptionMessage);

        $this->brevoApi->addOrUpdateContact($user);
    }
}
