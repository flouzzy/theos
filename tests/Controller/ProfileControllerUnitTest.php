<?php

namespace App\Tests\Controller;

use App\Controller\ProfileController;
use App\Entity\PortfolioProject;
use App\Entity\User;
use App\Service\MediaManager;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class ProfileControllerUnitTest extends TestCase
{
    public function testAddPortfolioWithMaliciousUrl()
    {
        // Mocks
        $mediaManager = $this->createMock(MediaManager::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $container = $this->createMock(ContainerInterface::class);
        $csrfTokenManager = $this->createMock(CsrfTokenManagerInterface::class);
        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $token = $this->createMock(TokenInterface::class);
        $user = $this->createMock(User::class);
        $router = $this->createMock(RouterInterface::class);

        // Setup Request with malicious URL
        $request = new Request([], [
            '_token' => 'valid_token',
            'title' => 'Malicious Project',
            'description' => 'Desc',
            'url' => 'javascript:alert(1)'
        ]);

        // Mock Session
        $session = $this->createMock(FlashBagAwareSessionInterface::class);
        $flashBag = $this->createMock(FlashBagInterface::class);
        $session->method('getFlashBag')->willReturn($flashBag);
        $request->setSession($session);

        // Mock RequestStack
        $requestStack = $this->createMock(RequestStack::class);
        $requestStack->method('getCurrentRequest')->willReturn($request);
        $requestStack->method('getSession')->willReturn($session);

        // Setup Container
        $container->method('has')->willReturn(true);
        $container->method('get')->willReturnMap([
            ['security.csrf.token_manager', 1, $csrfTokenManager],
            ['security.token_storage', 1, $tokenStorage],
            ['router', 1, $router],
            ['request_stack', 1, $requestStack],
        ]);

        // Mock CSRF check
        $csrfTokenManager->method('isTokenValid')->willReturn(true);

        // Mock User
        $tokenStorage->method('getToken')->willReturn($token);
        $token->method('getUser')->willReturn($user);

        // Mock Router
        $router->method('generate')->willReturn('/profile');

        // Expect Flash Error
        $flashBag->expects($this->once())
            ->method('add')
            ->with('error', 'Invalid URL. Only http and https are allowed.');

        // Expect NO Persist
        $entityManager->expects($this->never())
            ->method('persist');

        // Instantiate Controller
        $controller = new ProfileController($mediaManager);
        $controller->setContainer($container);

        // Execute
        $controller->addPortfolio($request, $entityManager);
    }

    public function testAddPortfolioWithValidUrl()
    {
        // Mocks
        $mediaManager = $this->createMock(MediaManager::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $container = $this->createMock(ContainerInterface::class);
        $csrfTokenManager = $this->createMock(CsrfTokenManagerInterface::class);
        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $token = $this->createMock(TokenInterface::class);
        $user = $this->createMock(User::class);
        $router = $this->createMock(RouterInterface::class);

        // Setup Request with valid URL
        $request = new Request([], [
            '_token' => 'valid_token',
            'title' => 'Valid Project',
            'description' => 'Desc',
            'url' => 'https://example.com'
        ]);

        // Mock Session
        $session = $this->createMock(FlashBagAwareSessionInterface::class);
        $flashBag = $this->createMock(FlashBagInterface::class);
        $session->method('getFlashBag')->willReturn($flashBag);
        $request->setSession($session);

        // Mock RequestStack
        $requestStack = $this->createMock(RequestStack::class);
        $requestStack->method('getCurrentRequest')->willReturn($request);
        $requestStack->method('getSession')->willReturn($session);

        // Setup Container
        $container->method('has')->willReturn(true);
        $container->method('get')->willReturnMap([
            ['security.csrf.token_manager', 1, $csrfTokenManager],
            ['security.token_storage', 1, $tokenStorage],
            ['router', 1, $router],
            ['request_stack', 1, $requestStack],
        ]);

        // Mock CSRF check
        $csrfTokenManager->method('isTokenValid')->willReturn(true);

        // Mock User
        $tokenStorage->method('getToken')->willReturn($token);
        $token->method('getUser')->willReturn($user);

        // Mock Router
        $router->method('generate')->willReturn('/profile');

        // Capture persisted entity
        $persistedProject = null;
        $entityManager->expects($this->once())
            ->method('persist')
            ->willReturnCallback(function($project) use (&$persistedProject) {
                $persistedProject = $project;
            });

        // Expect Success Flash
        $flashBag->expects($this->once())
            ->method('add')
            ->with('success', 'Project added to portfolio!');

        // Instantiate Controller
        $controller = new ProfileController($mediaManager);
        $controller->setContainer($container);

        // Execute
        $controller->addPortfolio($request, $entityManager);

        // Assert
        $this->assertInstanceOf(PortfolioProject::class, $persistedProject);
        $this->assertEquals('https://example.com', $persistedProject->getUrl());
    }
}
