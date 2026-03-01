<?php

declare(strict_types=1);

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

        $parameterBag->method('get')->willReturnCallback(function (string $name) {
            if ($name === 'brevo_api_key') {
                return 'test_api_key';
            }
            if ($name === 'kernel.environment') {
                return 'prod'; // To avoid early return
            }
            if ($name === 'brevo_list_id') {
                return '1,2';
            }
            return null;
        });

        $user = new User();
        $user->setEmail('test@example.com');
        $user->setFirstname('Test');
        $user->setLastname('User');

        $brevoApi = new BrevoApi($parameterBag, $logger);

        // Mock ContactsApi
        $contactsApi = $this->createMock(ContactsApi::class);
        $contactsApi->expects($this->once())
            ->method('createContact')
            ->willThrowException(new Exception('Brevo API is down'));

        // Inject mock via reflection
        $reflection = new \ReflectionClass($brevoApi);
        $property = $reflection->getProperty('apiContact');
        $property->setAccessible(true);
        $property->setValue($brevoApi, $contactsApi);

        // Assert logger is called
        $logger->expects($this->once())
            ->method('error')
            ->with('Exception when calling ContactsApi->createContact: Brevo API is down');

        $brevoApi->addOrUpdateContact($user);
    }
}
