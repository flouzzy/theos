<?php

namespace App\Controller;

use App\Entity\Notification;
use App\Repository\NotificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
#[Route('/notification', name: 'notification_')]
class NotificationController extends AbstractController
{

    /**
     * Show current user notifications
     */
    #[Route('/', name: 'index')]
    public function index(NotificationRepository $notificationRepository): Response
    {
        return $this->render('notification/index.html.twig', [
            'notifications' => $notificationRepository->findAllByUser($this->getUser(), 12),
        ]);
    }

    #[Route('/{id}', name: 'show')]
    public function show(Notification $notification, EntityManagerInterface $entityManager): Response
    {
        // We can't see other people personal notification...
        if ($notification->getUser() && $notification->getUser() !== $this->getUser()) {
            // Nothing to see here...
            return $this->redirectToRoute('notification_index');
        }

        // Mark as read if not read
        if (!$notification->isIsRead()) {
            $notification->setIsRead(true);
            $entityManager->flush();
        }

        // ... but we can see general notifications (without a targeted user)
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

    #[Route('/read/all', name: 'read_all')]
    public function markAllAsRead(EntityManagerInterface $entityManager): Response
    {
        /**
         * @var \App\Entity\User $currentUser
         */
        $currentUser = $this->getUser();
        $notifications = $currentUser->getNotifications();
        foreach ($notifications as $notification) {
            $notification->setIsRead(true);
        }
        $entityManager->flush();

        $this->addFlash('success', 'All notifications have been marked as read');
        return $this->redirectToRoute('notification_index');
    }
}
