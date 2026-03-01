<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\User;
use App\Service\BrevoApi;
use Brevo\Client\Api\ContactsApi;
use Brevo\Client\Api\TransactionalEmailsApi;
use Exception;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class BrevoApiTest extends TestCase
{
    public function testSendEmailThrowsExceptionLogsError(): void
    {
        $parameterBag = $this->createMock(ParameterBagInterface::class);
        $parameterBag->method('get')->willReturnCallback(function (string $key) {
            return match ($key) {
                'brevo_api_key' => 'test_api_key',
                'brevo_subject' => 'Test Subject',
                'brevo_from_name' => 'Test Sender',
                'brevo_from_email' => 'sender@example.com',
                default => null,
            };
        });

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('error')
            ->with($this->stringContains('Exception when calling TransactionalEmailsApi->sendTransacEmail: Test Exception'));

        $brevoApi = new BrevoApi($parameterBag, $logger);

        // Reflection to inject mock TransactionalEmailsApi
        $apiEmailMock = $this->createMock(TransactionalEmailsApi::class);
        $apiEmailMock->method('sendTransacEmail')
            ->willThrowException(new Exception('Test Exception'));

        $reflection = new \ReflectionClass(BrevoApi::class);
        $property = $reflection->getProperty('apiEmail');
        $property->setAccessible(true);
        $property->setValue($brevoApi, $apiEmailMock);

        $tos = [['email' => 'recipient@example.com']];
        $params = ['content' => 'Test content'];

        $brevoApi->sendEmail($tos, $params);
    }

    public function testAddOrUpdateContactThrowsExceptionLogsError(): void
    {
        $parameterBag = $this->createMock(ParameterBagInterface::class);
        $parameterBag->method('get')->willReturnCallback(function (string $key) {
            return match ($key) {
                'kernel.environment' => 'prod',
                'brevo_api_key' => 'test_api_key',
                'brevo_list_id' => '1,2,3',
                default => null,
            };
        });

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('error')
            ->with($this->stringContains('Exception when calling ContactsApi->createContact: Test Exception'));

        $brevoApi = new BrevoApi($parameterBag, $logger);

        // Reflection to inject mock ContactsApi
        $apiContactMock = $this->createMock(ContactsApi::class);
        $apiContactMock->method('createContact')
            ->willThrowException(new Exception('Test Exception'));

        $reflection = new \ReflectionClass(BrevoApi::class);
        $property = $reflection->getProperty('apiContact');
        $property->setAccessible(true);
        $property->setValue($brevoApi, $apiContactMock);

        $user = new User();
        $user->setEmail('test@example.com');
        $user->setFirstname('John');
        $user->setLastname('Doe');

        $brevoApi->addOrUpdateContact($user);
    }
}
