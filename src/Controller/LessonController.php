<?php

namespace App\Controller;

use App\Entity\Completion;
use App\Entity\Course;
use App\Entity\Lesson;
use App\Entity\Module;
use App\Event\LessonCompleteEvent;
use App\Repository\CompletionRepository;
use App\Service\CompletionService;
use App\Service\GamificationService;
use App\Service\NotificationService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/courses/{courseSlug}/{moduleSlug}/lesson', name: 'lesson_')]
#[IsGranted('IS_AUTHENTICATED', message: 'You must be logged in to view this lesson')]
class LessonController extends AbstractController
{
    public function __construct(
        private CompletionRepository $completionRepository,
        private EntityManagerInterface $entityManager,
        private NotificationService $notificationService,
        private TranslatorInterface $translator,
        private MessageBusInterface $bus,
        private EventDispatcherInterface $dispatcher,
        private CompletionService $completionService,
        private GamificationService $gamificationService,
    ) {}
    #[Route('/{id}', name: 'show')]
    public function show(
        Lesson $lesson,

        #[MapEntity(mapping: ['courseSlug' => 'slug'])]
        Course $course,

        #[MapEntity(mapping: ['moduleSlug' => 'slug'])]
        Module $module

    ): Response {

        // On récupère les leçons déjà complétées par l'utilisateur pour les identifier comme telles depuis le front
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $completedLessonIdsByCurrentUser = $this->completionRepository->findCompletedLessonIdsByCourse($user, $course);

        return $this->render('lesson/show.html.twig', [
            'course' => $course,
            'module' => $module,
            'currentLesson' => $lesson,
            'isSubscribed' => $this->getUser() ? $course->isUserSubscribed($this->getUser()) : false,
            'completedLessonIds' => $completedLessonIdsByCurrentUser,
        ]);
    }

    /**
     * Mark lesson as completed or uncompleted depending on $completed value
     */
    #[Route('/{id}/complete/{completed}', name: 'complete')]
    public function markLessonAsCompleted(
        Lesson $lesson,

        #[MapEntity(mapping: ['courseSlug' => 'slug'])]
        Course $course,

        #[MapEntity(mapping: ['moduleSlug' => 'slug'])]
        Module $module,
        $completed = 0
    ): Response {

        /**
         * @var \App\Entity\User $user
         */
        $user = $this->getUser();

        if (!$course->isUserSubscribed($user)) {
            // Si on est pas inscrit au cours, on le devient automatiquement
            $user->subscribeToCourse($course);
        }

        // Toogle completion status
        $completed = !(boolval($completed));


        // Save lesson completion status for current user
        // Check if completion already exist
        // If not, create it
        $completion = $this->completionRepository->findOneBy(['user' => $user, 'lesson' => $lesson]);
        $wasCompleted = $completion && $completion->isCompleted();

        if (!$completion) {
            $completion = new Completion();
        }
        $completion->setUser($this->getUser());
        $completion->setLesson($lesson);
        $completion->setCompleted($completed);

        // Maj du statut de completion d'un module
        $this->completionService->setModuleCompletion($module);

        // Maj du statut de completion d'un parcours
        $this->completionService->setCourseCompletion($course);

        $this->entityManager->persist($completion);
        $this->entityManager->flush();

        // Dispatch lesson event to notify subscribers
        $lessonCompleteEvent = new LessonCompleteEvent($lesson, $user, $completed, $wasCompleted);
        $this->dispatcher->dispatch($lessonCompleteEvent);

        if ($completed == false) {
            // Show current lesson
            $this->addFlash('success', 'Lesson marked as unread');
            return $this->redirectToRoute('lesson_show', [
                'courseSlug' => $course->getSlug(),
                'moduleSlug' => $module->getSlug(),
                'id' => $lesson->getId()
            ]);
        }

        /**
         * @var \App\Entity\Lesson $nextLesson 
         */

        // =====================
        // Go to next lesson
        // =====================

        // On récupère les leçons du module
        $lessons = $module->getLessons();

        // On triee les données par itemOrder
        /**
         * @var Traversable|array $iterator
         */
        $iterator = $lessons->getIterator();
        $iterator->uasort(function ($first, $second) {
            return (int) $first->getItemOrder() > (int) $second->getItemOrder() ? 1 : -1;
        });

        // On transforme les données en ArrayCollection en mettant à jour les index (grâce à array_values)
        $sortedArray = array_values(iterator_to_array($iterator));
        $sortedLessons = new ArrayCollection($sortedArray);

        // On récupère l'index suivant
        $nextIndex = $sortedLessons->indexOf($lesson) + 1;

        // Puis la leçon suivante
        $nextLesson  = $sortedLessons->get($nextIndex);

        // Il existe une leçon suivante
        if ($nextLesson) {
            // Go to next lesson
            $this->addFlash('success', 'Lesson completed');
            return $this->redirectToRoute('lesson_show', [
                'courseSlug' => $course->getSlug(),
                'moduleSlug' => $module->getSlug(),
                'id' => $nextLesson->getId()
            ]);
        } else {
            // C'était la dernière leçon du module
            $this->addFlash('success', 'Well done! Keep up the good work');

            // Go to next module
            return $this->redirectToRoute('course_show', [
                'slug' => $course->getSlug()
            ]);
        }
    }

