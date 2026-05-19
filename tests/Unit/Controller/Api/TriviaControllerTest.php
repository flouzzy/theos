<?php
declare(strict_types=1);

namespace App\Tests\Unit\Controller\Api;

use App\Controller\Api\TriviaController;
use App\Repository\TriviaQuestionRepository;
use App\Service\GamificationService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\Request;

class TriviaControllerTest extends TestCase
{
    public function testCheckWithMalformedJson()
    {
        $controller = new TriviaController();
        $container = new Container();
        $controller->setContainer($container);

        $repo = $this->createMock(TriviaQuestionRepository::class);
        $gam = $this->createMock(GamificationService::class);
        $em = $this->createMock(EntityManagerInterface::class);

        $request = new Request([], [], [], [], [], [], 'invalid json');

        $repo->expects($this->once())
             ->method('find')
             ->with(0)
             ->willReturn(null);

        $response = $controller->check($request, $repo, $gam, $em);

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals('{"error":"Invalid question"}', $response->getContent());
    }

    public function testCheckWithNonExistentId()
    {
        $controller = new TriviaController();
        $container = new Container();
        $controller->setContainer($container);

        $repo = $this->createMock(TriviaQuestionRepository::class);
        $gam = $this->createMock(GamificationService::class);
        $em = $this->createMock(EntityManagerInterface::class);

        $request = new Request([], [], [], [], [], [], json_encode(['id' => 999]));

        $repo->expects($this->once())
             ->method('find')
             ->with(999)
             ->willReturn(null);

        $response = $controller->check($request, $repo, $gam, $em);

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals('{"error":"Invalid question"}', $response->getContent());
    }
}
