<?php

namespace App\Controller;

use App\Entity\Cohort;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/cohort', name: 'cohort_')]
class CohortController extends AbstractController
{
    #[Route('/', name: 'index', priority: 3)]
    public function index(): Response
    {
        // Récupère l'utilisateur actuellement authentifié
        /** @var User $user */
        $user = $this->getUser();
        
        // Redirect to login if not authenticated
        if (!$user) {
            return $this->redirectToRoute('login');
        }
        
        // Récupère toutes ses cohortes
        $cohorts = $user->getCohorts();
        // Récupère les cours auxquels l'utilisateur est inscrit
        $myCourses = $user->getCourses();

        return $this->render('cohort/index.html.twig', [
            'cohorts' => $cohorts,
            'myCourses' => $myCourses,
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
