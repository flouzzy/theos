<?php

declare(strict_types=1);

namespace App\Twig;

use App\Entity\Event;
use App\Service\CalendarExportService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class CalendarExtension extends AbstractExtension
{
    public function __construct(
        private readonly CalendarExportService $calendarExportService
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('google_calendar_url', [$this->calendarExportService, 'generateGoogleUrl']),
            new TwigFunction('outlook_calendar_url', [$this->calendarExportService, 'generateOutlookUrl']),
        ];
    }
}
