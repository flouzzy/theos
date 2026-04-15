<?php

namespace App\Tests\Unit\Twig\Components;

use App\Entity\Calendar;
use App\Entity\Event;
use App\Entity\EventCategory;
use App\Repository\EventCategoryRepository;
use App\Repository\EventRepository;
use App\Twig\Components\EventList;
use PHPUnit\Framework\TestCase;

class EventListTest extends TestCase
{
    private EventRepository $eventRepository;
    private EventCategoryRepository $eventCategoryRepository;
    private EventList $eventList;

    protected function setUp(): void
    {
        $this->eventRepository = $this->createMock(EventRepository::class);
        $this->eventCategoryRepository = $this->createMock(EventCategoryRepository::class);

        $this->eventList = new EventList(
            $this->eventRepository,
            $this->eventCategoryRepository
        );
    }

    public function testGetEventsCallsRepositoryWithCorrectArguments(): void
    {
        $calendar = $this->createMock(Calendar::class);
        $expectedEvents = [$this->createMock(Event::class)];

        $this->eventList->calendar = $calendar;
        $this->eventList->query = 'search term';
        $this->eventList->typeId = 5;
        $this->eventList->sortBy = 'date_desc';

        $this->eventRepository->expects($this->once())
            ->method('findFilteredEvents')
            ->with(
                $calendar,
                'search term',
                5,
                'date_desc'
            )
            ->willReturn($expectedEvents);

        $result = $this->eventList->getEvents();

        $this->assertSame($expectedEvents, $result);
    }

    public function testGetCategoriesCallsRepositoryWithCorrectArguments(): void
    {
        $expectedCategories = [$this->createMock(EventCategory::class)];

        $this->eventCategoryRepository->expects($this->once())
            ->method('findBy')
            ->with([], ['name' => 'ASC'])
            ->willReturn($expectedCategories);

        $result = $this->eventList->getCategories();

        $this->assertSame($expectedCategories, $result);
    }
}
