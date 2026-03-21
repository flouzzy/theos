<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Playlist;
use App\Entity\Lesson;
use App\Repository\PlaylistRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/playlist', name: 'playlist_')]
#[IsGranted('IS_AUTHENTICATED')]
class PlaylistController extends AbstractController
{
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(PlaylistRepository $repo): Response
    {
        return $this->render('playlist/index.html.twig', [
            'playlists' => $repo->findBy(['owner' => $this->getUser()]),
        ]);
    }

    #[Route('/{id}/add/{lessonId}', name: 'add_lesson', methods: ['POST'])]
    public function addLesson(Playlist $playlist, int $lessonId, EntityManagerInterface $em): Response
    {
        if ($playlist->getOwner() !== $this->getUser()) throw $this->createAccessDeniedException();
        $lesson = $em->getRepository(Lesson::class)->find($lessonId);
        if ($lesson) {
            $playlist->addLesson($lesson);
            $em->flush();
            $this->addFlash('success', 'Leçon ajoutée à la playlist.');
        }
        return $this->redirectToRoute('playlist_index');
    }
}
