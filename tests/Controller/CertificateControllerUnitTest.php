<?php

namespace App\Tests\Controller;

use App\Controller\CertificateController;
use App\Entity\Course;
use App\Entity\CourseCompletion;
use App\Entity\User;
use App\Repository\CourseCompletionRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Twig\Environment;

class CertificateControllerUnitTest extends TestCase
{
    private $controller;
    private $courseCompletionRepository;
    private $container;
    private $tokenStorage;
    private $requestStack;
    private $router;
    private $twig;
    private $user;
    private $course;

    protected function setUp(): void
    {
        $this->courseCompletionRepository = $this->createMock(CourseCompletionRepository::class);
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);
        $this->requestStack = $this->createMock(RequestStack::class);
        $this->router = $this->createMock(RouterInterface::class);
        $this->twig = $this->createMock(Environment::class);
        $this->container = $this->createMock(ContainerInterface::class);

        $this->user = $this->createMock(User::class);
        $this->course = $this->createMock(Course::class);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($this->user);
        $this->tokenStorage->method('getToken')->willReturn($token);

        $this->container->method('has')->willReturnMap([
            ['security.token_storage', true],
            ['request_stack', true],
            ['router', true],
            ['twig', true],
        ]);

        $this->container->method('get')->willReturnMap([
            ['security.token_storage', 1, $this->tokenStorage],
            ['request_stack', 1, $this->requestStack],
            ['router', 1, $this->router],
            ['twig', 1, $this->twig],
        ]);

        $this->controller = new CertificateController();
        $this->controller->setContainer($this->container);
    }

    public function testShowRedirectsWhenNotCompleted(): void
    {
        $this->courseCompletionRepository->expects($this->once())
            ->method('findOneBy')
            ->with([
                'user' => $this->user,
                'course' => $this->course,
                'completed' => true
            ])
            ->willReturn(null);

        $this->course->method('getSlug')->willReturn('test-course-slug');

        $session = $this->createMock(Session::class);
        $flashBag = $this->createMock(FlashBagInterface::class);
        $session->method('getFlashBag')->willReturn($flashBag);

        $this->requestStack->method('getSession')->willReturn($session);

        $flashBag->expects($this->once())
            ->method('add')
            ->with('error', 'You have not completed this course yet.');

        $this->router->expects($this->once())
            ->method('generate')
            ->with('course_show', ['slug' => 'test-course-slug'])
            ->willReturn('/course/test-course-slug');

        $response = $this->controller->show($this->course, $this->courseCompletionRepository);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/course/test-course-slug', $response->getTargetUrl());
    }

    public function testShowRendersCertificateWhenCompleted(): void
    {
        $completion = $this->createMock(CourseCompletion::class);
        $completionDate = new \DateTimeImmutable('2023-01-01');
        $completion->method('getUpdatedAt')->willReturn($completionDate);

        $this->courseCompletionRepository->expects($this->once())
            ->method('findOneBy')
            ->with([
                'user' => $this->user,
                'course' => $this->course,
                'completed' => true
            ])
            ->willReturn($completion);

        $this->twig->expects($this->once())
            ->method('render')
            ->with('certificate/index.html.twig', [
                'course' => $this->course,
                'user' => $this->user,
                'completionDate' => $completionDate,
            ])
            ->willReturn('certificate html content');

        $response = $this->controller->show($this->course, $this->courseCompletionRepository);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('certificate html content', $response->getContent());
    }
}
