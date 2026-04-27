<?php

namespace App\Service;

use App\Entity\Completion;
use App\Entity\CourseCompletion;
use App\Entity\Module;
use App\Entity\Course;
use App\Entity\Lesson;
use App\Entity\ModuleCompletion;
use App\Event\LessonCompleteEvent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Twig\Environment;
use App\Service\GamificationService;
use App\Service\NotificationService;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use App\Event\TrainingCompletionEvent;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class CompletionService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private MessageBusInterface $bus,
        private TranslatorInterface $translator,
        private Security $security,
        private Environment $twig,
        private GamificationService $gamificationService,
        private NotificationService $notificationService,
        private EventDispatcherInterface $eventDispatcher,
        private UrlGeneratorInterface $urlGenerator,
        private LootBoxService $lootBoxService,
    ) {}

    public function completeLesson(\App\Entity\User $user, Lesson $lesson, Course $course, Module $module, bool $completed): bool
    {
        $completion = $this->entityManager->getRepository(Completion::class)->findOneBy([
            'user' => $user,
            'lesson' => $lesson
        ]);

        $wasCompleted = $completion && $completion->isCompleted();

        if (!$completion) {
            $completion = new Completion();
        }

        $completion->setUser($user);
        $completion->setLesson($lesson);
        $completion->setCompleted($completed);

        // Maj du statut de completion d'un module
        $this->setModuleCompletion($module);

        // Maj du statut de completion d'un parcours
        $this->setCourseCompletion($course);

        $this->entityManager->persist($completion);
        $this->entityManager->flush();

        // Dispatch lesson event to notify subscribers
        $lessonCompleteEvent = new LessonCompleteEvent($lesson, $user, $completed, $wasCompleted);
        $this->eventDispatcher->dispatch($lessonCompleteEvent);

        return $wasCompleted;
    }

    public function setModuleCompletion(Module $module): void
    {
        /**
         * @var \App\Entity\User $user
         */
        $user = $this->security->getUser();
        $allLessonsCompleted = $this->areAllModuleLessonsCompleted($user, $module);

        $moduleCompletion = $this->entityManager->getRepository(ModuleCompletion::class)->findOneBy([
            'user' => $user,
            'module' => $module
        ]);

        if (!$moduleCompletion) {
            $moduleCompletion = new ModuleCompletion();
            $moduleCompletion->setUser($user);
            $moduleCompletion->setModule($module);
        }

        // Send notification to all the users if someone complete a course
        if ($allLessonsCompleted) {
            $content = $this->twig->render('notification/emails/module_completed.html.twig', [
                'user' => $user,
                'module' => $module
            ]);

            $this->sendNotificationToAllUsers(
                $content,
                $this->translator->trans('Module completed for') . ' ' . $user->getFirstname()
            );

            // Trigger LootBox Surprise ONLY on first completion
            if (!$moduleCompletion->isCompleted() && $user instanceof \App\Entity\User) {
                $this->lootBoxService->unlockRandomBonus($user);
            }
        }

        // Mise à jour du statut de completion
        $moduleCompletion->setCompleted($allLessonsCompleted);

        $this->entityManager->persist($moduleCompletion);
        $this->entityManager->flush();
    }

    /**
     * Mark course as completed
     */
    public function setCourseCompletion(\App\Entity\Course $course): void
    {
        /**
         * @var \App\Entity\User $user
         */
        $user = $this->security->getUser();

        $courseCompletion = $this->getOrCreateCourseCompletion($user, $course);
        $allModulesCompleted = $this->areAllCourseLessonsCompleted($user, $course);

        // Send notification to all the users if someone complete a course
        if ($allModulesCompleted) {
            $this->handleCourseCompletionRewards($user, $course, $courseCompletion);
        }

        // MAj du statut du parcours
        $courseCompletion->setCompleted($allModulesCompleted);

        $this->entityManager->persist($courseCompletion);
    }

    public function sendNotificationToAllUsers(string $content, string $title): void
    {
        $this->bus->dispatch(new \App\Message\Notification($content, $title));
    }

    private function getOrCreateCourseCompletion(\App\Entity\User $user, \App\Entity\Course $course): CourseCompletion
    {
        $courseCompletion = $this->entityManager->getRepository(CourseCompletion::class)->findOneBy([
            'user' => $user,
            'course' => $course
        ]);

        if (!$courseCompletion) {
            $courseCompletion = new CourseCompletion();
            $courseCompletion->setUser($user);
            $courseCompletion->setCourse($course);
        }

        return $courseCompletion;
    }

    private function areAllModuleLessonsCompleted(\App\Entity\User $user, Module $module): bool
    {
        $lessonsCount = $module->getLessons()->count();

        if ($lessonsCount === 0) {
            return true;
        }

        $completedCount = $this->entityManager->getRepository(Completion::class)
            ->countCompletedLessonsForModule($user, $module);

        return $completedCount === $lessonsCount;
    }

    private function areAllCourseLessonsCompleted(\App\Entity\User $user, \App\Entity\Course $course): bool
    {
        $lessonsCount = $this->entityManager->getRepository(Lesson::class)
            ->countForCourse($course);

        if ($lessonsCount === 0) {
            return true;
        }

        $completedCount = $this->entityManager->getRepository(Completion::class)
            ->countCompletedLessonsForCourse($user, $course);

        return $completedCount === $lessonsCount;
    }

    private function handleCourseCompletionRewards(\App\Entity\User $user, \App\Entity\Course $course, CourseCompletion $courseCompletion): void
    {
        // Render a content based on twig template
        $content = $this->twig->render('notification/emails/course_completed.html.twig', [
            'user' => $user,
            'course' => $course
        ]);

        $this->sendNotificationToAllUsers(
            $content,
            $this->translator->trans('Course completed for') . ' ' . $user->getFirstname()
        );

        // Award Badges via GamificationService
        // Flush is false to allow atomic transaction commit by caller (or later flush)
        $this->gamificationService->awardCourseCompletionBadge($user, $course, false);
        $this->gamificationService->awardEarlyBirdBadge(
            $user,
            $course,
            $courseCompletion->getCreatedAt() ?? new \DateTimeImmutable(),
            false
        );

        // Personal Notification
        $this->notificationService->addNotification(
            $user,
            "🎓 Félicitations !",
            sprintf("Tu as terminé le cours '%s'. Ton certificat est prêt !", $course->getTitle()),
            $this->urlGenerator->generate('certificate_show', ['id' => $course->getId()], UrlGeneratorInterface::ABSOLUTE_URL)
        );

        // Dispatch TrainingCompletionEvent
        $this->eventDispatcher->dispatch(new TrainingCompletionEvent($user, $course));
    }
}
