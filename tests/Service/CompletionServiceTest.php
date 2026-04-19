<?php

namespace App\Tests\Service;

use App\Entity\Badge;
use App\Entity\BadgeType;
use App\Entity\Completion;
use App\Entity\Course;
use App\Entity\CourseCompletion;
use App\Entity\Lesson;
use App\Entity\Module;
use App\Entity\ModuleCompletion;
use App\Entity\User;
use App\Service\CompletionService;
use App\Service\GamificationService;
use App\Service\LootBoxService;
use App\Service\NotificationService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

class CompletionServiceTest extends TestCase
{
    /** @var EntityManagerInterface&MockObject */
    private EntityManagerInterface $entityManager;
    /** @var MessageBusInterface&MockObject */
    private MessageBusInterface $bus;
    /** @var TranslatorInterface&MockObject */
    private TranslatorInterface $translator;
    /** @var Security&MockObject */
    private Security $security;
    /** @var Environment&MockObject */
    private Environment $twig;
    /** @var GamificationService&MockObject */
    private GamificationService $gamificationService;
    /** @var NotificationService&MockObject */
    private NotificationService $notificationService;
    /** @var EventDispatcherInterface&MockObject */
    private EventDispatcherInterface $eventDispatcher;
    /** @var UrlGeneratorInterface|MockObject */
    private UrlGeneratorInterface $urlGenerator;
    /** @var LootBoxService&MockObject */
    private LootBoxService $lootBoxService;

    private CompletionService $completionService;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->bus = $this->createMock(MessageBusInterface::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->security = $this->createMock(Security::class);
        $this->twig = $this->createMock(Environment::class);
        $this->gamificationService = $this->createMock(GamificationService::class);
        $this->notificationService = $this->createMock(NotificationService::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $this->lootBoxService = $this->createMock(LootBoxService::class);

        $this->completionService = new CompletionService(
            $this->entityManager,
            $this->bus,
            $this->translator,
            $this->security,
            $this->twig,
            $this->gamificationService,
            $this->notificationService,
            $this->eventDispatcher,
            $this->urlGenerator,
            $this->lootBoxService
        );
    }

    public function testSetModuleCompletionIncomplete(): void
    {
        $user = $this->createMock(User::class);
        $this->security->method('getUser')->willReturn($user);

        $module = $this->createMock(Module::class);
        $lesson = $this->createMock(Lesson::class);
        $module->method('getLessons')->willReturn(new ArrayCollection([$lesson]));

        $completionRepo = $this->createMock(\App\Repository\CompletionRepository::class);
        $moduleCompletionRepo = $this->createMock(EntityRepository::class);

        $this->entityManager->method('getRepository')
            ->will($this->returnValueMap([
                [Completion::class, $completionRepo],
                [ModuleCompletion::class, $moduleCompletionRepo],
            ]));

        // Completion not found or not completed
        $completionRepo->expects($this->once())
            ->method('countCompletedLessonsForModule')
            ->with($user, $module)
            ->willReturn(0);

        // ModuleCompletion setup
        $moduleCompletion = new ModuleCompletion();
        $moduleCompletionRepo->expects($this->once())
            ->method('findOneBy')
            ->with(['user' => $user, 'module' => $module])
            ->willReturn($moduleCompletion);

        $this->entityManager->expects($this->once())->method('persist')->with($moduleCompletion);
        $this->entityManager->expects($this->once())->method('flush');

        // No notification should be sent
        $this->bus->expects($this->never())->method('dispatch');
        $this->twig->expects($this->never())->method('render');

        $this->completionService->setModuleCompletion($module);

        $this->assertFalse($moduleCompletion->isCompleted());
    }

    public function testSetModuleCompletionComplete(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getFirstname')->willReturn('John');
        $this->security->method('getUser')->willReturn($user);

        $module = $this->createMock(Module::class);
        $lesson = $this->createMock(Lesson::class);
        $lesson->method('getId')->willReturn(1);
        $module->method('getLessons')->willReturn(new ArrayCollection([$lesson]));

        $completionRepo = $this->createMock(\App\Repository\CompletionRepository::class);
        $completionRepo->method('countCompletedLessonsForModule')
            ->with($user, $module)
            ->willReturn(1);

        $moduleCompletionRepo = $this->createMock(EntityRepository::class);
        $moduleCompletion = new ModuleCompletion();
        $moduleCompletionRepo->method('findOneBy')
            ->with(['user' => $user, 'module' => $module])
            ->willReturn($moduleCompletion);

        $this->entityManager->method('getRepository')
            ->will($this->returnValueMap([
                [Completion::class, $completionRepo],
                [ModuleCompletion::class, $moduleCompletionRepo],
            ]));

        // ModuleCompletion setup
        $this->entityManager->expects($this->once())->method('persist')->with($moduleCompletion);
        $this->entityManager->expects($this->once())->method('flush');

        // Notification should be sent
        $this->twig->expects($this->once())
            ->method('render')
            ->with('notification/emails/module_completed.html.twig', [
                'user' => $user,
                'module' => $module
            ])
            ->willReturn('content');

        $this->translator->expects($this->once())
            ->method('trans')
            ->with('Module completed for')
            ->willReturn('Module completed for');

        $this->bus->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function ($message) {
                return $message instanceof \App\Message\Notification
                    && $message->getContent() === 'content'
                    && str_contains($message->getTitle(), 'Module completed for')
                    && str_contains($message->getTitle(), 'John');
            }))
            ->willReturn(new \Symfony\Component\Messenger\Envelope(new \stdClass()));

