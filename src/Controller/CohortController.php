<?php

namespace App\Controller;

use App\Entity\Cohort;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/cohort', name: 'cohort_')]
class CohortController extends AbstractController
{
    #[Route('/', name: 'index', priority: 3)]
    public function index(): Response
    {
        // Récupère l'utilisateur actuellement authentifié
        /** @var User $user */
        $user = $this->getUser();

        // Récupère toutes ses cohortes
        $cohorts = $user->getCohorts();

        return $this->render('cohort/index.html.twig', [
            'cohorts' => $cohorts,
        ]);
    }

    #[Route('/{slug}', name: 'show')]
    public function show(Cohort $cohort): Response
    {
        return $this->render('cohort/show.html.twig', [
            'cohort' => [$cohort],
        ]);
    }
}
