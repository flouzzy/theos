<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Event;
use App\Entity\Calendar;

class CalendarExportService
{
    public function generateIcsForEvent(Event $event): string
    {
        $ics = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//Le Rocher Academie//Calendar Export//FR',
            'BEGIN:VEVENT',
            'UID:' . uniqid() . '@academie.lerocher.fr',
            'DTSTAMP:' . gmdate('Ymd\THis\Z'),
            'SUMMARY:' . $this->escapeString($event->getTitle()),
            'DTSTART:' . $event->getStartAt()->format('Ymd\THis\Z'),
        ];

        if ($event->getEndAt()) {
            $ics[] = 'DTEND:' . $event->getEndAt()->format('Ymd\THis\Z');
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
            $ics[] = 'DTSTART:' . $event->getStartAt()->format('Ymd\THis\Z');
            
            if ($event->getEndAt()) {
                $ics[] = 'DTEND:' . $event->getEndAt()->format('Ymd\THis\Z');
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
}
