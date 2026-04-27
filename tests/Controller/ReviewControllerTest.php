<?php

namespace App\Tests\Controller;

use App\Controller\ReviewController;
use App\Entity\Course;
use App\Entity\Review;
use App\Entity\User;
use App\Repository\CourseRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ReviewControllerTest extends TestCase
{
    private function createContainerWithUser(User $user, ?CsrfTokenManagerInterface $csrfTokenManager = null, ?RouterInterface $router = null, ?RequestStack $requestStack = null): Container
    {
        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);
        $tokenStorage->method('getToken')->willReturn($token);

        $container = new Container();
        $container->set('security.token_storage', $tokenStorage);

        if ($csrfTokenManager) {
            $container->set('security.csrf.token_manager', $csrfTokenManager);
        }

        if ($router) {
            $container->set('router', $router);
        }

        if ($requestStack) {
            $container->set('request_stack', $requestStack);
        }

        return $container;
    }

    public function testAddReviewNotFoundException()
    {
        $controller = new ReviewController();

        $em = $this->createMock(EntityManagerInterface::class);
        $repo = $this->createMock(CourseRepository::class);
        $repo->expects($this->once())->method('find')->with(1)->willReturn(null);

        $request = new Request();
        $user = new User();

        $controller->setContainer($this->createContainerWithUser($user));

        $this->expectException(NotFoundHttpException::class);
        $controller->add(1, $request, $em, $repo);
    }

    public function testAddReviewInvalidCsrfToken()
    {
        $controller = new ReviewController();

        $em = $this->createMock(EntityManagerInterface::class);
        $repo = $this->createMock(CourseRepository::class);
        $course = new Course();
        $repo->expects($this->once())->method('find')->with(1)->willReturn($course);

        $request = new Request();
        $request->request = new InputBag(['_token' => 'invalid_token']);
        $user = new User();

        $csrfTokenManager = $this->createMock(CsrfTokenManagerInterface::class);
        $csrfTokenManager->expects($this->once())->method('isTokenValid')->willReturn(false);

        $controller->setContainer($this->createContainerWithUser($user, $csrfTokenManager));

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('Invalid CSRF token.');
        $controller->add(1, $request, $em, $repo);
    }

    public function testAddReviewSuccess()
    {
        $controller = new ReviewController();

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())->method('persist')->with($this->isInstanceOf(Review::class));
        $em->expects($this->once())->method('flush');

        $repo = $this->createMock(CourseRepository::class);
        $course = new Course();
        $course->setSlug('test-course');
        $repo->expects($this->once())->method('find')->with(1)->willReturn($course);

        $request = new Request();
        $request->request = new InputBag([
            '_token' => 'valid_token',
            'comment' => 'Great course!',
            'rating' => 4,
        ]);

        $user = new User();

        $csrfTokenManager = $this->createMock(CsrfTokenManagerInterface::class);
        $csrfTokenManager->expects($this->once())->method('isTokenValid')->willReturn(true);

        $router = $this->createMock(RouterInterface::class);
        $router->expects($this->once())
            ->method('generate')
            ->with('course_show', ['slug' => 'test-course'])
            ->willReturn('/course/test-course');

        $session = new Session(new MockArraySessionStorage());
        $requestStack = new RequestStack();
        $requestStack->push($request);
        $request->setSession($session);

        $controller->setContainer($this->createContainerWithUser($user, $csrfTokenManager, $router, $requestStack));

        $response = $controller->add(1, $request, $em, $repo);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertTrue($response->isRedirect('/course/test-course'));

        $flashes = $session->getFlashBag()->get('success');
        $this->assertCount(1, $flashes);
        $this->assertEquals('Votre avis a été publié !', $flashes[0]);
    }
}
