<?php

namespace App\EventSubscriber;

use App\Event\LessonCompleteEvent;
use App\Service\CompletionService;
use App\Service\GamificationService;
use App\Service\NotificationService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class LessonSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private CompletionService $completionService,
        private GamificationService $gamificationService,
        private NotificationService $notificationService,
        private TranslatorInterface $translator,
        private Environment $twig
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LessonCompleteEvent::class => 'onLessonCompleteEvent',
        ];
    }

    public function onLessonCompleteEvent(LessonCompleteEvent $event): void
    {
        if (!$event->getCompleted()) {
            return;
        }

        $user = $event->getUser();
        $now = new \DateTimeImmutable();
        $hour = (int)$now->format('H');

        // Study time anomaly detection: late night (00:00 - 05:00)
        if ($hour >= 0 && $hour < 5) {
            $this->notificationService->addNotification(
                $user,
                "🌙 Tu étudies tard !",
                "C'est super d'être motivé, mais n'oublie pas que le sommeil est essentiel pour bien mémoriser ce que tu apprends. Repose-toi bien !"
            );
        }

        // Send notification to all users
        $content = $this->twig->render('notification/emails/lesson_completed.html.twig', [
            'user' => $event->getUser(),
            'lesson' => $event->getLesson()
        ]);

        $this->completionService->sendNotificationToAllUsers(
            $content,
            $this->translator->trans('Lesson completed for') . ' ' . $event->getUser()->getFirstname()
        );

        // Award XP only if not previously completed
        if (!$event->isPreviouslyCompleted()) {
            $this->gamificationService->addXp($event->getUser(), 10, 'lesson_completed');
        }
    }
}
