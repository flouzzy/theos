<?php

namespace App\Tests\Service;

use App\Entity\User;
use App\Entity\Lesson;
use App\Entity\Course;
use App\Entity\Module;
use App\Entity\Completion;
use App\Entity\Cohort;
use App\Repository\UserRepository;
use App\Repository\CompletionRepository;
use App\Repository\LessonRepository;
use App\Service\TriggerService;
use App\Service\NotificationService;
use App\Service\CoachAIAgent;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Psr\Clock\ClockInterface;
use Symfony\Component\Clock\MockClock;
use Doctrine\Common\Collections\ArrayCollection;

class TriggerServiceTest extends TestCase
{
    private UserRepository&MockObject $userRepository;
    private CompletionRepository&MockObject $completionRepository;
    private LessonRepository&MockObject $lessonRepository;
    private NotificationService&MockObject $notificationService;
    private CoachAIAgent&MockObject $aiAgent;
    private UrlGeneratorInterface&MockObject $urlGenerator;
    private MockClock $clock;
    private LessonRepository&MockObject $lessonRepository;
    private TriggerService $triggerService;

    protected function setUp(): void
    {
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->completionRepository = $this->createMock(CompletionRepository::class);
        $this->lessonRepository = $this->createMock(LessonRepository::class);
        $this->notificationService = $this->createMock(NotificationService::class);
        $this->aiAgent = $this->createMock(CoachAIAgent::class);
        $this->urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $this->clock = new MockClock();
        $this->lessonRepository = $this->createMock(LessonRepository::class);

        $this->triggerService = new TriggerService(
            $this->userRepository,
            $this->completionRepository,
            $this->lessonRepository,
            $this->notificationService,
            $this->aiAgent,
            $this->urlGenerator,
            $this->lessonRepository,
            $this->clock
        );
    }

    public function testProcessDailyTriggersWithNoUsers(): void
    {
        $this->userRepository->expects($this->once())
            ->method('findAll')
            ->willReturn([]);

        $this->notificationService->expects($this->never())
            ->method('addNotification');

        $this->triggerService->processDailyTriggers();
    }

    public function testProcessGoalReminderTriggerSendsNotification(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getCustomGoal')->willReturn('Learn Symfony');
        $user->method('getTimezone')->willReturn('UTC');
        $user->method('getCourses')->willReturn(new ArrayCollection([]));
        $user->method('getCohorts')->willReturn(new ArrayCollection([]));

        $this->clock->modify('2023-11-01 10:30:00');

        $this->userRepository->expects($this->once())
            ->method('findAll')
            ->willReturn([$user]);

        $this->urlGenerator->method('generate')->willReturn('http://example.com');

        $this->notificationService->expects($this->once())
            ->method('addNotification')
            ->with(
                $user,
                "🎯 Rappel de ton objectif",
                "Garde le cap ! Tu travailles pour : 'Learn Symfony'. Une petite leçon aujourd'hui pour t'en rapprocher ?",
                'http://example.com'
            );

        $this->triggerService->processDailyTriggers();
    }

    public function testProcessWeeklyReflectionTriggerSendsNotification(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getTimezone')->willReturn('UTC');
        $user->method('getCourses')->willReturn(new ArrayCollection([]));
        $user->method('getCohorts')->willReturn(new ArrayCollection([]));

        $this->clock->modify('2023-11-05 18:30:00');

        $this->userRepository->expects($this->once())
            ->method('findAll')
            ->willReturn([$user]);

        $this->urlGenerator->method('generate')->willReturn('http://example.com/profile');

        $this->notificationService->expects($this->once())
            ->method('addNotification')
            ->with(
                $user,
                "📓 C'est l'heure du bilan !",
                "La semaine se termine. Prends un instant pour réfléchir à ce que tu as appris et fixe tes objectifs pour la semaine prochaine.",
                'http://example.com/profile'
            );

        $this->triggerService->processDailyTriggers();
    }

