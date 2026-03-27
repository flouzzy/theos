<?php

namespace App\Tests\Service;

use App\Entity\Lesson;
use App\Entity\Module;
use App\Entity\User;
use App\Repository\SiteSettingRepository;
use App\Service\CoachAIAgent;
use GeminiAPI\Client;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Cache\CacheInterface;

class CoachAIAgentTest extends TestCase
{
    private SiteSettingRepository&MockObject $siteSettingRepo;
    private CacheInterface&MockObject $cache;
    private CoachAIAgent $agent;

    protected function setUp(): void
    {
        $this->siteSettingRepo = $this->createMock(SiteSettingRepository::class);
        $this->cache = $this->createMock(CacheInterface::class);

        $this->agent = new CoachAIAgent(
            'fake_api_key',
            'gemini-1.5-flash',
            $this->siteSettingRepo,
            $this->cache
        );
    }

    public function testGenerateResponseCatchesExceptionAndReturnsFallbackMessage(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn(1);

        $this->cache->method('get')->willReturn([]);

        $this->siteSettingRepo->method('findOneBy')->willReturn(null);

        // Replace the client using reflection
        $clientMock = $this->createMock(Client::class);
        $clientMock->method('withV1BetaVersion')->willThrowException(new \Exception('API connection failed'));

        $reflection = new \ReflectionClass(CoachAIAgent::class);
        $property = $reflection->getProperty('client');
        $property->setAccessible(true);
        $property->setValue($this->agent, $clientMock);

        $response = $this->agent->generateResponse($user, 'Hello');

        $this->assertStringContainsString("Désolé, une erreur est survenue lors de ma réflexion.", $response);
        $this->assertStringContainsString("API connection failed", $response);
    }

    public function testGenerateNextStepNudgeCatchesExceptionAndReturnsFallbackMessage(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getFullname')->willReturn('John Doe');

        $module = $this->createMock(Module::class);
        $module->method('getTitle')->willReturn('Module 1');

        $lesson = $this->createMock(Lesson::class);
        $lesson->method('getTitle')->willReturn('Lesson 1');
        $lesson->method('getModule')->willReturn($module);

        $clientMock = $this->createMock(Client::class);
        $clientMock->method('generativeModel')->willThrowException(new \Exception('API connection failed'));

        $reflection = new \ReflectionClass(CoachAIAgent::class);
        $property = $reflection->getProperty('client');
        $property->setAccessible(true);
        $property->setValue($this->agent, $clientMock);

        $response = $this->agent->generateNextStepNudge($user, $lesson);

        $this->assertEquals("C'est le moment idéal pour découvrir votre prochaine leçon : Lesson 1 !", $response);
    }
}
