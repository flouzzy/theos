<?php

namespace App\Controller;

use App\Entity\Course;
use App\Entity\Lesson;
use App\Entity\Module;
use App\Entity\Note;
use App\Form\NoteType;
use App\Repository\NoteRepository;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/note', name: 'note_')]
#[IsGranted('IS_AUTHENTICATED')]
class NoteController extends AbstractController
{
    public function __construct(
        private NotificationService $notificationService
    ) {}

    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(
        NoteRepository $noteRepository
    ): Response {
        $notes = $noteRepository->findByUser($this->getUser());
        return $this->render('note/index.html.twig', [
            // Return all user notes from all lessons
            'notes' => $notes
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(
        Note $note
    ): Response {
        return $this->render('note/show.html.twig', [
            // Return all user notes from all lessons
            'note' => $note
        ]);
    }

    #[Route('/lesson/{lessonId}', name: 'index_lesson', methods: ['GET', 'POST'])]
    public function indexByLesson(
        #[MapEntity(mapping: ['lessonId' => 'id'])]
        Lesson $lesson,

        NoteRepository $noteRepository
    ): Response {
        /**
         * @var \App\Entity\User $currentUser
         */
        $currentUser = $this->getUser();
        $notes = $noteRepository->findUserNotesByLesson($lesson, $currentUser);
        return $this->render('note/_list.html.twig', [
            // Return all user notes form the current lesson
            'notes' => $notes
        ]);
    }

    #[Route('/{courseSlug}/{moduleSlug}/{lessonId}', name: 'show_lesson', methods: ['GET', 'POST'])]
    public function showOrAdd(

        #[MapEntity(mapping: ['courseSlug' => 'slug'])]
        Course $course,

        #[MapEntity(mapping: ['moduleSlug' => 'slug'])]
        Module $module,

        #[MapEntity(mapping: ['lessonId' => 'id'])]
        Lesson $lesson,

        Request $request,

        EntityManagerInterface $entityManager
    ): Response {

        $note = new Note();
        $form = $this->createForm(NoteType::class, $note, [
            'action' => $this->generateUrl('note_show_lesson', [
                'courseSlug' => $course->getSlug(),
                'moduleSlug' => $module->getSlug(),
                'lessonId' => $lesson->getId()
            ]),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // New note for current user
            /** @var \App\Entity\User|null $user */
            $user = $this->getUser();
            if (!$user instanceof \App\Entity\User) {
                throw $this->createAccessDeniedException();
            }
            $note->setUser($user);

            $note->setLesson($lesson);

            $entityManager->persist($note);
            $entityManager->flush();

            return $this->redirectToRoute('lesson_show', [
                'courseSlug' => $course->getSlug(),
                'moduleSlug' => $module->getSlug(),
                'lessonId' => $lesson->getId()
            ]);
        }

        return $this->render('note/_show.html.twig', [
            'note' => $note,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Note $note, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(NoteType::class, $note, [
            'action' => $this->generateUrl('note_edit', ['id' => $note->getId()]),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('note_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('note/edit.html.twig', [
            'note' => $note,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, Note $note, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $note->getId(), $request->getPayload()->getString('_token'))) {

            $entityManager->remove($note);
            $entityManager->flush();
        }

        return $this->redirectToRoute('note_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/like', name: 'like', methods: ['POST'])]
    public function like(Note $note, Request $request, EntityManagerInterface $entityManager): Response
    {
        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User) {
            throw $this->createAccessDeniedException();
        }

        if (!$this->isCsrfTokenValid('like_note' . $note->getId(), $request->getPayload()->getString('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        if ($note->getLikes()->contains($user)) {
            $note->removeLike($user);
        } else {
            $note->addLike($user);
            
            // Check for 5 likes milestone
            if ($note->getLikes()->count() === 5) {
                $this->notificationService->addNotification(
                    $note->getUser(),
                    "🎉 Ta note est populaire !",
                    sprintf("Ta note sur la leçon '%s' a déjà reçu 5 likes. Félicitations !", $note->getLesson()->getTitle()),
                    $this->generateUrl('lesson_show', [
                        'courseSlug' => $note->getLesson()->getModule()->getCourses()->first()->getSlug(),
                        'moduleSlug' => $note->getLesson()->getModule()->getSlug(),
                        'lessonId' => $note->getLesson()->getId()
                    ], UrlGeneratorInterface::ABSOLUTE_URL)
                );
            }
        }

        $entityManager->flush();

        return $this->redirect($request->headers->get('referer') ?: $this->generateUrl('note_index'));
    }
}
