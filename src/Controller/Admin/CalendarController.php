<?php

namespace App\Controller\Admin;

use App\Entity\Calendar;
use App\Form\CalendarType;
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

            return $this->redirectToRoute('admin_calendar_index', [], Response::HTTP_SEE_OTHER);
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

            return $this->redirectToRoute('admin_calendar_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/calendar/edit.html.twig', [
            'calendar' => $calendar,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/duplicate', name: 'duplicate', methods: ['POST'])]
    public function duplicate(Request $request, Calendar $calendar, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('duplicate' . $calendar->getId(), $request->request->get('_token'))) {
            $newCalendar = clone $calendar;
            
            // Note: The 'clone' in PHP is shallow. We need to handle related entities if needed.
            // But Calendar has a OneToOne with Cohort. We should probably NOT clone the cohort link
            // to avoid unique constraint violations.
            $newCalendar->setCohort(null);
            $newCalendar->setDescription($calendar->getDescription() . ' (Copy)');
            
            $entityManager->persist($newCalendar);

            // Clone events associated with the original cohort if we want to keep them for the new calendar
            // Wait, events are linked to Cohort, not Calendar directly according to the schema.
            // Cohort has OneToOne Calendar. Cohort has OneToMany Event.
            // If we duplicate a calendar, we probably want to associate it with a new cohort later.
            // If the user wants to duplicate "the planning", they might mean cloning events too.
            if ($calendar->getCohort()) {
                foreach ($calendar->getCohort()->getEvents() as $event) {
                    $newEvent = clone $event;
                    // We don't have a new cohort yet, so we leave it null or link to same if needed?
                    // User said "copy another (duplicate)". 
                    // Usually implies creating a draft for a new promo.
                    $newEvent->setCohort(null); 
                    // This might be tricky because Event doesn't have a direct link to Calendar.
                    // Let's stick to cloning the Calendar object first.
                    $entityManager->persist($newEvent);
                }
            }

            $entityManager->flush();
            $this->addFlash('success', 'Calendrier dupliqué avec succès.');

            return $this->redirectToRoute('admin_calendar_edit', ['id' => $newCalendar->getId()]);
        }

        return $this->redirectToRoute('admin_calendar_index');
    }

    #[Route('/{id}', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, Calendar $calendar, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $calendar->getId(), $request->request->get('_token'))) {
            $entityManager->remove($calendar);
            $entityManager->flush();
        }

        return $this->redirectToRoute('admin_calendar_index', [], Response::HTTP_SEE_OTHER);
    }
}
