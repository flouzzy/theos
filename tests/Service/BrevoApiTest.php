<?php

namespace App\Tests\Service;

use App\Entity\User;
use App\Service\BrevoApi;
use Brevo\Client\Api\ContactsApi;
use Exception;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class BrevoApiTest extends TestCase
{
    public function testAddOrUpdateContactExceptionPath(): void
    {
        $parameterBag = $this->createMock(ParameterBagInterface::class);
        $logger = $this->createMock(LoggerInterface::class);

        $parameterBag->method('get')
            ->willReturnMap([
                ['brevo_api_key', 'test_key'],
                ['kernel.environment', 'prod'],
                ['brevo_list_id', '1,2'],
            ]);

        $logger->expects($this->once())
            ->method('error')
            ->with('Exception when calling ContactsApi->createContact: Test exception message');

        $brevoApi = new BrevoApi($parameterBag, $logger);

        $mockContactsApi = $this->createMock(ContactsApi::class);
        $mockContactsApi->expects($this->once())
            ->method('createContact')
            ->willThrowException(new Exception('Test exception message'));

        $reflection = new \ReflectionClass($brevoApi);
        $property = $reflection->getProperty('apiContact');
        $property->setAccessible(true);
        $property->setValue($brevoApi, $mockContactsApi);

        $user = new User();
        $user->setEmail('test@example.com');
        $user->setFirstname('John');
        $user->setLastname('Doe');

        $brevoApi->addOrUpdateContact($user);
    }
}
