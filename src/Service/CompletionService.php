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
        foreach ($module->getLessons() as $moduleLesson) {
            $lessonCompletion = $this->entityManager->getRepository(Completion::class)->findOneBy([
                'user' => $user,
                'lesson' => $moduleLesson
            ]);

            if (!$lessonCompletion || !$lessonCompletion->isCompleted()) {
                $allLessonsCompleted = false;
                break;
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
