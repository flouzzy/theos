<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function index(): Response
    {
        $template = 'landing';
        $params = [];

        /**
         * @var \App\Entity\User|null $user
         */
        $user = $this->getUser();
        if ($user) {
            return $this->redirectToRoute('cohort_index');
        }

        return $this->render('home/' . $template . '.html.twig', $params);
    }
}
