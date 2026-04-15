<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller\Api;

use PHPUnit\Framework\TestCase;

use App\Controller\Api\TriviaController;
use App\Entity\TriviaQuestion;
use App\Entity\User;
use App\Repository\TriviaQuestionRepository;
use App\Service\GamificationService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class TriviaControllerTest extends TestCase
{
    private TriviaController $controller;
    private TriviaQuestionRepository&MockObject $repo;
    private GamificationService&MockObject $gamification;
    private EntityManagerInterface&MockObject $em;
    private User $user;

    protected function setUp(): void
    {
        $this->repo = $this->createMock(TriviaQuestionRepository::class);
        $this->gamification = $this->createMock(GamificationService::class);
        $this->em = $this->createMock(EntityManagerInterface::class);

        $this->user = new User();
        $this->user->setQuizCombo(0);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($this->user);

        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage->method('getToken')->willReturn($token);

        $container = new Container();
        $container->set('security.token_storage', $tokenStorage);

        $this->controller = new TriviaController();
        $this->controller->setContainer($container);
    }

    public function testCheckCorrectAnswerInitialCombo(): void
    {
        $request = new Request([], [], [], [], [], [], json_encode(['id' => 1, 'answer' => 'A']));

        $question = $this->createMock(TriviaQuestion::class);
        $question->method('getCorrectAnswer')->willReturn('A');

        $this->repo->expects($this->once())->method('find')->with(1)->willReturn($question);

        $this->user->setQuizCombo(0);

        $this->gamification->expects($this->once())
            ->method('addXp')
            ->with($this->user, 20, 'trivia_win');

        $this->em->expects($this->once())->method('flush');

        $response = $this->controller->check($request, $this->repo, $this->gamification, $this->em);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('{"correct":true,"combo":1}', $response->getContent());
        $this->assertEquals(1, $this->user->getQuizCombo());
    }

    public function testCheckCorrectAnswerIncrementsComboMultiplier(): void
    {
        $request = new Request([], [], [], [], [], [], json_encode(['id' => 1, 'answer' => 'A']));

        $question = $this->createMock(TriviaQuestion::class);
        $question->method('getCorrectAnswer')->willReturn('A');

        $this->repo->expects($this->once())->method('find')->with(1)->willReturn($question);

        $this->user->setQuizCombo(4);

        // Combo becomes 5. Multiplier = 1 + (5/5)*0.1 = 1.1. XP = 20 * 1.1 = 22
        $this->gamification->expects($this->once())
            ->method('addXp')
            ->with($this->user, 22, 'trivia_win');

        $this->em->expects($this->once())->method('flush');

        $response = $this->controller->check($request, $this->repo, $this->gamification, $this->em);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('{"correct":true,"combo":5}', $response->getContent());
        $this->assertEquals(5, $this->user->getQuizCombo());
    }

    public function testCheckIncorrectAnswerResetsCombo(): void
    {
        $request = new Request([], [], [], [], [], [], json_encode(['id' => 1, 'answer' => 'B']));

        $question = $this->createMock(TriviaQuestion::class);
        $question->method('getCorrectAnswer')->willReturn('A');

        $this->repo->expects($this->once())->method('find')->with(1)->willReturn($question);

        $this->user->setQuizCombo(5);

        $this->gamification->expects($this->never())->method('addXp');
        $this->em->expects($this->once())->method('flush');

        $response = $this->controller->check($request, $this->repo, $this->gamification, $this->em);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('{"correct":false}', $response->getContent());
        $this->assertEquals(0, $this->user->getQuizCombo());
    }

    public function testCheckInvalidQuestion(): void
    {
        $request = new Request([], [], [], [], [], [], json_encode(['id' => 999, 'answer' => 'A']));

        $this->repo->expects($this->once())->method('find')->with(999)->willReturn(null);

        $this->gamification->expects($this->never())->method('addXp');
        $this->em->expects($this->never())->method('flush');

        $response = $this->controller->check($request, $this->repo, $this->gamification, $this->em);

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertEquals('{"error":"Invalid question"}', $response->getContent());
    }
}