        $this->completionService->setModuleCompletion($module);

        $this->assertTrue($moduleCompletion->isCompleted());
    }

    public function testSetCourseCompletionIncomplete(): void
    {
        $user = $this->createMock(User::class);
        $this->security->method('getUser')->willReturn($user);

        $course = $this->createMock(Course::class);
        $module = $this->createMock(Module::class);
        $lesson = $this->createMock(Lesson::class);

        $course->method('getModules')->willReturn(new ArrayCollection([$module]));
        $module->method('getLessons')->willReturn(new ArrayCollection([$lesson]));

        $completionRepo = $this->createMock(EntityRepository::class);
        $courseCompletionRepo = $this->createMock(EntityRepository::class);

        // Completion incomplete
        $completionRepo->expects($this->once())
            ->method('findBy')
            ->with(['user' => $user, 'lesson' => [$lesson]])
            ->willReturn([]);

        $courseCompletion = new CourseCompletion();
        $courseCompletionRepo->expects($this->once())
            ->method('findOneBy')
            ->with(['user' => $user, 'course' => $course])
            ->willReturn($courseCompletion);

        $this->entityManager->method('getRepository')
            ->will($this->returnValueMap([
                [CourseCompletion::class, $courseCompletionRepo],
                [Completion::class, $completionRepo],
            ]));

        $this->entityManager->expects($this->once())->method('persist')->with($courseCompletion);
        // Note: setCourseCompletion does not call flush

        // No notification
        $this->bus->expects($this->never())->method('dispatch');
        $this->twig->expects($this->never())->method('render');

        $this->completionService->setCourseCompletion($course);

        $this->assertFalse($courseCompletion->isCompleted());
    }

    public function testSetCourseCompletionComplete(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getFirstname')->willReturn('John');
        $this->security->method('getUser')->willReturn($user);

        $course = $this->createMock(Course::class);
        $module = $this->createMock(Module::class);
        $lesson = $this->createMock(Lesson::class);
        $lesson->method('getId')->willReturn(1);

        $course->method('getModules')->willReturn(new ArrayCollection([$module]));
        $module->method('getLessons')->willReturn(new ArrayCollection([$lesson]));

        $completion = $this->createMock(Completion::class);
        $completion->method('isCompleted')->willReturn(true);
        $completion->method('getLesson')->willReturn($lesson);

        $user->method('getBadges')->willReturn(new ArrayCollection());

        $completionRepo = $this->createMock(EntityRepository::class);
        $completionRepo->method('findBy')
            ->with(['user' => $user, 'lesson' => [$lesson]])
            ->willReturn([$completion]);

        $courseCompletionRepo = $this->createMock(EntityRepository::class);
        $courseCompletion = new CourseCompletion();
        $courseCompletionRepo->method('findOneBy')
            ->with(['user' => $user, 'course' => $course])
            ->willReturn($courseCompletion);

        $this->entityManager->method('getRepository')
            ->will($this->returnValueMap([
                [CourseCompletion::class, $courseCompletionRepo],
                [Completion::class, $completionRepo],
            ]));

        $this->entityManager->expects($this->once())->method('persist')->with($courseCompletion);

        // Notification should be sent
        $this->twig->expects($this->once())
            ->method('render')
            ->with('notification/emails/course_completed.html.twig', [
                'user' => $user,
                'course' => $course
            ])
            ->willReturn('content');

        $this->translator->expects($this->once())
            ->method('trans')
            ->with('Course completed for')
            ->willReturn('Course completed for');

        $this->bus->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function ($message) {
                return $message instanceof \App\Message\Notification
                    && $message->getContent() === 'content'
                    && str_contains($message->getTitle(), 'Course completed for')
                    && str_contains($message->getTitle(), 'John');
            }))
            ->willReturn(new \Symfony\Component\Messenger\Envelope(new \stdClass()));

        // Expect calls to GamificationService with flush=false
        $this->gamificationService->expects($this->once())
            ->method('awardCourseCompletionBadge')
            ->with($user, $course, false);

        $this->gamificationService->expects($this->once())
            ->method('awardEarlyBirdBadge')
            ->with($user, $course, $this->isInstanceOf(\DateTimeImmutable::class), false);
            
        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(\App\Event\TrainingCompletionEvent::class));

        $this->completionService->setCourseCompletion($course);

        $this->assertTrue($courseCompletion->isCompleted());
    }
}
