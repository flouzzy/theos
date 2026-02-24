<?php

namespace App\Tests\Unit\Controller;

use App\Controller\CourseController;
use App\Entity\Course;
use App\Entity\User;
use App\Repository\CompletionRepository;
use App\Repository\CourseCompletionRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Twig\Environment;

class CourseControllerTest extends TestCase
{
    public function testShowUsesOptimizedRepositoryMethod()
    {
        // Mocks
        $course = $this->createMock(Course::class);
        $completionRepository = $this->createMock(CompletionRepository::class);
        $courseCompletionRepository = $this->createMock(CourseCompletionRepository::class);
        $user = $this->createMock(User::class);

        // Controller setup
        $controller = new CourseController();
        $container = $this->createMock(ContainerInterface::class);

        // Mock Security (getUser)
        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);
        $tokenStorage->method('getToken')->willReturn($token);

        // Mock Twig (render)
        $twig = $this->createMock(Environment::class);
        $twig->expects($this->once())
            ->method('render')
            ->willReturn('html content');

        // Setup Container
        $container->method('has')->willReturnMap([
            ['security.token_storage', true],
            ['twig', true],
        ]);

        $container->method('get')->willReturnMap([
            ['security.token_storage', $tokenStorage],
            ['twig', $twig],
        ]);

        $controller->setContainer($container);

        // Expectation: findCompletedLessonIdsByCourse should be called
        $completionRepository->expects($this->once())
            ->method('findCompletedLessonIdsByCourse')
            ->with($user, $course)
            ->willReturn([1, 2, 3]);

        // Course Setup
        $course->method('getStatus')->willReturn('published');
        $course->method('getAuthor')->willReturn($this->createMock(User::class)); // Different author

        // Call the method
        $controller->show($course, $completionRepository, $courseCompletionRepository);
    }
}
