<?php

namespace App\Controller\Admin;

use App\Entity\Lesson;
use App\Form\LessonType;
use App\Repository\LessonRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

use App\Message\GenerateLessonAudioMessage;
use App\Message\GenerateLessonEmbeddingMessage;
use Symfony\Component\Messenger\MessageBusInterface;

#[Route('/admin/lesson', name: 'admin_lesson_',)]
class LessonController extends AbstractController
{
    #[Route('/{id}/generate-embedding', name: 'generate_embedding', methods: ['POST'])]
    public function generateEmbedding(Lesson $lesson, MessageBusInterface $bus): Response
    {
        $lessonId = $lesson->getId();
        if (null === $lessonId) {
            throw new \LogicException('Lesson must be persisted before generating embedding.');
        }
        $bus->dispatch(new GenerateLessonEmbeddingMessage($lessonId));

        $this->addFlash('success', 'La génération de l\'embedding a été lancée. Les recommandations seront bientôt à jour.');

        return $this->redirectToRoute('admin_lesson_edit', ['id' => $lesson->getId()]);
    }

    #[Route('/{id}/generate-audio', name: 'generate_audio', methods: ['POST'])]
    public function generateAudio(Lesson $lesson, MessageBusInterface $bus): Response
    {
        if (!$lesson->getContent()) {
            $this->addFlash('error', 'La leçon doit avoir du contenu pour générer un audio.');
            return $this->redirectToRoute('admin_lesson_edit', ['id' => $lesson->getId()]);
        }

        $lessonId = $lesson->getId();
        if (null === $lessonId) {
            throw new \LogicException('Lesson must be persisted before generating audio.');
        }
        $bus->dispatch(new GenerateLessonAudioMessage($lessonId));

        $this->addFlash('success', 'La génération de l\'audio a été lancée en tâche de fond. Elle sera disponible dans quelques instants.');

        return $this->redirectToRoute('admin_lesson_edit', ['id' => $lesson->getId()]);
    }

    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(LessonRepository $lessonRepository): Response
    {
        return $this->render('admin/lesson/index.html.twig', [
            'lessons' => $lessonRepository->findAllWithModules(),
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $lesson = new Lesson();
        $form = $this->createForm(LessonType::class, $lesson, [
            'action' => $this->generateUrl('admin_lesson_new'),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Set author
            /** @var \App\Entity\User|null $user */
            $user = $this->getUser();
            if (!$user instanceof \App\Entity\User) {
                throw $this->createAccessDeniedException();
            }
            $lesson->setAuthor($user);


            $entityManager->persist($lesson);
            $entityManager->flush();

            $this->addFlash('success', 'New item added');

            return $this->redirectToRoute('admin_lesson_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/lesson/new.html.twig', [
            'lesson' => $lesson,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Lesson $lesson): Response
    {
        return $this->render('admin/lesson/show.html.twig', [
            'lesson' => $lesson,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Lesson $lesson, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(LessonType::class, $lesson, [
            'action' => $this->generateUrl('admin_lesson_edit', ['id' => $lesson->getId()]),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Your data has been saved');

            return $this->redirectToRoute('admin_lesson_edit', ['id' => $lesson->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/lesson/edit.html.twig', [
            'lesson' => $lesson,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, Lesson $lesson, EntityManagerInterface $entityManager): Response
    {
        $token = $request->request->get('_token');
        if (is_string($token) && $this->isCsrfTokenValid('delete' . $lesson->getId(), $token)) {
            $entityManager->remove($lesson);
            $entityManager->flush();

            $this->addFlash('success', 'Item deleted');
        }

        return $this->redirectToRoute('index', [], Response::HTTP_SEE_OTHER);
    }
}