    public function testProcessMorningRoutineTriggerSendsNotification(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getTimezone')->willReturn('UTC');
        $user->method('getCohorts')->willReturn(new ArrayCollection([]));

        $module = $this->createMock(Module::class);
        $module->method('getSlug')->willReturn('module-1');
        $module->method('getId')->willReturn(1);

        $lesson = $this->createMock(Lesson::class);
        $lesson->method('getId')->willReturn(1);
        $lesson->method('getTitle')->willReturn('Audio Lesson');
        $lesson->method('getAudioPath')->willReturn('/path/to/audio.mp3');
        $lesson->method('getModule')->willReturn($module);

        $lesson2 = $this->createMock(Lesson::class);
        $lesson2->method('getId')->willReturn(2);
        $lesson2->method('getModule')->willReturn($module);

        $module->method('getLessons')->willReturn(new ArrayCollection([$lesson, $lesson2]));

        $course = $this->createMock(Course::class);
        $course->method('getSlug')->willReturn('course-1');
        $course->method('getModules')->willReturn(new ArrayCollection([$module]));

        $module->method('getCourses')->willReturn(new ArrayCollection([$course]));

        $user->method('getCourses')->willReturn(new ArrayCollection([$course]));

        $this->clock->modify('2023-11-01 07:00:00');

        $this->userRepository->expects($this->once())
            ->method('findAll')
            ->willReturn([$user]);

        $this->lessonRepository->expects($this->once())
            ->method('findFirstUncompletedAudioLessonWithContext')
            ->with($user)
            ->willReturn([
                'lesson' => $lesson,
                'module' => $module,
                'course' => $course,
            ]);

        $this->completionRepository->method('findCompletedLessonIdsByUser')->willReturn([1, 2]);

        $this->urlGenerator->method('generate')->willReturn('http://example.com/lesson/1');

        $routineCalled = false;
        $this->notificationService->expects($this->any())
            ->method('addNotification')
            ->willReturnCallback(function($u, $title, $message, $link) use (&$routineCalled) {
                if ($title === '☕ Ta routine matinale') {
                    $routineCalled = true;
                    $this->assertEquals('http://example.com/lesson/1', $link);
                }
            });

        $this->triggerService->processDailyTriggers();
        $this->assertTrue($routineCalled);
    }

    public function testProcessFomoTriggerSendsNotification(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getTimezone')->willReturn('UTC');

        $lesson = $this->createMock(Lesson::class);
        $lesson->method('getId')->willReturn(1);
        $lesson->method('getTitle')->willReturn('FOMO Lesson');

        $module = $this->createMock(Module::class);
        $module->method('getSlug')->willReturn('module-1');
        $module->method('getId')->willReturn(1);

        $lesson2 = $this->createMock(Lesson::class);
        $lesson2->method('getId')->willReturn(2);
        $lesson2->method('getTitle')->willReturn('FOMO Lesson 2');
        $lesson2->method('getModule')->willReturn($module);

        $module->method('getLessons')->willReturn(new ArrayCollection([$lesson, $lesson2]));

        $lesson->method('getModule')->willReturn($module);

        $course = $this->createMock(Course::class);
        $course->method('getSlug')->willReturn('course-1');
        $course->method('getModules')->willReturn(new ArrayCollection([$module]));

        $cohort = $this->createMock(Cohort::class);
        $cohort->method('getUsers')->willReturn(new ArrayCollection([1, 2, 3, 4, 5])); // 5 users
        $cohort->method('getCourses')->willReturn(new ArrayCollection([$course]));
        $cohort->method('getTitle')->willReturn('Promo 2024');

        $user->method('getCohorts')->willReturn(new ArrayCollection([$cohort]));
        $user->method('getCourses')->willReturn(new ArrayCollection([$course]));

        $this->clock->modify('2023-11-01 13:00:00');

        $this->userRepository->expects($this->once())
            ->method('findAll')
            ->willReturn([$user]);

        $this->completionRepository->method('findCompletedLessonIdsByUser')
            ->willReturn([1]);

        $this->lessonRepository->expects($this->once())
            ->method('findLessonIdsByCohort')
            ->with($cohort)
            ->willReturn([1, 2]);

        $this->completionRepository->expects($this->once())
            ->method('countCompletionsForLessons')
            ->with([1, 2])
            ->willReturn([2 => 4]);

        $this->urlGenerator->method('generate')->willReturn('http://example.com/fomo/2');

        $fomoCalled = false;
        $this->notificationService->expects($this->any())
            ->method('addNotification')
            ->willReturnCallback(function($u, $title, $message, $link) use (&$fomoCalled) {
                if ($title === '🚀 Ne reste pas à la traîne !') {
                    $fomoCalled = true;
                    $this->assertEquals('http://example.com/fomo/2', $link);
                }
            });

        $this->triggerService->processDailyTriggers();

        $this->assertTrue($fomoCalled);
    }

