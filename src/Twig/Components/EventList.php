<?php

namespace App\Twig\Components;

use App\Entity\Calendar;
use App\Repository\EventCategoryRepository;
use App\Repository\EventRepository;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent('EventList')]
class EventList
{
    use DefaultActionTrait;

    #[LiveProp]
    public Calendar $calendar;

    #[LiveProp(writable: true)]
    public string $query = '';

    #[LiveProp(writable: true)]
    public ?int $typeId = null;

    #[LiveProp(writable: true)]
    public string $sortBy = 'date_asc';

    public function __construct(
        private EventRepository $eventRepository,
        private EventCategoryRepository $eventCategoryRepository
    ) {
    }

    /**
     * @return array<\App\Entity\Event>
     */
    public function getEvents(): array
    {
        return $this->eventRepository->findFilteredEvents(
            $this->calendar,
            $this->query,
            $this->typeId,
            $this->sortBy
        );
    }

    /**
     * @return array<\App\Entity\EventCategory>
     */
    public function getCategories(): array
    {
        return $this->eventCategoryRepository->findBy([], ['name' => 'ASC']);
    }
}
