<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Calendar;
use App\Entity\Event;
use App\Service\CalendarExportService;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;

class CalendarExportServiceTest extends TestCase
{
    public function testGenerateIcsForEvent(): void
    {
        $event = $this->createMock(Event::class);
        $event->method('getTitle')->willReturn('Test Event');
        $event->method('getStartAt')->willReturn(new \DateTimeImmutable('2026-03-11 10:00:00'));
        $event->method('getEndAt')->willReturn(new \DateTimeImmutable('2026-03-11 12:00:00'));
        $event->method('getLocation')->willReturn('Online');

        $service = new CalendarExportService();
        $ics = $service->generateIcsForEvent($event);

        $this->assertStringContainsString('BEGIN:VCALENDAR', $ics);
        $this->assertStringContainsString('SUMMARY:Test Event', $ics);
        $this->assertStringContainsString('DTSTART:20260311T100000Z', $ics);
        $this->assertStringContainsString('LOCATION:Online', $ics);
        $this->assertStringContainsString('END:VEVENT', $ics);
    }

    public function testGenerateIcsForCalendar(): void
    {
        $event = $this->createMock(Event::class);
        $event->method('getId')->willReturn(1);
        $event->method('getTitle')->willReturn('Event 1');
        $event->method('getStartAt')->willReturn(new \DateTimeImmutable('2026-03-11 10:00:00'));

        $calendar = $this->createMock(Calendar::class);
        $calendar->method('getTitle')->willReturn('My Calendar');
        $calendar->method('getEvents')->willReturn(new ArrayCollection([$event]));

        $service = new CalendarExportService();
        $ics = $service->generateIcsForCalendar($calendar);

        $this->assertStringContainsString('X-WR-CALNAME:My Calendar', $ics);
        $this->assertStringContainsString('SUMMARY:Event 1', $ics);
    }
}
