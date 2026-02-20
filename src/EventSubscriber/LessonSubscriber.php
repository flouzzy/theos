<?php

namespace App\EventSubscriber;

use App\Event\LessonCompleteEvent;
use App\Service\CompletionService;
use App\Service\GamificationService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class LessonSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private CompletionService $completionService,
        private GamificationService $gamificationService,
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
