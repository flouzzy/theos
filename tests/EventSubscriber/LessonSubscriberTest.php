<?php

namespace App\Tests\EventSubscriber;

use App\Entity\Lesson;
use App\Entity\User;
use App\Event\LessonCompleteEvent;
use App\EventSubscriber\LessonSubscriber;
use App\Service\CompletionService;
use App\Service\GamificationService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class LessonSubscriberTest extends TestCase
{
    private CompletionService|MockObject $completionService;
    private GamificationService|MockObject $gamificationService;
    private TranslatorInterface|MockObject $translator;
    private Environment|MockObject $twig;
    private LessonSubscriber $subscriber;

    protected function setUp(): void
    {
        $this->completionService = $this->createMock(CompletionService::class);
        $this->gamificationService = $this->createMock(GamificationService::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->twig = $this->createMock(Environment::class);

        $this->subscriber = new LessonSubscriber(
            $this->completionService,
            $this->gamificationService,
            $this->translator,
            $this->twig
        );
    }

    public function testGetSubscribedEvents(): void
    {
        $events = LessonSubscriber::getSubscribedEvents();
        $this->assertArrayHasKey(LessonCompleteEvent::class, $events);
        $this->assertSame('onLessonCompleteEvent', $events[LessonCompleteEvent::class]);
    }

    public function testOnLessonCompleteEventNotCompleted(): void
    {
        $lesson = $this->createMock(Lesson::class);
        $user = $this->createMock(User::class);
        // Assuming future signature: Lesson, User, completed=false, previouslyCompleted=false
        $event = new LessonCompleteEvent($lesson, $user, false, false);

        $this->completionService->expects($this->never())->method('sendNotificationToAllUsers');
        $this->gamificationService->expects($this->never())->method('addXp');

        $this->subscriber->onLessonCompleteEvent($event);
    }

    public function testOnLessonCompleteEventCompletedAndNew(): void
    {
        $lesson = $this->createMock(Lesson::class);
        $user = $this->createMock(User::class);
        $user->method('getFirstname')->willReturn('John');

        // completed=true, previouslyCompleted=false
        $event = new LessonCompleteEvent($lesson, $user, true, false);

        $this->twig->expects($this->once())
            ->method('render')
            ->with('notification/emails/lesson_completed.html.twig', ['user' => $user, 'lesson' => $lesson])
            ->willReturn('email content');

        $this->translator->expects($this->once())
            ->method('trans')
            ->with('Lesson completed for')
            ->willReturn('Lesson completed for');

        $this->completionService->expects($this->once())
            ->method('sendNotificationToAllUsers')
            ->with('email content', 'Lesson completed for John');

        $this->gamificationService->expects($this->once())
            ->method('addXp')
            ->with($user, 10, 'lesson_completed');

        $this->subscriber->onLessonCompleteEvent($event);
    }

    public function testOnLessonCompleteEventCompletedButAlreadyDone(): void
    {
        $lesson = $this->createMock(Lesson::class);
        $user = $this->createMock(User::class);
        $user->method('getFirstname')->willReturn('John');

        // completed=true, previouslyCompleted=true
        $event = new LessonCompleteEvent($lesson, $user, true, true);

        $this->twig->expects($this->once())
            ->method('render')
            ->with('notification/emails/lesson_completed.html.twig', ['user' => $user, 'lesson' => $lesson])
            ->willReturn('email content');

        $this->translator->expects($this->once())
            ->method('trans')
            ->with('Lesson completed for')
            ->willReturn('Lesson completed for');

        $this->completionService->expects($this->once())
            ->method('sendNotificationToAllUsers')
            ->with('email content', 'Lesson completed for John');

        // Should NOT award XP
        $this->gamificationService->expects($this->never())->method('addXp');

        $this->subscriber->onLessonCompleteEvent($event);
    }
}
