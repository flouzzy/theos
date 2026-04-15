<?php

namespace {
    // Create a stub class if Redis does not exist in the global namespace
    if (!class_exists('Redis')) {
        class Redis {
            public function sAdd(string $key, string $value): int|false { return 1; }
            public function sMembers(string $key): array { return []; }
        }
    }
}

namespace App\Tests\Controller {

    use App\Controller\PomodoroController;
    use App\Entity\PomodoroRoom;
    use App\Entity\User;
    use PHPUnit\Framework\TestCase;
    use Symfony\Component\DependencyInjection\ContainerInterface;
    use Symfony\Component\HttpFoundation\Response;
    use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
    use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
    use Twig\Environment;
    use Redis;

    class PomodoroControllerUnitTest extends TestCase
    {
        public function testJoin()
        {
            $room = $this->createMock(PomodoroRoom::class);
            $room->method('getId')->willReturn(42);

            $redis = $this->createMock(Redis::class);
            $redis->expects($this->once())
                ->method('sAdd')
                ->with('pomodoro_room_42', '100');
            $redis->expects($this->once())
                ->method('sMembers')
                ->with('pomodoro_room_42')
                ->willReturn(['100', '101']);

            $user = $this->createMock(User::class);
            $user->method('getId')->willReturn(100);

            $token = $this->createMock(TokenInterface::class);
            $token->method('getUser')->willReturn($user);

            $tokenStorage = $this->createMock(TokenStorageInterface::class);
            $tokenStorage->method('getToken')->willReturn($token);

            $twig = $this->createMock(Environment::class);
            $twig->expects($this->once())
                ->method('render')
                ->with('pomodoro/room.html.twig', [
                    'room' => $room,
                    'participants' => ['100', '101'],
                ])
                ->willReturn('rendered_content');

            $container = $this->createMock(ContainerInterface::class);
            $container->method('has')->willReturnMap([
                ['security.token_storage', true],
                ['twig', true],
            ]);
            $container->method('get')->willReturnMap([
                ['security.token_storage', 1, $tokenStorage],
                ['twig', 1, $twig],
            ]);

            $controller = new PomodoroController();
            $controller->setContainer($container);

            $response = $controller->join($room, $redis);

            $this->assertInstanceOf(Response::class, $response);
            $this->assertEquals('rendered_content', $response->getContent());
        }
    }
}
