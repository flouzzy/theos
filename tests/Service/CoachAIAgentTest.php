<?php

namespace App\Tests\Service;

use App\Entity\Lesson;
use App\Entity\Module;
use App\Entity\SiteSetting;
use App\Entity\User;
use App\Repository\SiteSettingRepository;
use App\Service\CoachAIAgent;
use GeminiAPI\ChatSession;
use GeminiAPI\Client;
use GeminiAPI\GenerativeModel;
use GeminiAPI\Responses\GenerateContentResponse;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Contracts\Cache\CacheInterface;

interface AppCacheInterface extends CacheInterface, CacheItemPoolInterface
{
}

class CoachAIAgentTest extends TestCase
{
    private SiteSettingRepository&MockObject $siteSettingRepo;
    private AppCacheInterface&MockObject $cache;
    private CoachAIAgent $agent;

    protected function setUp(): void
    {
        $this->siteSettingRepo = $this->createMock(SiteSettingRepository::class);
        $this->cache = $this->createMock(AppCacheInterface::class);

        $this->agent = new CoachAIAgent(
            'fake_api_key',
            'gemini-1.5-flash',
            $this->siteSettingRepo,
            $this->cache
        );
    }

    public function testGetHistoryReturnsCachedHistory(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn(1);

        $expectedHistory = [['role' => 'user', 'content' => 'Hello']];
        $this->cache->method('get')->willReturn($expectedHistory);

        $history = $this->agent->getHistory($user);
        $this->assertEquals($expectedHistory, $history);
    }

    public function testSaveHistoryUpdatesCacheItem(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn(1);

        $cacheItem = $this->createMock(CacheItemInterface::class);
        $cacheItem->expects($this->once())->method('set')->with([['role' => 'user', 'content' => 'Hi']]);
        $cacheItem->expects($this->once())->method('expiresAfter');

        $this->cache->method('getItem')->willReturn($cacheItem);
        $this->cache->expects($this->once())->method('save')->with($cacheItem);

        $this->agent->saveHistory($user, [['role' => 'user', 'content' => 'Hi']]);
    }

    public function testGenerateResponseSuccess(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn(1);

        // Mock getting history
        $this->cache->method('get')->willReturn([
            ['role' => 'user', 'content' => 'Hi'],
            ['role' => 'model', 'content' => 'Hello there!']
        ]);

        // Mock saving history
        $cacheItem = $this->createMock(CacheItemInterface::class);
        $cacheItem->expects($this->once())->method('set'); // Will be updated history
        $cacheItem->expects($this->once())->method('expiresAfter');
        $this->cache->method('getItem')->willReturn($cacheItem);
        $this->cache->expects($this->once())->method('save')->with($cacheItem);

        // Mock site setting prompt
        $siteSetting = $this->createMock(SiteSetting::class);
        $siteSetting->method('getValue')->willReturn('Custom system prompt');
        $this->siteSettingRepo->method('findOneBy')->willReturn($siteSetting);

        // Mock Gemini API components
        $clientMock = $this->createMock(Client::class);
        $modelMock = $this->createMock(GenerativeModel::class);
        $chatSessionMock = $this->createMock(ChatSession::class);
        $responseMock = $this->createMock(GenerateContentResponse::class);

        $responseMock->method('text')->willReturn('Mocked AI Response');

        $clientMock->method('withV1BetaVersion')->willReturnSelf();
        $clientMock->method('generativeModel')->willReturn($modelMock);
        $modelMock->method('withSystemInstruction')->willReturnSelf();
        $modelMock->method('startChat')->willReturn($chatSessionMock);
        $chatSessionMock->method('withHistory')->willReturnSelf();
        $chatSessionMock->method('sendMessage')->willReturn($responseMock);

        // Inject the mocked client
        $reflection = new \ReflectionClass(CoachAIAgent::class);
        $property = $reflection->getProperty('client');
        $property->setAccessible(true);
        $property->setValue($this->agent, $clientMock);

        $response = $this->agent->generateResponse($user, 'What is the meaning of life?');

        $this->assertEquals('Mocked AI Response', $response);
    }

    public function testGenerateNextStepNudgeSuccess(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getFullname')->willReturn('John Doe');

        $module = $this->createMock(Module::class);
        $module->method('getTitle')->willReturn('Module 1');

        $lesson = $this->createMock(Lesson::class);
        $lesson->method('getTitle')->willReturn('Lesson 1');
        $lesson->method('getModule')->willReturn($module);

        $clientMock = $this->createMock(Client::class);
        $modelMock = $this->createMock(GenerativeModel::class);
        $responseMock = clone $this->createMock(GenerateContentResponse::class);
        $responseMock->method('text')->willReturn('Super nudge message!');

        $clientMock->method('generativeModel')->willReturn($modelMock);
        $modelMock->method('generateContent')->willReturn($responseMock);

        // Inject the mocked client
        $reflection = new \ReflectionClass(CoachAIAgent::class);
        $property = $reflection->getProperty('client');
        $property->setAccessible(true);
        $property->setValue($this->agent, $clientMock);

        $response = $this->agent->generateNextStepNudge($user, $lesson);

        $this->assertEquals('Super nudge message!', $response);
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
