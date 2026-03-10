<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class LocaleController extends AbstractController
{
    #[Route('/change-locale/{locale}', name: 'app_change_locale')]
    public function changeLocale(string $locale, Request $request): Response
    {
        $request->getSession()->set('_locale', $locale);
        $request->setLocale($locale);

        $referer = $request->headers->get('referer');
        if (!$referer) {
            return $this->redirectToRoute('home');
        }

        return $this->redirect($referer);
    }
}
