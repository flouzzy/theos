<?php

namespace App\Tests\Controller;

use App\Controller\CoachController;
use App\Entity\Lesson;
use App\Entity\User;
use App\Service\CoachAIAgent;
use App\Service\CoachDataService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Twig\Environment;

class CoachControllerUnitTest extends TestCase
{
    private function createContainerWithUser(User $user = null, $twig = null)
    {
        $container = new Container();

        if ($user) {
            $token = $this->createMock(TokenInterface::class);
            $token->method('getUser')->willReturn($user);

            $tokenStorage = $this->createMock(TokenStorageInterface::class);
            $tokenStorage->method('getToken')->willReturn($token);

            $container->set('security.token_storage', $tokenStorage);
        }

        if ($twig) {
            $container->set('twig', $twig);
        }

        return $container;
    }

    public function testIndexDisabled()
    {
        $coachData = $this->createMock(CoachDataService::class);
        $controller = new CoachController(false, $coachData);

        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Coach is not enabled');

        $controller->index();
    }

    public function testIndexEnabled()
    {
        $user = new User();
        $user->setStreak(5);
        $user->setXp(100);
        // User has empty badges array by default

        $coachData = $this->createMock(CoachDataService::class);
        $coachData->expects($this->once())->method('getWeeklyXpData')->with($user)->willReturn([10, 20, 30]);

        $nextLesson = $this->createMock(Lesson::class);
        $coachData->expects($this->once())->method('getNextLesson')->with($user)->willReturn($nextLesson);

        $reviewLesson = $this->createMock(Lesson::class);
        $coachData->expects($this->once())->method('getLastCompletedLesson')->with($user)->willReturn($reviewLesson);

        $coachData->expects($this->once())->method('getStreakInfo')->with($user)->willReturn(['info' => 'test']);

        $twig = $this->createMock(Environment::class);
        $twig->expects($this->once())
            ->method('render')
            ->with('coach/index.html.twig', [
                'streak' => 5,
                'xp' => 100,
                'badgesCount' => 0,
                'weeklyXp' => [10, 20, 30],
                'weeklyXpTotal' => 60,
                'nextLesson' => $nextLesson,
                'reviewLesson' => $reviewLesson,
                'streakInfo' => ['info' => 'test'],
            ])
            ->willReturn('rendered_twig');

        $controller = new CoachController(true, $coachData);
        $controller->setContainer($this->createContainerWithUser($user, $twig));

        $response = $controller->index();
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('rendered_twig', $response->getContent());
    }

    public function testChatDisabled()
    {
        $coachData = $this->createMock(CoachDataService::class);
        $agent = $this->createMock(CoachAIAgent::class);
        $request = new Request([], [], [], [], [], [], json_encode(['message' => 'hi']));

        $controller = new CoachController(false, $coachData);
        $controller->setContainer(new Container());

        $response = $controller->chat($request, $agent);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(403, $response->getStatusCode());
        $this->assertEquals('{"error":"Coach is not enabled"}', $response->getContent());
    }

    public function testChatEmptyMessage()
    {
        $user = new User();
        $coachData = $this->createMock(CoachDataService::class);
        $agent = $this->createMock(CoachAIAgent::class);
        $request = new Request([], [], [], [], [], [], json_encode(['message' => '']));

        $controller = new CoachController(true, $coachData);
        $controller->setContainer($this->createContainerWithUser($user));

        $response = $controller->chat($request, $agent);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals('{"error":"Message is empty"}', $response->getContent());
    }

    public function testChatValidMessage()
    {
        $user = new User();
        $coachData = $this->createMock(CoachDataService::class);
        $agent = $this->createMock(CoachAIAgent::class);

        $message = 'Hello coach';
        $reply = 'Hello user';

        $agent->expects($this->once())
            ->method('generateResponse')
            ->with($user, $message)
            ->willReturn($reply);

        $request = new Request([], [], [], [], [], [], json_encode(['message' => $message]));

        $controller = new CoachController(true, $coachData);
        $controller->setContainer($this->createContainerWithUser($user));

        $response = $controller->chat($request, $agent);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('{"reply":"Hello user"}', $response->getContent());
    }

    public function testHistoryDisabled()
    {
        $coachData = $this->createMock(CoachDataService::class);
        $agent = $this->createMock(CoachAIAgent::class);

        $controller = new CoachController(false, $coachData);
        $controller->setContainer(new Container());

        $response = $controller->history($agent);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(403, $response->getStatusCode());
        $this->assertEquals('{"error":"Coach is not enabled"}', $response->getContent());
    }

    public function testHistoryEnabled()
    {
        $user = new User();
        $coachData = $this->createMock(CoachDataService::class);
        $agent = $this->createMock(CoachAIAgent::class);

        $history = [
            ['role' => 'user', 'content' => 'hi'],
            ['role' => 'assistant', 'content' => 'hello'],
        ];

        $agent->expects($this->once())
            ->method('getHistory')
            ->with($user)
            ->willReturn($history);

        $controller = new CoachController(true, $coachData);
        $controller->setContainer($this->createContainerWithUser($user));

        $response = $controller->history($agent);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('{"history":[{"role":"user","content":"hi"},{"role":"assistant","content":"hello"}]}', $response->getContent());
    }
}
