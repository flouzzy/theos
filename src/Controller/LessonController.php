<?php

namespace App\Controller;

use App\Entity\Comment;
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
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

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
        private NotificationService $notificationService,
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

        $nextLesson = $this->getNextLesson($module, $lesson);
        $prevLesson = $this->getPrevLesson($module, $lesson);

        return $this->render('lesson/show.html.twig', [
            'course' => $course,
            'module' => $module,
            'currentLesson' => $lesson,
            'isSubscribed' => true,
            'completedLessonIds' => $completedLessonIdsByCurrentUser,
            'nextLesson' => $nextLesson,
            'prevLesson' => $prevLesson
        ]);
    }

    private function getPrevLesson(Module $module, Lesson $lesson): ?Lesson
    {
        $sortedArray = $module->getSortedLessons();
        $sortedLessons = new ArrayCollection($sortedArray);

        $currentIndex = $sortedLessons->indexOf($lesson);

        if ($currentIndex !== false && $currentIndex > 0) {
            return $sortedLessons->get((int) $currentIndex - 1);
        }

        return null;
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

    #[Route('/{lessonId}/comment', name: 'add_comment', methods: ['POST'])]
    public function addComment(
        Request $request,
        #[MapEntity(mapping: ['lessonId' => 'id'])]
        Lesson $lesson,
        #[MapEntity(mapping: ['courseSlug' => 'slug'])]
        Course $course,
        #[MapEntity(mapping: ['moduleSlug' => 'slug'])]
        Module $module
    ): Response {
        $content = $request->request->get('content');
        $token = $request->getPayload()->getString('_token');

        if (!$this->isCsrfTokenValid('add_comment', (string) $token)) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        if (empty(trim((string)$content))) {
            return $this->redirectToRoute('lesson_show', [
                'courseSlug' => $course->getSlug(),
                'moduleSlug' => $module->getSlug(),
                'lessonId' => $lesson->getId()
            ]);
        }

        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('lesson_show', [
                'courseSlug' => $course->getSlug(),
                'moduleSlug' => $module->getSlug(),
                'lessonId' => $lesson->getId()
            ]);
        }

        $comment = new Comment();
        $comment->setContent((string)$content);
        $comment->setUser($user);
        $comment->setLesson($lesson);

        $this->entityManager->persist($comment);
        $this->entityManager->flush();

        $this->gamificationService->addXp($user, 5, 'comment_posted');
        $this->addFlash('success', $this->translator->trans('Commentaire ajouté !'));

        return $this->redirectToRoute('lesson_show', [
            'courseSlug' => $course->getSlug(),
            'moduleSlug' => $module->getSlug(),
            'lessonId' => $lesson->getId()
        ]);
    }

    #[Route('/{lessonId}/comment/{parentId}/reply', name: 'add_reply', methods: ['POST'])]
    public function addReply(
        Request $request,
        #[MapEntity(mapping: ['lessonId' => 'id'])]
        Lesson $lesson,
        #[MapEntity(mapping: ['courseSlug' => 'slug'])]
        Course $course,
        #[MapEntity(mapping: ['moduleSlug' => 'slug'])]
        Module $module,
        #[MapEntity(mapping: ['parentId' => 'id'])]
        Comment $parent
    ): Response {
        $content = $request->request->get('content');
        $token = $request->getPayload()->getString('_token');

        if (!$this->isCsrfTokenValid('add_reply', (string) $token)) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        if (empty(trim((string)$content))) {
            return $this->redirectToRoute('lesson_show', [
                'courseSlug' => $course->getSlug(),
                'moduleSlug' => $module->getSlug(),
                'lessonId' => $lesson->getId()
            ]);
        }

        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('lesson_show', [
                'courseSlug' => $course->getSlug(),
                'moduleSlug' => $module->getSlug(),
                'lessonId' => $lesson->getId()
            ]);
        }

        $comment = new Comment();
        $comment->setContent((string)$content);
        $comment->setUser($user);
        $comment->setLesson($lesson);
        $comment->setParent($parent);

        $this->entityManager->persist($comment);
        $this->entityManager->flush();

        $this->gamificationService->addXp($user, 2, 'reply_posted');

        // Notify parent comment author if they are not the same person
        if ($parent->getUser() && $parent->getUser()->getId() !== $user->getId()) {
            $title = "💬 Nouveau message";
            $msg = sprintf("%s a répondu à votre commentaire dans la leçon : %s", $user->getFullname(), $lesson->getTitle());

            if ($this->isGranted('ROLE_COACH') || $this->isGranted('ROLE_ADMIN')) {
                $title = "💡 Un mentor vous a répondu !";
                $msg = sprintf("Bonne nouvelle ! Le mentor %s a répondu à votre question dans la leçon : %s", $user->getFullname(), $lesson->getTitle());
            }

            $this->notificationService->addNotification(
                $parent->getUser(),
                $title,
                $msg,
                $this->generateUrl('lesson_show', [
                    'courseSlug' => $course->getSlug(),
                    'moduleSlug' => $module->getSlug(),
                    'lessonId' => $lesson->getId()
                ], UrlGeneratorInterface::ABSOLUTE_URL)
            );
        }

        $this->addFlash('success', $this->translator->trans('Réponse ajoutée !'));

        return $this->redirectToRoute('lesson_show', [
            'courseSlug' => $course->getSlug(),
            'moduleSlug' => $module->getSlug(),
            'lessonId' => $lesson->getId()
        ]);
    }

    private function getNextLesson(Module $module, Lesson $lesson): ?Lesson
    {
        $sortedArray = $module->getSortedLessons();
        $sortedLessons = new ArrayCollection($sortedArray);

        // On récupère l'index courant
        $currentIndex = $sortedLessons->indexOf($lesson);

        if ($currentIndex !== false) {
            // Puis la leçon suivante
            return $sortedLessons->get((int) $currentIndex + 1);
        }

        return null;
    }

    #[Route('/easter-egg/claim', name: 'claim_easter_egg', methods: ['POST'])]
    public function claimEasterEgg(Request $request, GamificationService $gamificationService): JsonResponse
    {
        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User) {
            return new JsonResponse(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        if (!$this->isCsrfTokenValid('claim_egg', $request->getPayload()->getString('_token'))) {
            return new JsonResponse(['error' => 'Invalid CSRF token'], Response::HTTP_BAD_REQUEST);
        }

        // Award XP and Badge
        $gamificationService->addXp($user, 50, 'easter_egg_found');
        $gamificationService->awardBadge(
            $user, 
            'EASTER_EGG_HUNTER', 
            'Chasseur de Trésors', 
            'Bravo ! Tu as trouvé un secret caché dans les leçons.'
        );

        return new JsonResponse(['success' => true, 'message' => 'Félicitations ! Tu as trouvé un secret ! (+50 XP)']);
    }
}