    public function testProcessInactivityTriggerSendsNotification(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getTimezone')->willReturn('UTC');
        $user->method('getCohorts')->willReturn(new ArrayCollection([]));
        $user->method('getCourses')->willReturn(new ArrayCollection([]));

        // Connected exactly 3 days ago
        $lastConnection = new \DateTimeImmutable('2023-10-29 13:00:00', new \DateTimeZone('UTC'));
        $user->method('getLastConnectionAt')->willReturn($lastConnection);

        $this->clock->modify('2023-11-01 13:00:00');

        $this->userRepository->expects($this->once())
            ->method('findAll')
            ->willReturn([$user]);

        $this->urlGenerator->method('generate')->willReturn('http://example.com/app');

        $inactivityCalled = false;
        $this->notificationService->expects($this->any())
            ->method('addNotification')
            ->willReturnCallback(function($u, $title, $message, $link) use (&$inactivityCalled) {
                if ($title === '👋 Tu nous manques !') {
                    $inactivityCalled = true;
                    $this->assertEquals('http://example.com/app', $link);
                }
            });

        $this->triggerService->processDailyTriggers();

        $this->assertTrue($inactivityCalled);
    }

    public function testProcessMilestoneTriggerSendsNotification(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getTimezone')->willReturn('UTC');
        $user->method('getCohorts')->willReturn(new ArrayCollection([]));

        $module = $this->createMock(Module::class);
        $module->method('getSlug')->willReturn('module-1');
        $module->method('getId')->willReturn(1);

        $lesson1 = $this->createMock(Lesson::class);
        $lesson1->method('getId')->willReturn(1);
        $lesson1->method('getModule')->willReturn($module);

        $lesson2 = $this->createMock(Lesson::class);
        $lesson2->method('getId')->willReturn(2);
        $lesson2->method('getModule')->willReturn($module);

        $module->method('getLessons')->willReturn(new ArrayCollection([$lesson1, $lesson2]));

        $course = $this->createMock(Course::class);
        $course->method('getSlug')->willReturn('course-1');
        $course->method('getTitle')->willReturn('Advanced Symfony');
        $course->method('getModules')->willReturn(new ArrayCollection([$module]));

        $user->method('getCourses')->willReturn(new ArrayCollection([$course]));

        // 13:00 to avoid DailyDigest
        $this->clock->modify('2023-11-01 13:00:00');

        $this->userRepository->expects($this->once())
            ->method('findAll')
            ->willReturn([$user]);

        $this->completionRepository->method('findCompletedLessonIdsByUser')
            ->willReturn([1]); // lesson1 id is 1, so it is completed

        $this->urlGenerator->method('generate')->willReturn('http://example.com/course/1');

        $milestoneCalled = false;
        $this->notificationService->expects($this->any())
            ->method('addNotification')
            ->willReturnCallback(function($u, $title, $message, $link) use (&$milestoneCalled) {
                if ($title === '🎯 Presque arrivé !') {
                    $milestoneCalled = true;
                    $this->assertStringContainsString('Advanced Symfony', $message);
                    $this->assertEquals('http://example.com/course/1', $link);
                }
            });

        $this->triggerService->processDailyTriggers();

        $this->assertTrue($milestoneCalled);
    }

