<?php

namespace App\Controller;

use App\Repository\NotificationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\SecurityBundle\Security;

class UserCalendarController extends AbstractController
{
    #[Route('/calendar', name: 'calendar_index', priority: 10)]
    public function index(Security $security): Response
    {
        $user = $security->getUser();
        if (!$user instanceof User) {
            return $this->redirectToRoute('login');
        }

        // We assume active_cohort() twig function logic or similar
        // For now, we take the first cohort if available or use a strategy to find the active one
        $cohort = $user->getCohorts()->first() ?: null;

        return $this->render('calendar/index.html.twig', [
            'cohort' => $cohort,
            'calendar' => $cohort ? $cohort->getCalendar() : null,
            'events' => $cohort ? $cohort->getEvents() : [],
        ]);
    }
}
