<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function index(): Response
    {
        $template = 'landing';
        $params = [];

        /**
         * @var \App\Entity\User $user
         */
        $user = $this->getUser();
        if ($user) {
            // Page d'accueil si on est connecté
            $template = 'index';

            $params = [
                // Cours de l'utilisateur courant
                'courses' => $user->getCourses()
            ];
        }

        return $this->render('home/' . $template . '.html.twig', $params);
    }
}
