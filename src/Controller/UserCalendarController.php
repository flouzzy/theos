<?php

namespace App\Controller;

use App\Entity\Calendar;
use App\Entity\Event;
use App\Service\CalendarExportService;
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
        $calendar = $cohort ? $cohort->getCalendar() : null;

        return $this->render('calendar/index.html.twig', [
            'cohort' => $cohort,
            'calendar' => $calendar,
            'events' => $calendar ? $calendar->getEvents() : [],
        ]);
    }

    #[Route('/calendar/export/{id}', name: 'calendar_export')]
    public function export(Calendar $calendar, CalendarExportService $exportService): Response
    {
        $icsContent = $exportService->generateIcsForCalendar($calendar);

        return new Response($icsContent, 200, [
            'Content-Type' => 'text/calendar',
            'Content-Disposition' => 'attachment; filename="calendar.ics"',
        ]);
    }

    #[Route('/calendar/event/{id}/export', name: 'calendar_event_export')]
    public function exportEvent(Event $event, CalendarExportService $exportService): Response
    {
        $icsContent = $exportService->generateIcsForEvent($event);

        return new Response($icsContent, 200, [
            'Content-Type' => 'text/calendar',
            'Content-Disposition' => 'attachment; filename="event.ics"',
        ]);
    }
}
