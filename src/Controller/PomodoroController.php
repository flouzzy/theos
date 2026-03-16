<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\PomodoroRoom;
use App\Repository\PomodoroRoomRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/pomodoro', name: 'pomodoro_')]
#[IsGranted('IS_AUTHENTICATED')]
class PomodoroController extends AbstractController
{
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(PomodoroRoomRepository $repo): Response
    {
        return $this->render('pomodoro/index.html.twig', [
            'rooms' => $repo->findAll(),
        ]);
    }

    #[Route('/{id}/join', name: 'join', methods: ['GET'])]
    public function join(PomodoroRoom $room, \Redis $redis): Response
    {
        $redis->sAdd('pomodoro_room_' . $room->getId(), (string)$this->getUser()->getId());
        
        return $this->render('pomodoro/room.html.twig', [
            'room' => $room,
            'participants' => $redis->sMembers('pomodoro_room_' . $room->getId()),
        ]);
    }
}
