<?php

namespace App\Tests\Service;

use App\Entity\User;
use App\Service\BrevoApi;
use Brevo\Client\Api\ContactsApi;
use Brevo\Client\Api\TransactionalEmailsApi;
use Brevo\Client\Model\CreateContact;
use Brevo\Client\Model\SendSmtpEmail;
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

        $this->parameterBag->method('get')
            ->willReturnMap([
                ['brevo_api_key', 'test_api_key'],
                ['kernel.environment', 'prod'],
                ['brevo_list_id', '1,2'],
                ['brevo_subject', 'Default Subject'],
                ['brevo_from_name', 'Test Name'],
                ['brevo_from_email', 'test@example.com']
            ]);

        $this->brevoApi = new BrevoApi($this->parameterBag, $this->logger);
    }

    public function testAddOrUpdateContactException(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setFirstname('John');
        $user->setLastname('Doe');

        $contactsApi = $this->createMock(ContactsApi::class);
        $contactsApi->expects($this->once())
            ->method('createContact')
            ->willThrowException(new Exception('API Error Test'));

        $reflection = new \ReflectionClass($this->brevoApi);
        $property = $reflection->getProperty('apiContact');
        $property->setAccessible(true);
        $property->setValue($this->brevoApi, $contactsApi);

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Exception when calling ContactsApi->createContact: API Error Test');

        $this->brevoApi->addOrUpdateContact($user);
    }

    public function testAddOrUpdateContactSkipsInDevOrTest(): void
    {
        // Setup new brevo api with dev env
        $parameterBag = $this->createMock(ParameterBagInterface::class);
        $logger = $this->createMock(LoggerInterface::class);

        $parameterBag->method('get')
            ->willReturnMap([
                ['brevo_api_key', 'test_api_key'],
                ['kernel.environment', 'dev'],
            ]);

        $brevoApi = new BrevoApi($parameterBag, $logger);

        $user = new User();
        $user->setEmail('dev@example.com');

        $logger->expects($this->once())
            ->method('info')
            ->with('Skipping Brevo contact addition in dev environment');

        $contactsApi = $this->createMock(ContactsApi::class);
        $contactsApi->expects($this->never())
            ->method('createContact');

        $reflection = new \ReflectionClass($brevoApi);
        $property = $reflection->getProperty('apiContact');
        $property->setAccessible(true);
        $property->setValue($brevoApi, $contactsApi);

        $brevoApi->addOrUpdateContact($user);
    }

    public function testAddOrUpdateContactReturnsIfNoEmail(): void
    {
        $user = new User();
        // email is null

        $contactsApi = $this->createMock(ContactsApi::class);
        $contactsApi->expects($this->never())
            ->method('createContact');

        $reflection = new \ReflectionClass($this->brevoApi);
        $property = $reflection->getProperty('apiContact');
        $property->setAccessible(true);
        $property->setValue($this->brevoApi, $contactsApi);

        $this->brevoApi->addOrUpdateContact($user);
    }

    public function testAddOrUpdateContactSuccess(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setFirstname('John');
        $user->setLastname('Doe');

        $contactsApi = $this->createMock(ContactsApi::class);
        $contactsApi->expects($this->once())
            ->method('createContact')
            ->with($this->callback(function (CreateContact $createContact) {
                return $createContact->getEmail() === 'test@example.com' &&
                       $createContact->getUpdateEnabled() === true &&
                       $createContact->getListIds() === [1, 2] &&
                       $createContact->getAttributes()->PRENOM === 'John' &&
                       $createContact->getAttributes()->NOM === 'Doe';
            }));

        $reflection = new \ReflectionClass($this->brevoApi);
        $property = $reflection->getProperty('apiContact');
        $property->setAccessible(true);
        $property->setValue($this->brevoApi, $contactsApi);

        $this->logger->expects($this->never())
            ->method('error');

        $this->brevoApi->addOrUpdateContact($user);
    }

    public function testSendEmailException(): void
    {
        $tos = [['email' => 'recipient@example.com', 'name' => 'Recipient Name']];
        $params = ['content' => 'Test Content', 'subject' => 'Custom Subject'];

        $emailApi = $this->createMock(TransactionalEmailsApi::class);
        $emailApi->expects($this->once())
            ->method('sendTransacEmail')
            ->willThrowException(new Exception('Email API Error Test'));

        $reflection = new \ReflectionClass($this->brevoApi);
        $property = $reflection->getProperty('apiEmail');
        $property->setAccessible(true);
        $property->setValue($this->brevoApi, $emailApi);

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Exception when calling TransactionalEmailsApi->sendTransacEmail: Email API Error Test');

        $this->brevoApi->sendEmail($tos, $params);
    }

    public function testSendEmailSkipsIfApiKeyIsNull(): void
    {
        $parameterBag = $this->createMock(ParameterBagInterface::class);
        $logger = $this->createMock(LoggerInterface::class);

        $parameterBag->method('get')
            ->willReturnMap([
                ['brevo_api_key', 'null'], // Exact string 'null' checks
            ]);

        $brevoApi = new BrevoApi($parameterBag, $logger);

        $emailApi = $this->createMock(TransactionalEmailsApi::class);
        $emailApi->expects($this->never())
            ->method('sendTransacEmail');

        $reflection = new \ReflectionClass($brevoApi);
        $property = $reflection->getProperty('apiEmail');
        $property->setAccessible(true);
        $property->setValue($brevoApi, $emailApi);

        $brevoApi->sendEmail([['email' => 'test@example.com']], []);
    }

    public function testSendEmailSuccess(): void
    {
        $tos = [['email' => 'recipient@example.com', 'name' => 'Recipient Name']];
        $params = ['content' => 'Test Content']; // No subject, defaults to 'brevo_subject'

        $emailApi = $this->createMock(TransactionalEmailsApi::class);
        $emailApi->expects($this->once())
            ->method('sendTransacEmail')
            ->with($this->callback(function (SendSmtpEmail $sendSmtpEmail) {
                return $sendSmtpEmail->getSubject() === 'Default Subject' &&
                       $sendSmtpEmail->getParams() === ['content' => 'Test Content'] &&
                       $sendSmtpEmail->getTo() === [['email' => 'recipient@example.com', 'name' => 'Recipient Name']] &&
                       $sendSmtpEmail->getSender()['name'] === 'Test Name' &&
                       $sendSmtpEmail->getSender()['email'] === 'test@example.com';
            }));

        $reflection = new \ReflectionClass($this->brevoApi);
        $property = $reflection->getProperty('apiEmail');
        $property->setAccessible(true);
        $property->setValue($this->brevoApi, $emailApi);

        $this->logger->expects($this->never())
            ->method('error');

        $this->brevoApi->sendEmail($tos, $params);
    }
}