<?php

namespace App\Tests\Service;

use App\Entity\Completion;
use App\Entity\Course;
use App\Entity\CourseCompletion;
use App\Entity\Lesson;
use App\Entity\Module;
use App\Entity\ModuleCompletion;
use App\Entity\User;
use App\Service\CompletionService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class CompletionServiceTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private MessageBusInterface $bus;
    private TranslatorInterface $translator;
    private Security $security;
    private Environment $twig;
    private CompletionService $completionService;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->bus = $this->createMock(MessageBusInterface::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->security = $this->createMock(Security::class);
        $this->twig = $this->createMock(Environment::class);

        $this->completionService = new CompletionService(
            $this->entityManager,
            $this->bus,
            $this->translator,
            $this->security,
            $this->twig
        );
    }

    public function testSetModuleCompletionIncomplete(): void
    {
        $user = $this->createMock(User::class);
        $this->security->method('getUser')->willReturn($user);

        $module = $this->createMock(Module::class);
        $lesson = $this->createMock(Lesson::class);
        $module->method('getLessons')->willReturn(new ArrayCollection([$lesson]));

        $completionRepo = $this->createMock(EntityRepository::class);
        $moduleCompletionRepo = $this->createMock(EntityRepository::class);

        $this->entityManager->method('getRepository')
            ->willReturnMap([
                [Completion::class, $completionRepo],
                [ModuleCompletion::class, $moduleCompletionRepo],
            ]);

        // Completion not found or not completed
        $completionRepo->expects($this->once())
            ->method('findOneBy')
            ->with(['user' => $user, 'lesson' => $lesson])
            ->willReturn(null);

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
        $module->method('getLessons')->willReturn(new ArrayCollection([$lesson]));

        $completion = $this->createMock(Completion::class);
        $completion->method('isCompleted')->willReturn(true);

        $completionRepo = $this->createMock(EntityRepository::class);
        $completionRepo->method('findOneBy')
            ->with(['user' => $user, 'lesson' => $lesson])
            ->willReturn($completion);

        $moduleCompletionRepo = $this->createMock(EntityRepository::class);
        $moduleCompletion = new ModuleCompletion();
        $moduleCompletionRepo->method('findOneBy')
            ->with(['user' => $user, 'module' => $module])
            ->willReturn($moduleCompletion);

        $this->entityManager->method('getRepository')
            ->willReturnMap([
                [Completion::class, $completionRepo],
                [ModuleCompletion::class, $moduleCompletionRepo],
            ]);

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
            ->method('findOneBy')
            ->with(['user' => $user, 'lesson' => $lesson])
            ->willReturn(null);

        $courseCompletion = new CourseCompletion();
        $courseCompletionRepo->expects($this->once())
            ->method('findOneBy')
            ->with(['user' => $user, 'course' => $course])
            ->willReturn($courseCompletion);

        $this->entityManager->method('getRepository')
            ->willReturnMap([
                [CourseCompletion::class, $courseCompletionRepo],
                [Completion::class, $completionRepo],
            ]);

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

        $course->method('getModules')->willReturn(new ArrayCollection([$module]));
        $module->method('getLessons')->willReturn(new ArrayCollection([$lesson]));

        $completion = $this->createMock(Completion::class);
        $completion->method('isCompleted')->willReturn(true);

        $completionRepo = $this->createMock(EntityRepository::class);
        $completionRepo->method('findOneBy')
            ->with(['user' => $user, 'lesson' => $lesson])
            ->willReturn($completion);

        $courseCompletionRepo = $this->createMock(EntityRepository::class);
        $courseCompletion = new CourseCompletion();
        $courseCompletionRepo->method('findOneBy')
            ->with(['user' => $user, 'course' => $course])
            ->willReturn($courseCompletion);

        $this->entityManager->method('getRepository')
            ->willReturnMap([
                [CourseCompletion::class, $courseCompletionRepo],
                [Completion::class, $completionRepo],
            ]);

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

        $this->completionService->setCourseCompletion($course);

        $this->assertTrue($courseCompletion->isCompleted());
    }
}
