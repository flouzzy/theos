<?php

namespace App\EventSubscriber;

use App\Event\LessonCompleteEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class LessonSubscriber implements EventSubscriberInterface
{


    public static function getSubscribedEvents(): array
    {
        return [
            'LessonCompleteEvent' => 'onLessonCompleteEvent',
        ];
    }

    public function onLessonCompleteEvent(LessonCompleteEvent $event): void
    {
        // dd('onLessonCompleteEvent', $event);
    }
}