    #[Route('/{id}/comment', name: 'add_comment', methods: ['POST'])]
    public function addComment(
        Request $request,
        Lesson $lesson,
        #[MapEntity(mapping: ['courseSlug' => 'slug'])]
        Course $course,
        #[MapEntity(mapping: ['moduleSlug' => 'slug'])]
        Module $module
    ): Response {
        $content = $request->request->get('content');
        
        if ($content) {
            $comment = new \App\Entity\Comment();
            $comment->setContent($content);
            $comment->setLesson($lesson);
            $comment->setUser($this->getUser());
            
            $this->entityManager->persist($comment);
            $this->entityManager->flush();

            $this->gamificationService->addXp($this->getUser(), 5, 'comment_posted');
            
            $this->addFlash('success', 'Commentaire publié !');
        }

        return $this->redirectToRoute('lesson_show', [
            'courseSlug' => $course->getSlug(),
            'moduleSlug' => $module->getSlug(),
            'id' => $lesson->getId()
        ]);
    }


    /**
     * Mark module as completed
     *
     * @param Module $module
     * @param EntityManagerInterface $entityManager
     * @return void
     */
    // private function setModuleCompletion(Module $module): void
    // {
    //     /**
    //      * @var \App\Entity\User $user
    //      */
    //     $user = $this->getUser();
    //     $allLessonsCompleted = true;
    //     foreach ($module->getLessons() as $moduleLesson) {
    //         $lessonCompletion = $this->completionRepository->findOneBy([
    //             'user' => $user,
    //             'lesson' => $moduleLesson
    //         ]);

    //         if (!$lessonCompletion || !$lessonCompletion->isCompleted()) {
    //             $allLessonsCompleted = false;
    //             break;
    //         }
    //     }

    //     $moduleCompletion = $this->entityManager->getRepository(ModuleCompletion::class)->findOneBy([
    //         'user' => $user,
    //         'module' => $module
    //     ]);

    //     if (!$moduleCompletion) {
    //         $moduleCompletion = new ModuleCompletion();
    //         $moduleCompletion->setUser($user);
    //         $moduleCompletion->setModule($module);
    //     }

    //     // Send notification to all the users if someone complete a course
    //     if ($allLessonsCompleted) {
    //         // Render a content based on twig template
    //         $content = $this->renderView('notification/emails/module_completed.html.twig', [
    //             'user' => $user,
    //             'module' => $module
    //         ]);

    //         $this->sendNotificationToAllUsers(
    //             $content,
    //             $this->translator->trans('Module completed for') . ' ' . $user->getFirstname()
    //         );
    //     }

    //     // Mise à jour du statut de completion
    //     $moduleCompletion->setCompleted($allLessonsCompleted);

    //     $this->entityManager->persist($moduleCompletion);
    //     $this->entityManager->flush();
    // }

    /**
     * Mark course as completed
     */
    // private function setCourseCompletion($course)
    // {
    //     /**
    //      * @var \App\Entity\User $user
    //      */
    //     $user = $this->getUser();
    //     $courseCompletion = $this->entityManager->getRepository(CourseCompletion::class)->findOneBy([
    //         'user' => $user,
    //         'course' => $course
    //     ]);

    //     if (!$courseCompletion) {
    //         $courseCompletion = new CourseCompletion();
    //         $courseCompletion->setUser($user);
    //         $courseCompletion->setCourse($course);
    //     }

    //     // On vérifie que tous les modules sont completés (ou non)
    //     $allModulesCompleted = true;
    //     $modules = $course->getModules();
    //     foreach ($modules as $courseModule) {
    //         // On aurait pu simplement vérifié le statut de completion du module sans passer par les leçons
    //         // mais dans ce cas on risquerait de passer à côté des modules qui ne sont associés à aucune leçon (cygne noir)
    //         $moduleLessons = $courseModule->getLessons();
    //         foreach ($moduleLessons as $lesson) {
    //             $completion = $this->completionRepository->findOneBy(['user' => $user, 'lesson' => $lesson]);

    //             if (!$completion || !$completion->isCompleted()) {
    //                 $allModulesCompleted = false;
    //                 break 2; // Sort des deux boucles si une leçon non complétée est trouvée
    //             }
    //         }
    //     }

    //     // Send notification to all the users if someone complete a course
    //     if ($allModulesCompleted) {
    //         // Render a content based on twig template
    //         $content = $this->renderView('notification/emails/course_completed.html.twig', [
    //             'user' => $user,
    //             'course' => $course
    //         ]);

    //         $this->sendNotificationToAllUsers(
    //             $content,
    //             $this->translator->trans('Course completed for') . ' ' . $user->getFirstname()
    //         );
    //     }

    //     // MAj du statut du parcours
    //     $courseCompletion->setCompleted($allModulesCompleted);

    //     $this->entityManager->persist($courseCompletion);
    // }

    /**
     * Send notification to all users, except the current one
     *
     * @param string $content
     * @param string $title
     * @return void
     */
    // private function sendNotificationToAllUsers($content, $title): void
    // {
    //     $this->bus->dispatch(new \App\Message\Notification($content, $title));
    // }
}
