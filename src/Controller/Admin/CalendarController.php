<?php

namespace App\Controller\Admin;

use App\Entity\Calendar;
use App\Entity\Event;
use App\Form\CalendarType;
use App\Form\EventType;
use App\Repository\CalendarRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/calendar', name: 'admin_calendar_')]
class CalendarController extends AbstractController
{
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(CalendarRepository $calendarRepository): Response
    {
        return $this->render('admin/calendar/index.html.twig', [
            'calendars' => $calendarRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $calendar = new Calendar();
        $form = $this->createForm(CalendarType::class, $calendar);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($calendar);
            $entityManager->flush();

            $this->addFlash('success', 'Calendrier créé avec succès.');
            return $this->redirectToRoute('admin_calendar_show', ['id' => $calendar->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/calendar/new.html.twig', [
            'calendar' => $calendar,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Calendar $calendar): Response
    {
        return $this->render('admin/calendar/show.html.twig', [
            'calendar' => $calendar,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Calendar $calendar, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CalendarType::class, $calendar);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Calendrier mis à jour.');
            return $this->redirectToRoute('admin_calendar_show', ['id' => $calendar->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/calendar/edit.html.twig', [
            'calendar' => $calendar,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/event/new', name: 'event_new', methods: ['GET', 'POST'])]
    public function eventNew(Request $request, Calendar $calendar, EntityManagerInterface $entityManager): Response
    {
        $event = new Event();
        $event->setCalendar($calendar);
        
        $form = $this->createForm(EventType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($event);
            $entityManager->flush();

            $this->addFlash('success', 'Événement ajouté au calendrier.');
            return $this->redirectToRoute('admin_calendar_show', ['id' => $calendar->getId()]);
        }

        return $this->render('admin/calendar/event_new.html.twig', [
            'calendar' => $calendar,
            'event' => $event,
            'form' => $form,
        ]);
    }

    #[Route('/event/{id}/edit', name: 'event_edit', methods: ['GET', 'POST'])]
    public function eventEdit(Request $request, Event $event, EntityManagerInterface $entityManager): Response
    {
        $calendar = $event->getCalendar();
        $form = $this->createForm(EventType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Événement mis à jour.');
            return $this->redirectToRoute('admin_calendar_show', ['id' => $calendar->getId()]);
        }

        return $this->render('admin/calendar/event_edit.html.twig', [
            'calendar' => $calendar,
            'event' => $event,
            'form' => $form,
        ]);
    }

    #[Route('/event/{id}/duplicate', name: 'event_duplicate', methods: ['POST'])]
    public function eventDuplicate(Request $request, Event $event, EntityManagerInterface $entityManager): Response
    {
        $calendarId = $event->getCalendar() ? $event->getCalendar()->getId() : null;

        if ($this->isCsrfTokenValid('duplicate' . $event->getId(), $request->request->getString('_token'))) {
            $duplicatedEvent = clone $event;
            $duplicatedEvent->setTitle($event->getTitle() . ' (copie)');

            $entityManager->persist($duplicatedEvent);
            $entityManager->flush();
            $this->addFlash('success', 'Événement dupliqué.');
        }

        if ($calendarId) {
            return $this->redirectToRoute('admin_calendar_show', ['id' => $calendarId]);
        }
        return $this->redirectToRoute('admin_calendar_index');
    }

    #[Route('/event/{id}/delete', name: 'event_delete', methods: ['POST'])]
    public function eventDelete(Request $request, Event $event, EntityManagerInterface $entityManager): Response
    {
        $calendarId = $event->getCalendar() ? $event->getCalendar()->getId() : null;
        
        if ($this->isCsrfTokenValid('delete' . $event->getId(), $request->request->getString('_token'))) {
            $entityManager->remove($event);
            $entityManager->flush();
            $this->addFlash('success', 'Événement supprimé.');
        }

        if ($calendarId) {
            return $this->redirectToRoute('admin_calendar_show', ['id' => $calendarId]);
        }
        return $this->redirectToRoute('admin_calendar_index');
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, Calendar $calendar, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $calendar->getId(), $request->request->getString('_token'))) {
            $entityManager->remove($calendar);
            $entityManager->flush();
            $this->addFlash('success', 'Calendrier supprimé.');
        }

        return $this->redirectToRoute('admin_calendar_index', [], Response::HTTP_SEE_OTHER);
    }
}
