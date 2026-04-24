<?php

namespace App\Tests\Controller;

use App\Controller\LessonController;
use App\Entity\User;
use App\Repository\CompletionRepository;
use App\Service\CompletionService;
use App\Service\GamificationService;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class LessonControllerUnitTest extends TestCase
{
    private function createController(): LessonController
    {
        $completionRepository = $this->createMock(CompletionRepository::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $completionService = $this->createMock(CompletionService::class);
        $gamificationService = $this->createMock(GamificationService::class);
        $notificationService = $this->createMock(NotificationService::class);
        $translator = $this->createMock(TranslatorInterface::class);

        return new LessonController(
            $completionRepository,
            $entityManager,
            $dispatcher,
            $completionService,
            $gamificationService,
            $notificationService,
            $translator
        );
    }

    public function testClaimEasterEggUnauthorized(): void
    {
        $controller = $this->createController();

        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage->method('getToken')->willReturn(null); // No token

        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturnMap([
            ['security.token_storage', true],
        ]);
        $container->method('get')->willReturnMap([
            ['security.token_storage', $tokenStorage],
        ]);

        $controller->setContainer($container);

        $request = new Request();
        $gamificationService = $this->createMock(GamificationService::class);

        $response = $controller->claimEasterEgg($request, $gamificationService);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString('{"error":"Unauthorized"}', (string) $response->getContent());
    }

    public function testClaimEasterEggInvalidCsrf(): void
    {
        $controller = $this->createController();

        $user = $this->createMock(User::class);
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage->method('getToken')->willReturn($token);

        $csrfTokenManager = $this->createMock(CsrfTokenManagerInterface::class);
        $csrfTokenManager->method('isTokenValid')->willReturn(false); // Invalid token

        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturnMap([
            ['security.token_storage', true],
            ['security.csrf.token_manager', true],
        ]);
        $container->method('get')->willReturnMap([
            ['security.token_storage', $tokenStorage],
            ['security.csrf.token_manager', $csrfTokenManager],
        ]);

        $controller->setContainer($container);

        $request = new Request();
        $request->request = new InputBag(['_token' => 'invalid_token']);

        $gamificationService = $this->createMock(GamificationService::class);

        $response = $controller->claimEasterEgg($request, $gamificationService);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString('{"error":"Invalid CSRF token"}', (string) $response->getContent());
    }

    public function testClaimEasterEggSuccess(): void
    {
        $controller = $this->createController();

        $user = $this->createMock(User::class);
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage->method('getToken')->willReturn($token);

        $csrfTokenManager = $this->createMock(CsrfTokenManagerInterface::class);
        $csrfTokenManager->method('isTokenValid')->willReturn(true); // Valid token

        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturnMap([
            ['security.token_storage', true],
            ['security.csrf.token_manager', true],
        ]);
        $container->method('get')->willReturnMap([
            ['security.token_storage', $tokenStorage],
            ['security.csrf.token_manager', $csrfTokenManager],
        ]);

        $controller->setContainer($container);

        $request = new Request();
        $request->request = new InputBag(['_token' => 'valid_token']);

        $gamificationService = $this->createMock(GamificationService::class);

        $gamificationService->expects($this->once())
            ->method('addXp')
            ->with($user, 50, 'easter_egg_found');

        $gamificationService->expects($this->once())
            ->method('awardBadge')
            ->with(
                $user,
                'EASTER_EGG_HUNTER',
                'Chasseur de Trésors',
                'Bravo ! Tu as trouvé un secret caché dans les leçons.'
            );

        $response = $controller->claimEasterEgg($request, $gamificationService);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $expectedContent = json_encode([
            'success' => true,
            'message' => 'Félicitations ! Tu as trouvé un secret ! (+50 XP)'
        ]);
        $this->assertJsonStringEqualsJsonString($expectedContent, (string) $response->getContent());
    }
}
