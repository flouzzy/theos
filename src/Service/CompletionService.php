<?php

namespace App\Service;

use App\Entity\BadgeType;
use App\Entity\Completion;
use App\Entity\CourseCompletion;
use App\Entity\Module;
use App\Entity\ModuleCompletion;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Twig\Environment;

class CompletionService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private MessageBusInterface $bus,
        private TranslatorInterface $translator,
        private Security $security,
        private Environment $twig,
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
            // Render a content based on twig template
            $content = $this->twig->render('notification/emails/module_completed.html.twig', [
                'user' => $user,
                'module' => $module
            ]);

            $this->sendNotificationToAllUsers(
                $content,
                $this->translator->trans('Module completed for') . ' ' . $user->getFirstname()
            );
        }

        // Mise à jour du statut de completion
        $moduleCompletion->setCompleted($allLessonsCompleted);

        $this->entityManager->persist($moduleCompletion);
        $this->entityManager->flush();
    }

    /**
     * Mark course as completed
     */
    public function setCourseCompletion($course)
    {
        /**
         * @var \App\Entity\User $user
         */
        $user = $this->security->getUser();
        $courseCompletion = $this->entityManager->getRepository(CourseCompletion::class)->findOneBy([
            'user' => $user,
            'course' => $course
        ]);

        if (!$courseCompletion) {
            $courseCompletion = new CourseCompletion();
            $courseCompletion->setUser($user);
            $courseCompletion->setCourse($course);
        }

        // On vérifie que tous les modules sont completés (ou non)
        $allModulesCompleted = true;
        $modules = $course->getModules();
        foreach ($modules as $courseModule) {
            // On aurait pu simplement vérifié le statut de completion du module sans passer par les leçons
            // mais dans ce cas on risquerait de passer à côté des modules qui ne sont associés à aucune leçon (cygne noir)
            $moduleLessons = $courseModule->getLessons();
            foreach ($moduleLessons as $lesson) {
                $completion = $this->entityManager->getRepository(Completion::class)->findOneBy(['user' => $user, 'lesson' => $lesson]);

                if (!$completion || !$completion->isCompleted()) {
                    $allModulesCompleted = false;
                    break 2; // Sort des deux boucles si une leçon non complétée est trouvée
                }
            }
        }

        // Send notification to all the users if someone complete a course
        if ($allModulesCompleted) {
            // Render a content based on twig template
            $content = $this->twig->render('notification/emails/course_completed.html.twig', [
                'user' => $user,
                'course' => $course
            ]);

            $this->sendNotificationToAllUsers(
                $content,
                $this->translator->trans('Course completed for') . ' ' . $user->getFirstname()
            );

            // Award Badge
            $badgeTitle = 'Completed ' . $course->getTitle();
            $badgeRepository = $this->entityManager->getRepository(\App\Entity\Badge::class);
            $badge = $badgeRepository->findOneBy(['title' => $badgeTitle]);

            if (!$badge) {
                // Find or create BadgeType
                $badgeTypeRepository = $this->entityManager->getRepository(BadgeType::class);
                $badgeType = $badgeTypeRepository->findOneBy(['code' => BadgeType::CODE_COURSE_COMPLETION]);

                if (!$badgeType) {
                    $badgeType = new BadgeType();
                    $badgeType->setTitle('Course Completion');
                    $badgeType->setCode(BadgeType::CODE_COURSE_COMPLETION);
                    $badgeType->setDescription('Badge awarded for completing a course.');
                    $this->entityManager->persist($badgeType);
                }

                $badge = new \App\Entity\Badge();
                $badge->setTitle($badgeTitle);
                $badge->setDescription('Awarded for completing the course: ' . $course->getTitle());
                $badge->setBadgeType($badgeType);
                $this->entityManager->persist($badge);
            }

            // Use User's badge collection to avoid loading all users of a badge (performance optimization)
            if (!$user->getBadges()->contains($badge)) {
                $badge->addUser($user);
            }

            // Check for Early Bird Badge (completed within 7 days of starting)
            $createdAt = $courseCompletion->getCreatedAt() ?? new \DateTimeImmutable();
            $now = new \DateTimeImmutable();
            if ($now->diff($createdAt)->days < 7) {
                $earlyBirdTitle = 'Early Bird: ' . $course->getTitle();
                $badge = $badgeRepository->findOneBy(['title' => $earlyBirdTitle]);

                if (!$badge) {
                    $ebType = $this->entityManager->getRepository(BadgeType::class)->findOneBy(['code' => BadgeType::CODE_EARLY_BIRD]);
                    if (!$ebType) {
                        $ebType = new BadgeType();
                        $ebType->setCode(BadgeType::CODE_EARLY_BIRD);
                        $ebType->setTitle('Early Bird');
                        $ebType->setDescription('Completed a course within 7 days');
                        $this->entityManager->persist($ebType);
                    }

                    $badge = new \App\Entity\Badge();
                    $badge->setTitle($earlyBirdTitle);
                    $badge->setDescription('Completed ' . $course->getTitle() . ' within 7 days');
                    $badge->setBadgeType($ebType);
                    $this->entityManager->persist($badge);
                }

                if (!$user->getBadges()->contains($badge)) {
                    $badge->addUser($user);
                }
            }
        }

        // MAj du statut du parcours
        $courseCompletion->setCompleted($allModulesCompleted);

        $this->entityManager->persist($courseCompletion);
    }

    public function sendNotificationToAllUsers($content, $title): void
    {
        $this->bus->dispatch(new \App\Message\Notification($content, $title));
    }
}
