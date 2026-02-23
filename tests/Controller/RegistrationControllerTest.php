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
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
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

    protected function setUp(): void
    {
        // Mocks for constructor
        $this->emailVerifier = $this->createMock(EmailVerifier::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->jwt = $this->createMock(JWT::class);
        $this->mailer = $this->createMock(SendMail::class);

        // Mocks for method arguments
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->brevoApi = $this->createMock(BrevoApi::class);

        // Container mocks
        $this->container = $this->createMock(ContainerInterface::class);
        $this->requestStack = $this->createMock(RequestStack::class);
        $this->session = $this->createMock(SessionInterface::class);
        $this->flashBag = $this->createMock(FlashBagInterface::class);
        $this->router = $this->createMock(RouterInterface::class);

        // Setup session/flash chain
        $this->session->method('getFlashBag')->willReturn($this->flashBag);
        $this->requestStack->method('getSession')->willReturn($this->session);

        // Setup container
        $this->container->method('has')->willReturnMap([
            ['request_stack', true],
            ['router', true],
            ['parameter_bag', false],
        ]);

        $this->container->method('get')->willReturnMap([
            ['request_stack', 1, $this->requestStack],
            ['router', 1, $this->router],
        ]);

        // Setup parameters
        $this->container->method('getParameter')->willReturnMap([
            ['app.jwtsecret', 'secret'],
        ]);

        // Common router behavior
        $this->router->method('generate')->willReturn('/home');
    }

    private function getController(): RegistrationController
    {
        $controller = new RegistrationController(
            $this->emailVerifier,
            $this->entityManager,
            $this->translator,
            $this->jwt,
            $this->mailer
        );
        $controller->setContainer($this->container);
        return $controller;
    }

    public function testVerifyUserEmailSuccess(): void
    {
        $token = 'valid_token';

        // JWT Valid
        $this->jwt->method('isValid')->with($token)->willReturn(true);
        $this->jwt->method('isExpired')->with($token)->willReturn(false);
        $this->jwt->method('check')->with($token, 'secret')->willReturn(true);
        $this->jwt->method('getPayload')->with($token)->willReturn(['user_id' => 1]);

        // User Found and Not Verified
        $user = $this->createMock(User::class);
        $user->method('isVerified')->willReturn(false);
        $user->expects($this->once())->method('setIsVerified')->with(true);

        $this->userRepository->method('find')->with(1)->willReturn($user);

        // Capture flashes
        $flashes = [];
        $this->flashBag->method('add')->willReturnCallback(function($type, $msg) use (&$flashes) {
            $flashes[] = [$type, $msg];
        });

        // Execute
        $response = $this->getController()->verifyUserEmail($token, $this->userRepository, $this->brevoApi);

        // Assertions
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertCount(1, $flashes, 'Should have exactly 1 success flash');
        $this->assertEquals(['success', 'Your email address has been verified'], $flashes[0]);
    }

    public function testVerifyUserEmailAlreadyVerified(): void
    {
        $token = 'valid_token';

        // JWT Valid
        $this->jwt->method('isValid')->with($token)->willReturn(true);
        $this->jwt->method('isExpired')->with($token)->willReturn(false);
        $this->jwt->method('check')->with($token, 'secret')->willReturn(true);
        $this->jwt->method('getPayload')->with($token)->willReturn(['user_id' => 1]);

        // User Found and ALREADY Verified
        $user = $this->createMock(User::class);
        $user->method('isVerified')->willReturn(true);
        $user->expects($this->never())->method('setIsVerified');

        $this->userRepository->method('find')->with(1)->willReturn($user);

        // Capture flashes
        $flashes = [];
        $this->flashBag->method('add')->willReturnCallback(function($type, $msg) use (&$flashes) {
            $flashes[] = [$type, $msg];
        });

        // Execute
        $response = $this->getController()->verifyUserEmail($token, $this->userRepository, $this->brevoApi);

        // Assertions
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertCount(1, $flashes, 'Should have exactly 1 info flash');
        $this->assertEquals(['info', 'Your email address is already verified'], $flashes[0]);
    }

    public function testVerifyUserEmailInvalidToken(): void
    {
        $token = 'invalid_token';

        // JWT Invalid
        $this->jwt->method('isValid')->with($token)->willReturn(false);
        // Short circuit evaluation means isExpired/check might not be called, but we don't care about expectation strictness here much.

        // Capture flashes
        $flashes = [];
        $this->flashBag->method('add')->willReturnCallback(function($type, $msg) use (&$flashes) {
            $flashes[] = [$type, $msg];
        });

        // Execute
        $response = $this->getController()->verifyUserEmail($token, $this->userRepository, $this->brevoApi);

        // Assertions
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertCount(1, $flashes, 'Should have exactly 1 danger flash');
        $this->assertEquals(['danger', 'The token is invalid or has expired'], $flashes[0]);
    }

    public function testVerifyUserEmailExpiredToken(): void
    {
        $token = 'expired_token';

        $this->jwt->method('isValid')->willReturn(true);
        $this->jwt->method('isExpired')->willReturn(true); // Expired!

        $flashes = [];
        $this->flashBag->method('add')->willReturnCallback(function($type, $msg) use (&$flashes) {
            $flashes[] = [$type, $msg];
        });

        $this->getController()->verifyUserEmail($token, $this->userRepository, $this->brevoApi);

        $this->assertCount(1, $flashes);
        $this->assertEquals(['danger', 'The token is invalid or has expired'], $flashes[0]);
    }

    public function testVerifyUserEmailUserNotFound(): void
    {
        $token = 'valid_token_no_user';

        $this->jwt->method('isValid')->willReturn(true);
        $this->jwt->method('isExpired')->willReturn(false);
        $this->jwt->method('check')->willReturn(true);
        $this->jwt->method('getPayload')->willReturn(['user_id' => 999]);

        $this->userRepository->method('find')->with(999)->willReturn(null);

        $flashes = [];
        $this->flashBag->method('add')->willReturnCallback(function($type, $msg) use (&$flashes) {
            $flashes[] = [$type, $msg];
        });

        $this->getController()->verifyUserEmail($token, $this->userRepository, $this->brevoApi);

        // User not found -> Falls through to generic error
        $this->assertCount(1, $flashes);
        $this->assertEquals(['danger', 'The token is invalid or has expired'], $flashes[0]);
    }
}
