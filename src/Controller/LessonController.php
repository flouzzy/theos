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
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
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
        private TranslatorInterface $translator,
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


        // Dispatch lesson event to notify subscribers
        $lessonCompleteEvent = new LessonCompleteEvent($lesson, $completed);
        $this->dispatcher->dispatch($lessonCompleteEvent);

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

        if ($completed && !$wasCompleted) {
            $this->gamificationService->addXp($user, 10, 'lesson_completed');
        }

        $this->entityManager->persist($completion);
        $this->entityManager->flush();

        if ($completed == false) {
            // Show current lesson
            $this->addFlash('success', 'Lesson marked as unread');
            return $this->redirectToRoute('lesson_show', [
                'courseSlug' => $course->getSlug(),
                'moduleSlug' => $module->getSlug(),
                'id' => $lesson->getId()
            ]);
        } else {
            // Send a notification to all users
            $content = $this->renderView('notification/emails/lesson_completed.html.twig', [
                'user' => $user,
                'lesson' => $lesson
            ]);

            $this->completionService->sendNotificationToAllUsers(
                $content,
                $this->translator->trans('Lesson completed for') . ' ' . $user->getFirstname()
            );
        }

        // =====================
        // Go to next lesson
        // =====================

        // On récupère les leçons du module
        $lessons = $module->getLessons();

        // On trie les données par itemOrder
        /**
         * @var \ArrayIterator<int, \App\Entity\Lesson> $iterator
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
        /** @var \App\Entity\Lesson|null $nextLesson */
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


}
