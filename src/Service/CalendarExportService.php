<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Event;
use App\Entity\Calendar;

class CalendarExportService
{
    public function generateIcsForEvent(Event $event): string
    {
        $utc = new \DateTimeZone('UTC');
        $ics = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//Le Rocher Academie//Calendar Export//FR',
            'BEGIN:VEVENT',
            'UID:' . uniqid() . '@academie.lerocher.fr',
            'DTSTAMP:' . gmdate('Ymd\THis\Z'),
            'SUMMARY:' . $this->escapeString($event->getTitle()),
            'DTSTART:' . $event->getStartAt()->setTimezone($utc)->format('Ymd\THis\Z'),
        ];

        if ($event->getEndAt()) {
            $ics[] = 'DTEND:' . $event->getEndAt()->setTimezone($utc)->format('Ymd\THis\Z');
        }

        if ($event->getLocation()) {
            $ics[] = 'LOCATION:' . $this->escapeString($event->getLocation());
        }

        $ics[] = 'END:VEVENT';
        $ics[] = 'END:VCALENDAR';

        return implode("\r\n", $ics);
    }

    public function generateIcsForCalendar(Calendar $calendar): string
    {
        $utc = new \DateTimeZone('UTC');
        $ics = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//Le Rocher Academie//Calendar Export//FR',
            'X-WR-CALNAME:' . $this->escapeString($calendar->getTitle() ?? 'Calendrier'),
        ];

        foreach ($calendar->getEvents() as $event) {
            $ics[] = 'BEGIN:VEVENT';
            $ics[] = 'UID:' . $event->getId() . '@academie.lerocher.fr';
            $ics[] = 'DTSTAMP:' . gmdate('Ymd\THis\Z');
            $ics[] = 'SUMMARY:' . $this->escapeString($event->getTitle());
            $ics[] = 'DTSTART:' . $event->getStartAt()->setTimezone($utc)->format('Ymd\THis\Z');
            
            if ($event->getEndAt()) {
                $ics[] = 'DTEND:' . $event->getEndAt()->setTimezone($utc)->format('Ymd\THis\Z');
            }

            if ($event->getLocation()) {
                $ics[] = 'LOCATION:' . $this->escapeString($event->getLocation());
            }
            $ics[] = 'END:VEVENT';
        }

        $ics[] = 'END:VCALENDAR';

        return implode("\r\n", $ics);
    }

    private function escapeString(?string $string): string
    {
        if (!$string) return '';
        return str_replace([',', ';', "\n"], ['\,', '\;', ' '], $string);
    }

    public function generateGoogleUrl(Event $event): string
    {
        $baseUrl = 'https://www.google.com/calendar/render?action=TEMPLATE';
        $params = [
            'text' => $event->getTitle(),
            'dates' => $this->formatDateRange($event),
            'details' => 'Événement de l\'Académie Le Rocher',
            'location' => $event->getLocation(),
            'sf' => 'true',
            'output' => 'xml'
        ];

        return $baseUrl . '&' . http_build_query($params);
    }

    public function generateOutlookUrl(Event $event): string
    {
        $baseUrl = 'https://outlook.live.com/calendar/0/deeplink/compose?path=/calendar/action/compose&rru=addevent';
        $params = [
            'subject' => $event->getTitle(),
            'startdt' => $event->getStartAt()->format('Y-m-d\TH:i:s'),
            'enddt' => $event->getEndAt() ? $event->getEndAt()->format('Y-m-d\TH:i:s') : $event->getStartAt()->modify('+1 hour')->format('Y-m-d\TH:i:s'),
            'body' => 'Événement de l\'Académie Le Rocher',
            'location' => $event->getLocation()
        ];

        return $baseUrl . '&' . http_build_query($params);
    }

    public function generateAppleUrl(Event $event): string
    {
        // Apple usually uses .ics files for "Add to calendar" buttons on web.
        // We can just point to our ICS export route.
        return ''; 
    }

    private function formatDateRange(Event $event): string
    {
        $utc = new \DateTimeZone('UTC');
        $start = $event->getStartAt()->setTimezone($utc)->format('Ymd\THis\Z');
        
        $endDate = $event->getEndAt() ?? $event->getStartAt()->modify('+1 hour');
        $end = $endDate->setTimezone($utc)->format('Ymd\THis\Z');
        
        return $start . '/' . $end;
    }
}
