<?php

namespace App\Service;

use App\Entity\Completion;
use App\Entity\CourseCompletion;
use App\Entity\Module;
use App\Entity\ModuleCompletion;
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

    public function setModuleCompletion(Module $module): void
    {
        /**
         * @var \App\Entity\User $user
         */
        $user = $this->security->getUser();
        $allLessonsCompleted = true;
        $lessons = $module->getLessons();

        if ($lessons->count() > 0) {
            $completions = $this->entityManager->getRepository(Completion::class)->findBy([
                'user' => $user,
                'lesson' => $lessons->toArray()
            ]);

            $completionMap = [];
            foreach ($completions as $completion) {
                if ($completion->getLesson()) {
                    $completionMap[$completion->getLesson()->getId()] = $completion;
                }
            }

            foreach ($lessons as $moduleLesson) {
                $lessonId = $moduleLesson->getId();
                $lessonCompletion = $completionMap[$lessonId] ?? null;

                if (!$lessonCompletion || !$lessonCompletion->isCompleted()) {
                    $allLessonsCompleted = false;
                    break;
                }
            }
        }

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

    private function areAllCourseLessonsCompleted(\App\Entity\User $user, \App\Entity\Course $course): bool
    {
        $totalLessonsCount = (int) $this->entityManager->createQuery('
            SELECT COUNT(DISTINCT l.id)
            FROM App\Entity\Course c
            JOIN c.modules m
            JOIN m.lessons l
            WHERE c = :course
        ')
        ->setParameter('course', $course)
        ->getSingleScalarResult();

        if ($totalLessonsCount === 0) {
            return true;
        }

        $completedLessonsCount = (int) $this->entityManager->createQuery('
            SELECT COUNT(DISTINCT l.id)
            FROM App\Entity\Completion comp
            JOIN comp.lesson l
            JOIN l.module m
            JOIN m.courses c
            WHERE comp.user = :user
            AND comp.completed = true
            AND c = :course
        ')
        ->setParameter('user', $user)
        ->setParameter('course', $course)
        ->getSingleScalarResult();

        return $totalLessonsCount === $completedLessonsCount;
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
