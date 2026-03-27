<?php

namespace App\Controller\Api;

use App\Entity\Event;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api', name: 'api_')]
#[IsGranted('IS_AUTHENTICATED')]
class CalendarController extends AbstractController
{
    #[Route('/calendar/event/{id}/update', name: 'calendar_update', methods: ['PATCH'])]
    public function updateEvent(Event $event, Request $request, EntityManagerInterface $em): JsonResponse
    {
        // Vérification ownership si nécessaire
        $data = json_decode($request->getContent(), true);
        
        if (isset($data['start'])) {
            $event->setStartAt(new \DateTimeImmutable($data['start']));
        }
        if (isset($data['end'])) {
            $event->setEndAt(new \DateTimeImmutable($data['end']));
        }
        
        $em->flush();
        return $this->json(['success' => true]);
    }
}
