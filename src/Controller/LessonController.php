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
#[IsGranted('IS_AUTHENTICATED', message: 'Vous devez être connecté pour voir cette leçon')]
class LessonController extends AbstractController
{
    public function __construct(
        private CompletionRepository $completionRepository,
        private EntityManagerInterface $entityManager,
        private EventDispatcherInterface $dispatcher,
        private CompletionService $completionService,
        private GamificationService $gamificationService,
        private TranslatorInterface $translator,
    ) {}
    #[Route('/{lessonId}', name: 'show')]
    public function show(
        #[MapEntity(mapping: ['lessonId' => 'id'])]
        Lesson $lesson,

        #[MapEntity(mapping: ['courseSlug' => 'slug'])]
        Course $course,

        #[MapEntity(mapping: ['moduleSlug' => 'slug'])]
        Module $module

    ): Response {

        // On récupère les leçons déjà complétées par l'utilisateur pour les identifier comme telles depuis le front
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        if (!$course->isUserSubscribed($user)) {
            $user->subscribeToCourse($course);
            $this->entityManager->flush();
            $this->dispatcher->dispatch(new \App\Event\CourseSubscribedEvent($course, $user));
        }

        $completedLessonIdsByCurrentUser = $this->completionRepository->findCompletedLessonIdsByCourse($user, $course);

        return $this->render('lesson/show.html.twig', [
            'course' => $course,
            'module' => $module,
            'currentLesson' => $lesson,
            'isSubscribed' => true,
            'completedLessonIds' => $completedLessonIdsByCurrentUser,
        ]);
    }

    /**
     * Mark lesson as completed or uncompleted depending on $completed value
     */
    #[Route('/{lessonId}/complete/{completed}', name: 'complete')]
    public function markLessonAsCompleted(
        #[MapEntity(mapping: ['lessonId' => 'id'])]
        Lesson $lesson,

        #[MapEntity(mapping: ['courseSlug' => 'slug'])]
        Course $course,

        #[MapEntity(mapping: ['moduleSlug' => 'slug'])]
        Module $module,
        mixed $completed = 0
    ): Response {

        /**
         * @var \App\Entity\User $user
         */
        $user = $this->getUser();

        $this->ensureUserIsSubscribed($course, $user);

        // Parse completion status
        $completed = boolval($completed);

        // Save lesson completion status for current user
        $wasCompleted = $this->updateCompletionStatus($user, $lesson, $course, $module, $completed);

        // Dispatch lesson event to notify subscribers
        $lessonCompleteEvent = new LessonCompleteEvent($lesson, $user, $completed, $wasCompleted);
        $this->dispatcher->dispatch($lessonCompleteEvent);

        if ($completed == false) {
            // Afficher la leçon actuelle
            $this->addFlash('success', $this->translator->trans('Leçon marquée comme non lue'));
            return $this->redirectToRoute('lesson_show', [
                'courseSlug' => $course->getSlug(),
                'moduleSlug' => $module->getSlug(),
                'lessonId' => $lesson->getId()
            ]);
        }

        $nextLesson = $this->getNextLesson($module, $lesson);

        if ($nextLesson && $nextLesson->getId() !== $lesson->getId()) {
            // Aller à la leçon suivante
            $this->addFlash('success', $this->translator->trans('Leçon terminée'));
            return $this->redirectToRoute('lesson_show', [
                'courseSlug' => $course->getSlug(),
                'moduleSlug' => $module->getSlug(),
                'lessonId' => $nextLesson->getId()
            ]);
        } else {
            // C'était la dernière leçon du module ou soucis de résolution
            $this->addFlash('success', $this->translator->trans('Bravo ! Module terminé'));

            // Retour à la page du cours
            return $this->redirectToRoute('course_show', [
                'slug' => $course->getSlug()
            ]);
        }
    }

    private function ensureUserIsSubscribed(Course $course, \App\Entity\User $user): void
    {
        if (!$course->isUserSubscribed($user)) {
            // Si on est pas inscrit au cours, on le devient automatiquement
            $user->subscribeToCourse($course);
        }
    }

    private function updateCompletionStatus(\App\Entity\User $user, Lesson $lesson, Course $course, Module $module, bool $completed): bool
    {
        $completion = $this->completionRepository->findOneBy(['user' => $user, 'lesson' => $lesson]);
        $wasCompleted = $completion && $completion->isCompleted();

        if (!$completion) {
            $completion = new Completion();
        }
        $completion->setUser($user);
        $completion->setLesson($lesson);
        $completion->setCompleted($completed);

        // Maj du statut de completion d'un module
        $this->completionService->setModuleCompletion($module);

        // Maj du statut de completion d'un parcours
        $this->completionService->setCourseCompletion($course);

        $this->entityManager->persist($completion);
        $this->entityManager->flush();

        return $wasCompleted;
    }

    private function getNextLesson(Module $module, Lesson $lesson): ?Lesson
    {
        // On récupère les leçons du module
        $lessons = $module->getLessons();

        // On trie les données par itemOrder
        /**
         * @var \ArrayIterator<int, Lesson> $iterator
         */
        $iterator = $lessons->getIterator();
        $iterator->uasort(function ($first, $second) {
            return (int) $first->getItemOrder() > (int) $second->getItemOrder() ? 1 : -1;
        });

        // On transforme les données en ArrayCollection en mettant à jour les index (grâce à array_values)
        $sortedArray = array_values(iterator_to_array($iterator));
        $sortedLessons = new ArrayCollection($sortedArray);

        // On récupère l'index courant
        $currentIndex = $sortedLessons->indexOf($lesson);

        if ($currentIndex !== false) {
            // Puis la leçon suivante
            return $sortedLessons->get((int) $currentIndex + 1);
        }

        return null;
    }

}
