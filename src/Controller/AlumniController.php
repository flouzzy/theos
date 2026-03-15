<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/alumni', name: 'alumni_')]
#[IsGranted('IS_AUTHENTICATED')]
class AlumniController extends AbstractController
{
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(UserRepository $repo): Response
    {
        return $this->render('alumni/index.html.twig', [
            'alumni' => $repo->findAlumni(),
        ]);
    }
}
