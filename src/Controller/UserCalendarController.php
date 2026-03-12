<?php

namespace App\Controller;

use App\Service\CohortSession;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class UserCalendarController extends AbstractController
{
    #[Route('/calendar', name: 'calendar_index', priority: 10)]
    public function index(CohortSession $cohortSession): Response
    {
        $cohort = $cohortSession->getSelectedCohort();

        return $this->render('calendar/index.html.twig', [
            'cohort' => $cohort,
            'calendar' => $cohort ? $cohort->getCalendar() : null,
            'events' => $cohort ? $cohort->getEvents() : [],
        ]);
    }
}
