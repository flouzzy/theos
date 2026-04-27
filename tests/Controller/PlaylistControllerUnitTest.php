<?php

namespace App\Tests\Controller;

use App\Controller\PlaylistController;
use App\Entity\Lesson;
use App\Entity\Playlist;
use App\Entity\User;
use App\Repository\LessonRepository;
use App\Repository\PlaylistRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Twig\Environment;

class PlaylistControllerUnitTest extends TestCase
{
    public function testIndex(): void
    {
        $user = $this->createMock(User::class);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage->method('getToken')->willReturn($token);

        $playlist1 = $this->createMock(Playlist::class);
        $playlist2 = $this->createMock(Playlist::class);

        $repo = $this->createMock(PlaylistRepository::class);
        $repo->expects($this->once())
            ->method('findBy')
            ->with(['owner' => $user])
            ->willReturn([$playlist1, $playlist2]);

        $twig = $this->createMock(Environment::class);
        $twig->expects($this->once())
            ->method('render')
            ->with('playlist/index.html.twig', ['playlists' => [$playlist1, $playlist2]])
            ->willReturn('rendered_view');

        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturnMap([
            ['security.token_storage', true],
            ['twig', true],
        ]);
        $container->method('get')->willReturnMap([
            ['security.token_storage', 1, $tokenStorage],
            ['twig', 1, $twig],
        ]);

        $controller = new PlaylistController();
        $controller->setContainer($container);

        $response = $controller->index($repo);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('rendered_view', $response->getContent());
    }

    public function testAddLessonSuccess(): void
    {
        $user = $this->createMock(User::class);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage->method('getToken')->willReturn($token);

        $lesson = $this->createMock(Lesson::class);

        $lessonRepository = $this->createMock(LessonRepository::class);
        $lessonRepository->expects($this->once())
            ->method('find')
            ->with(42)
            ->willReturn($lesson);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('getRepository')
            ->with(Lesson::class)
            ->willReturn($lessonRepository);

        $em->expects($this->once())->method('flush');

        $playlist = $this->createMock(Playlist::class);
        $playlist->expects($this->once())
            ->method('getOwner')
            ->willReturn($user);
        $playlist->expects($this->once())
            ->method('addLesson')
            ->with($lesson);

        $flashBag = $this->createMock(FlashBagInterface::class);
        $flashBag->expects($this->once())
            ->method('add')
            ->with('success', 'Leçon ajoutée à la playlist.');

        $session = $this->createMock(FlashBagAwareSessionInterface::class);
        $session->method('getFlashBag')->willReturn($flashBag);

        $request = new Request();
        $request->setSession($session);

        $requestStack = $this->createMock(RequestStack::class);
        $requestStack->method('getSession')->willReturn($session);

        $router = $this->createMock(RouterInterface::class);
        $router->expects($this->once())
            ->method('generate')
            ->with('playlist_index')
            ->willReturn('/playlist/');

        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturnMap([
            ['security.token_storage', true],
            ['request_stack', true],
            ['router', true],
        ]);
        $container->method('get')->willReturnMap([
            ['security.token_storage', 1, $tokenStorage],
            ['request_stack', 1, $requestStack],
            ['router', 1, $router],
        ]);

        $controller = new PlaylistController();
        $controller->setContainer($container);

        $response = $controller->addLesson($playlist, 42, $em);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/playlist/', $response->getTargetUrl());
    }

    public function testAddLessonAccessDenied(): void
    {
        $loggedUser = $this->createMock(User::class);
        $otherUser = $this->createMock(User::class);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($loggedUser);

        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage->method('getToken')->willReturn($token);

        $playlist = $this->createMock(Playlist::class);
        $playlist->expects($this->once())
            ->method('getOwner')
            ->willReturn($otherUser);

        $em = $this->createMock(EntityManagerInterface::class);

        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturnMap([
            ['security.token_storage', true],
        ]);
        $container->method('get')->willReturnMap([
            ['security.token_storage', 1, $tokenStorage],
        ]);

        $controller = new PlaylistController();
        $controller->setContainer($container);

        $this->expectException(AccessDeniedException::class);

        $controller->addLesson($playlist, 42, $em);
    }

    public function testAddLessonLessonNotFound(): void
    {
        $user = $this->createMock(User::class);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage->method('getToken')->willReturn($token);

        $lessonRepository = $this->createMock(LessonRepository::class);
        $lessonRepository->expects($this->once())
            ->method('find')
            ->with(99)
            ->willReturn(null);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('getRepository')
            ->with(Lesson::class)
            ->willReturn($lessonRepository);

        $em->expects($this->never())->method('flush');

        $playlist = $this->createMock(Playlist::class);
        $playlist->expects($this->once())
            ->method('getOwner')
            ->willReturn($user);
        $playlist->expects($this->never())
            ->method('addLesson');

        $router = $this->createMock(RouterInterface::class);
        $router->expects($this->once())
            ->method('generate')
            ->with('playlist_index')
            ->willReturn('/playlist/');

        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturnMap([
            ['security.token_storage', true],
            ['router', true],
        ]);
        $container->method('get')->willReturnMap([
            ['security.token_storage', 1, $tokenStorage],
            ['router', 1, $router],
        ]);

        $controller = new PlaylistController();
        $controller->setContainer($container);

        $response = $controller->addLesson($playlist, 99, $em);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/playlist/', $response->getTargetUrl());
    }
}
