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
        if ($this->getUser()) {
            $template = 'index';
        }

        return $this->render('home/' . $template . '.html.twig', [
            'controller_name' => 'HomeController',
        ]);
    }
}
