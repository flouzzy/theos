<?php

namespace App\Controller;

use App\Entity\Course;
use App\Entity\Lesson;
use App\Entity\Module;
use App\Entity\Note;
use App\Form\NoteType;
use App\Repository\NoteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/note', name: 'note_')]
#[IsGranted('IS_AUTHENTICATED')]
class NoteController extends AbstractController
{

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

    #[Route('/{courseSlug}/{moduleSlug}/{id}', name: 'show_lesson', methods: ['GET', 'POST'])]
    public function showOrAdd(

        #[MapEntity(mapping: ['courseSlug' => 'slug'])]
        Course $course,

        #[MapEntity(mapping: ['moduleSlug' => 'slug'])]
        Module $module,

        #[MapEntity(mapping: ['id' => 'id'])]
        Lesson $lesson,

        Request $request,

        EntityManagerInterface $entityManager
    ): Response {

        $note = new Note();
        $form = $this->createForm(NoteType::class, $note, [
            'action' => $this->generateUrl('note_show_lesson', [
                'courseSlug' => $course->getSlug(),
                'moduleSlug' => $module->getSlug(),
                'id' => $lesson->getId()
            ]),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // New note for current user
            $note->setUser($this->getUser());

            $note->setLesson($lesson);

            $entityManager->persist($note);
            $entityManager->flush();

            return $this->redirectToRoute('lesson_show', [
                'courseSlug' => $course->getSlug(),
                'moduleSlug' => $module->getSlug(),
                'id' => $lesson->getId()
            ]);

            return $this->redirectToRoute('lesson_show', [], Response::HTTP_SEE_OTHER);
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
        if ($this->isCsrfTokenValid('delete' . $note->getId(), $request->request->get('_token'))) {
            $entityManager->remove($note);
            $entityManager->flush();
        }

        return $this->redirectToRoute('note_index', [], Response::HTTP_SEE_OTHER);
    }
}
