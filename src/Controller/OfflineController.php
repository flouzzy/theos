<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class OfflineController extends AbstractController
{
    #[Route('/offline', name: 'offline')]
    public function index(): Response
    {
        return $this->render('offline/index.html.twig');
    }
}
