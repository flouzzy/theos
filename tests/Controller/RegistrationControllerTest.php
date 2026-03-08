<?php

namespace App\Tests\Controller;

use App\Controller\RegistrationController;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Security\EmailVerifier;
use App\Service\BrevoApi;
use App\Service\JWT;
use App\Service\SendMail;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

class RegistrationControllerTest extends TestCase
{
    private $emailVerifier;
    private $entityManager;
    private $translator;
    private $jwt;
    private $mailer;
    private $userRepository;
    private $brevoApi;
    private $container;
    private $requestStack;
    private $session;
    private $flashBag;
    private $router;
    private $parameterBag;
    private $controller;

    protected function setUp(): void
    {
        $this->emailVerifier = $this->createMock(EmailVerifier::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->jwt = $this->createMock(JWT::class);
        $this->mailer = $this->createMock(SendMail::class);
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->brevoApi = $this->createMock(BrevoApi::class);
        $this->container = $this->createMock(ContainerInterface::class);
        $this->requestStack = $this->createMock(RequestStack::class);
        $this->session = $this->createMock(FlashBagAwareSessionInterface::class);
        $this->flashBag = $this->createMock(FlashBagInterface::class);
        $this->router = $this->createMock(RouterInterface::class);
        $this->parameterBag = $this->createMock(ParameterBagInterface::class);

        $this->session->method('getFlashBag')->willReturn($this->flashBag);
        $this->requestStack->method('getSession')->willReturn($this->session);

        $this->container->method('has')->willReturn(true);
        $this->container->method('get')->willReturnMap([
            ['request_stack', 1, $this->requestStack],
            ['router', 1, $this->router],
            ['parameter_bag', 1, $this->parameterBag],
        ]);
        $this->parameterBag->method('get')->with('app.jwtsecret')->willReturn('secret');

        $this->controller = new RegistrationController($this->entityManager, $this->translator, $this->jwt, $this->mailer, 'test@example.com', 'Test Sender');
        $this->controller->setContainer($this->container);
    }

    public function testVerifyUserEmailSuccess()
    {
        $token = 'valid.token';
        $userId = 123;
        $user = $this->createMock(User::class);

        $this->jwt->method('isValid')->with($token)->willReturn(true);
        $this->jwt->method('isExpired')->with($token)->willReturn(false);
        $this->jwt->method('check')->with($token, 'secret')->willReturn(true);
        $this->jwt->method('getPayload')->with($token)->willReturn(['user_id' => $userId]);

        $this->userRepository->method('find')->with($userId)->willReturn($user);

        $user->method('isVerified')->willReturn(false);
        $user->expects($this->once())->method('setIsVerified')->with(true);

        $this->entityManager->expects($this->once())->method('flush')->with($user);
        $this->brevoApi->expects($this->once())->method('addOrUpdateContact')->with($user);

        $flashes = [];
        $this->flashBag->expects($this->once())->method('add')
            ->willReturnCallback(function($type, $message) use (&$flashes) {
                $flashes[] = [$type, $message];
            });

        $this->router->method('generate')->with('home')->willReturn('/home');

        $response = $this->controller->verifyUserEmail($token, $this->userRepository, $this->brevoApi);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertCount(1, $flashes);
        $this->assertEquals(['success', 'Your email address has been verified'], $flashes[0]);
    }

    public function testVerifyUserEmailAlreadyVerified()
    {
        $token = 'valid.token';
        $userId = 123;
        $user = $this->createMock(User::class);

        $this->jwt->method('isValid')->with($token)->willReturn(true);
        $this->jwt->method('isExpired')->with($token)->willReturn(false);
        $this->jwt->method('check')->with($token, 'secret')->willReturn(true);
        $this->jwt->method('getPayload')->with($token)->willReturn(['user_id' => $userId]);

        $this->userRepository->method('find')->with($userId)->willReturn($user);

        $user->method('isVerified')->willReturn(true);
        $user->expects($this->never())->method('setIsVerified');

        $this->entityManager->expects($this->never())->method('flush');
        $this->brevoApi->expects($this->never())->method('addOrUpdateContact');

        $flashes = [];
        $this->flashBag->expects($this->once())->method('add')
            ->willReturnCallback(function($type, $message) use (&$flashes) {
                $flashes[] = [$type, $message];
            });

        $this->router->method('generate')->with('home')->willReturn('/home');

        $response = $this->controller->verifyUserEmail($token, $this->userRepository, $this->brevoApi);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertCount(1, $flashes);
        $this->assertEquals(['success', 'Your email address has been verified'], $flashes[0]);
    }

    public function testVerifyUserEmailInvalidToken()
    {
        $token = 'invalid.token';

        $this->jwt->method('isValid')->with($token)->willReturn(false);

        $flashes = [];
        $this->flashBag->expects($this->once())->method('add')
            ->willReturnCallback(function($type, $message) use (&$flashes) {
                $flashes[] = [$type, $message];
            });

        $this->router->method('generate')->with('home')->willReturn('/home');

        $response = $this->controller->verifyUserEmail($token, $this->userRepository, $this->brevoApi);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertCount(1, $flashes);
        $this->assertEquals(['danger', 'The token is invalid or has expired'], $flashes[0]);
    }
}
