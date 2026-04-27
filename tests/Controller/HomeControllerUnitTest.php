<?php

namespace App\Tests\Controller;

use App\Controller\HomeController;
use App\Entity\User;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Twig\Environment;

class HomeControllerUnitTest extends TestCase
{
    public function testIndexUnauthenticated(): void
    {
        // For unauthenticated, tokenStorage might return null or a token with a null user.
        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage->method('getToken')->willReturn(null);

        $twig = $this->createMock(Environment::class);
        $twig->expects($this->once())
            ->method('render')
            ->with('home/landing.html.twig', [])
            ->willReturn('Landing Page Content');

        $container = new Container();
        $container->set('security.token_storage', $tokenStorage);
        $container->set('twig', $twig);

        $controller = new HomeController();
        $controller->setContainer($container);

        $response = $controller->index();

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('Landing Page Content', $response->getContent());
    }

    public function testIndexAuthenticated(): void
    {
        $user = $this->createMock(User::class);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage->method('getToken')->willReturn($token);

        $router = $this->createMock(RouterInterface::class);
        $router->expects($this->once())
            ->method('generate')
            ->with('cohort_index')
            ->willReturn('/cohort');

        $container = new Container();
        $container->set('security.token_storage', $tokenStorage);
        $container->set('router', $router);

        $controller = new HomeController();
        $controller->setContainer($container);

        $response = $controller->index();

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/cohort', $response->getTargetUrl());
    }
}
