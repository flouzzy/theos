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
use Brevo\Client\Api\ContactsApi;
use Brevo\Client\Model\CreateContact;
use Brevo\Client\Model\RemoveContactFromList;
use App\Entity\User;
use App\Entity\Setting;

class BrevoApiTest extends TestCase
{
    private LoggerInterface&MockObject $logger;
    private SettingRepository&MockObject $settingRepository;
    private BrevoApi $brevoApi;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->settingRepository = $this->createMock(SettingRepository::class);

        $this->brevoApi = new BrevoApi(
            'fake_api_key',
            'Fake Name',
            'fake@example.com',
            'fake_subject',
            '1,2',
            'prod', // Use prod to bypass environment check
            $this->logger,
            $this->settingRepository
        );
    }

    public function testAddOrUpdateContactCallsApi(): void
    {
        $mockApiContact = $this->createMock(ContactsApi::class);
        $user = $this->createMock(User::class);
        $user->method('getEmail')->willReturn('test@example.com');
        $user->method('getId')->willReturn(123);
        $user->method('getFirstname')->willReturn('John');
        $user->method('getLastname')->willReturn('Doe');

        $mockApiContact->expects($this->once())
            ->method('createContact')
            ->with($this->isInstanceOf(CreateContact::class));

        $this->setPrivateProperty($this->brevoApi, 'apiContact', $mockApiContact);

        $this->brevoApi->addOrUpdateContact($user);
    }

    public function testAddContactToOnboardedList(): void
    {
        $setting = new Setting();
        $setting->setBrevoListOnboarded('456');
        $this->settingRepository->method('getSettings')->willReturn($setting);

        $mockApiContact = $this->createMock(ContactsApi::class);
        $user = $this->createMock(User::class);
        $user->method('getEmail')->willReturn('test@example.com');

        $mockApiContact->expects($this->once())
            ->method('createContact')
            ->with($this->callback(function (CreateContact $contact) {
                return $contact->getListIds() === [456];
            }));

        $this->setPrivateProperty($this->brevoApi, 'apiContact', $mockApiContact);

        $this->brevoApi->addContactToOnboardedList($user);
    }

    public function testMoveToAlumniListRemovesFromOnboardedAndAddsToAlumni(): void
    {
        $setting = new Setting();
        $setting->setBrevoListOnboarded('456');
        $setting->setBrevoListAlumni('789');
        $this->settingRepository->method('getSettings')->willReturn($setting);

        $mockApiContact = $this->createMock(ContactsApi::class);
        $user = $this->createMock(User::class);
        $user->method('getEmail')->willReturn('test@example.com');

        $mockApiContact->expects($this->once())
            ->method('removeContactFromList')
            ->with(456, $this->isInstanceOf(RemoveContactFromList::class));

        $mockApiContact->expects($this->once())
            ->method('createContact')
            ->with($this->callback(function (CreateContact $contact) {
                return $contact->getListIds() === [789];
            }));

        $this->setPrivateProperty($this->brevoApi, 'apiContact', $mockApiContact);

        $this->brevoApi->moveToAlumniList($user);
    }

    public function testSendEmailExceptionIsCaughtAndLogged(): void
    {
        $mockApiEmail = $this->createMock(TransactionalEmailsApi::class);

        $exceptionMessage = 'API Timeout';
        $mockApiEmail->expects($this->once())
            ->method('sendTransacEmail')
            ->with($this->isInstanceOf(SendSmtpEmail::class))
            ->willThrowException(new Exception($exceptionMessage));

        $this->setPrivateProperty($this->brevoApi, 'apiEmail', $mockApiEmail);

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Exception when calling TransactionalEmailsApi->sendTransacEmail: ' . $exceptionMessage);

        $tos = [['email' => 'test@example.com', 'name' => 'Test User']];
        $params = ['content' => 'Test content'];

        $this->brevoApi->sendEmail($tos, $params);
    }

    private function setPrivateProperty(object $object, string $property, mixed $value): void
    {
        $reflection = new \ReflectionClass($object);
        $prop = $reflection->getProperty($property);
        $prop->setAccessible(true);
        $prop->setValue($object, $value);
    }
}
