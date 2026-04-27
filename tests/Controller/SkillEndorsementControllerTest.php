<?php

namespace App\Tests\Controller;

use App\Controller\SkillEndorsementController;
use App\Entity\Skill;
use App\Entity\SkillEndorsement;
use App\Entity\User;
use App\Repository\SkillRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class SkillEndorsementControllerTest extends TestCase
{
    public function testAddSuccess()
    {
        $userId = 1;
        $skillId = 2;

        $receiver = $this->createMock(User::class);
        $skill = $this->createMock(Skill::class);
        $giver = $this->createMock(User::class);

        $em = $this->createMock(EntityManagerInterface::class);
        $userRepo = $this->createMock(UserRepository::class);
        $skillRepo = $this->createMock(SkillRepository::class);

        $userRepo->method('find')->with($userId)->willReturn($receiver);
        $skillRepo->method('find')->with($skillId)->willReturn($skill);

        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($giver);
        $tokenStorage->method('getToken')->willReturn($token);

        $router = $this->createMock(RouterInterface::class);
        $router->method('generate')
            ->with('profile_public', ['id' => $userId])
            ->willReturn('/profile/1');

        $session = $this->createMock(FlashBagAwareSessionInterface::class);
        $flashBag = $this->createMock(FlashBagInterface::class);
        $session->method('getFlashBag')->willReturn($flashBag);

        $request = new Request();
        $request->setSession($session);

        $requestStack = $this->createMock(RequestStack::class);
        $requestStack->method('getSession')->willReturn($session);

        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturn(true);
        $container->method('get')->willReturnMap([
            ['security.token_storage', 1, $tokenStorage],
            ['router', 1, $router],
            ['request_stack', 1, $requestStack],
        ]);

        $flashBag->expects($this->once())
            ->method('add')
            ->with('success', 'Compétence approuvée !');

        $persistedEndorsement = null;
        $em->expects($this->once())
            ->method('persist')
            ->willReturnCallback(function($endorsement) use (&$persistedEndorsement) {
                $persistedEndorsement = $endorsement;
            });

        $em->expects($this->once())
            ->method('flush');

        $controller = new SkillEndorsementController();
        $controller->setContainer($container);

        $response = $controller->add($userId, $skillId, $em, $userRepo, $skillRepo);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/profile/1', $response->getTargetUrl());

        $this->assertInstanceOf(SkillEndorsement::class, $persistedEndorsement);
        $this->assertSame($receiver, $persistedEndorsement->getReceiver());
        $this->assertSame($giver, $persistedEndorsement->getGiver());
        $this->assertSame($skill, $persistedEndorsement->getSkill());
    }

    public function testAddReceiverNotFound()
    {
        $userId = 1;
        $skillId = 2;

        $em = $this->createMock(EntityManagerInterface::class);
        $userRepo = $this->createMock(UserRepository::class);
        $skillRepo = $this->createMock(SkillRepository::class);

        $userRepo->method('find')->with($userId)->willReturn(null);
        $skillRepo->method('find')->with($skillId)->willReturn($this->createMock(Skill::class));

        $controller = new SkillEndorsementController();

        $this->expectException(NotFoundHttpException::class);

        $controller->add($userId, $skillId, $em, $userRepo, $skillRepo);
    }

    public function testAddSkillNotFound()
    {
        $userId = 1;
        $skillId = 2;

        $em = $this->createMock(EntityManagerInterface::class);
        $userRepo = $this->createMock(UserRepository::class);
        $skillRepo = $this->createMock(SkillRepository::class);

        $userRepo->method('find')->with($userId)->willReturn($this->createMock(User::class));
        $skillRepo->method('find')->with($skillId)->willReturn(null);

        $controller = new SkillEndorsementController();

        $this->expectException(NotFoundHttpException::class);

        $controller->add($userId, $skillId, $em, $userRepo, $skillRepo);
    }
}
