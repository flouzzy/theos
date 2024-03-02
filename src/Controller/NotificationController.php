<?php

namespace App\Controller;

use App\Entity\Notification;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
#[Route('/notification', name: 'notification_')]
class NotificationController extends AbstractController
{

    /**
     * Show current user notifications
     */
    #[Route('/', name: 'index')]
    public function index(NotificationService $notificationService): Response
    {
        // $notificationService->createAndSendNotification($this->getUser(), 'Test de notification 3', 'Hello : Prêt à tout déchirer 22?', 'Nouvelle notification');

        /**
         * @var \App\Entity\User $user
         */
        $user = $this->getUser();
        return $this->render('notification/index.html.twig', [
            'notifications' => $user->getNotifications(),
        ]);
    }

    #[Route('/{id)', name: 'show')]
    public function show(Notification $notification): Response
    {
        // We can't see other people notification
        if ($notification->getUser() !== $this->getUser()) {
            return $this->redirectToRoute('notification_index');
        }
        return $this->render('notification/show.html.twig', [
            'notification' => $notification,
        ]);
    }

    #[Route('/{id}/read', name: 'read')]
    public function markAsRead(Notification $notification, EntityManagerInterface $entityManager): Response
    {
        // We can't see other people notification
        if ($notification->getUser() !== $this->getUser()) {
            return $this->redirectToRoute('notification_index');
        }

        $notification->setIsRead(true);
        $entityManager->flush();

        return $this->redirectToRoute('notification_index');
    }
}