    public function testProcessHabitTriggerSendsNotification(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getTimezone')->willReturn('UTC');
        $user->method('getCohorts')->willReturn(new ArrayCollection([]));
        $user->method('getCourses')->willReturn(new ArrayCollection([]));

        // It checks if user usually studies at this hour. Let's make it 20:00.
        $this->clock->modify('2023-11-01 20:30:00');

        $this->userRepository->expects($this->once())
            ->method('findAll')
            ->willReturn([$user]);

        $completion1 = $this->createMock(Completion::class);
        $completion1->method('getCreatedAt')->willReturn(new \DateTimeImmutable('2023-10-25 20:15:00', new \DateTimeZone('UTC')));

        $completion2 = $this->createMock(Completion::class);
        $completion2->method('getCreatedAt')->willReturn(new \DateTimeImmutable('2023-10-26 20:45:00', new \DateTimeZone('UTC')));

        $completion3 = $this->createMock(Completion::class);
        $completion3->method('getCreatedAt')->willReturn(new \DateTimeImmutable('2023-10-27 20:10:00', new \DateTimeZone('UTC')));

        $completion4 = $this->createMock(Completion::class);
        $completion4->method('getCreatedAt')->willReturn(new \DateTimeImmutable('2023-10-28 20:20:00', new \DateTimeZone('UTC')));

        $completion5 = $this->createMock(Completion::class);
        $completion5->method('getCreatedAt')->willReturn(new \DateTimeImmutable('2023-10-29 20:05:00', new \DateTimeZone('UTC')));

        $this->completionRepository->method('findBy')
            ->willReturnMap([
                [['user' => $user], ['createdAt' => 'DESC'], 20, null, [$completion1, $completion2, $completion3, $completion4, $completion5]]
            ]);

        $this->urlGenerator->method('generate')->willReturn('http://example.com/app');

        $habitCalled = false;
        $this->notificationService->expects($this->any())
            ->method('addNotification')
            ->willReturnCallback(function($u, $title, $message, $link) use (&$habitCalled) {
                if ($title === "🧠 C'est ton heure habituelle !") {
                    $habitCalled = true;
                    $this->assertEquals('http://example.com/app', $link);
                }
            });

        $this->triggerService->processDailyTriggers();

        $this->assertTrue($habitCalled);
    }

    public function testProcessStreakTriggerSendsNotification(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getTimezone')->willReturn('UTC');
        $user->method('getCohorts')->willReturn(new ArrayCollection([]));
        $user->method('getCourses')->willReturn(new ArrayCollection([]));

        $user->method('getStreak')->willReturn(5);
        $user->method('getLastStreakDate')->willReturn(new \DateTimeImmutable('2023-10-31 15:00:00', new \DateTimeZone('UTC')));

        // Next day at 18:30 (streak triggers >= 18:00 if 1 day passed)
        $this->clock->modify('2023-11-01 18:30:00');

        $this->userRepository->expects($this->once())
            ->method('findAll')
            ->willReturn([$user]);

        $this->urlGenerator->method('generate')->willReturn('http://example.com/app');

        $streakCalled = false;
        $this->notificationService->expects($this->any())
            ->method('addNotification')
            ->willReturnCallback(function($u, $title, $message, $link) use (&$streakCalled) {
                if ($title === "🔥 Ne perds pas ton rythme !") {
                    $streakCalled = true;
                    $this->assertEquals('http://example.com/app', $link);
                }
            });

        $this->triggerService->processDailyTriggers();

        $this->assertTrue($streakCalled);
    }

    public function testProcessDailyDigestTriggerSendsNotification(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getTimezone')->willReturn('UTC');
        $user->method('getCohorts')->willReturn(new ArrayCollection([]));

        $module = $this->createMock(Module::class);
        $module->method('getId')->willReturn(1);

        $lesson = $this->createMock(Lesson::class);
        $lesson->method('getId')->willReturn(1);
        $lesson->method('getModule')->willReturn($module);

        $module->method('getLessons')->willReturn(new ArrayCollection([$lesson]));

        $course = $this->createMock(Course::class);
        $course->method('getModules')->willReturn(new ArrayCollection([$module]));

        $user->method('getCourses')->willReturn(new ArrayCollection([$course]));

        // Exactly 08:00 AM server time (or UTC in test)
        $this->clock->modify('2023-11-01 08:00:00');

        $this->userRepository->expects($this->once())
            ->method('findAll')
            ->willReturn([$user]);

        $this->completionRepository->method('findCompletedLessonIdsByUser')
            ->willReturn([]); // Lesson 1 is uncompleted

        $this->aiAgent->method('generateNextStepNudge')->willReturn('AI generated nudge message.');

        $this->urlGenerator->method('generate')->willReturn('http://example.com/lesson/1');

        $digestCalled = false;
        $this->notificationService->expects($this->any())
            ->method('addNotification')
            ->willReturnCallback(function($u, $title, $message, $link) use (&$digestCalled) {
                if ($title === "💡 Ton programme du jour") {
                    $digestCalled = true;
                    $this->assertEquals('AI generated nudge message.', $message);
                    $this->assertEquals('http://example.com/lesson/1', $link);
                }
            });

        $this->triggerService->processDailyTriggers();

        $this->assertTrue($digestCalled);
    }
}
