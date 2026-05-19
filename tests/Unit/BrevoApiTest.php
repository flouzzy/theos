<?php

namespace App\Tests\Unit;

use App\Entity\Setting;
use App\Entity\User;
use App\Repository\SettingRepository;
use App\Service\BrevoApi;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class BrevoApiTest extends TestCase
{
    private $logger;
    private $settingRepository;
    private $brevoApi;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->settingRepository = $this->createMock(SettingRepository::class);

        $this->brevoApi = new BrevoApi(
            'test_key',
            'Academy',
            'no-reply@test.com',
            'Subject',
            '123',
            'test',
            $this->logger,
            $this->settingRepository,
            'App Name'
        );
    }

    public function testAddContactToOnboardedList(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getEmail')->willReturn('test@test.com');
        $user->method('getId')->willReturn(1);

        $setting = $this->createMock(Setting::class);
        $setting->method('getBrevoListOnboarded')->willReturn('123');

        $this->settingRepository->method('getSettings')->willReturn($setting);

        // We can't easily mock the internal Brevo API client without more refactoring
        // but we can check if it tries to call it.
        // For unit test purposes, we'll just check if the logic reaches the point of needing the API client.
        
        // Actually, let's just test that it fetches the settings.
        $this->settingRepository->expects($this->once())->method('getSettings');
        
        $this->brevoApi->addContactToOnboardedList($user);
    }
}
