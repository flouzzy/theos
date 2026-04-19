<?php

namespace App\Controller;

use App\Repository\PageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PageController extends AbstractController
{
    #[Route('/{slug}', name: 'page_show')]
    public function show(string $slug, PageRepository $pageRepository): Response
    {
        $page = $pageRepository->findOneBy(['slug' => $slug]);
        if (!$page) {
            throw $this->createNotFoundException();
        }
        return $this->render('page/show.html.twig', [
            'page' => $page,
        ]);
    }
}
