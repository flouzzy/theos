<?php

namespace App\Controller\Admin;

use App\Entity\Lesson;
use App\Form\LessonType;
use App\Repository\LessonRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/lesson', name: 'admin_lesson_',)]
class LessonController extends AbstractController
{
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(LessonRepository $lessonRepository): Response
    {
        return $this->render('admin/lesson/index.html.twig', [
            'lessons' => $lessonRepository->findAll(),
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
            $lesson->setAuthor($this->getUser());


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
        if ($this->isCsrfTokenValid('delete' . $lesson->getId(), $request->request->get('_token'))) {
            $entityManager->remove($lesson);
            $entityManager->flush();
        }

        return $this->redirectToRoute('index', [], Response::HTTP_SEE_OTHER);
    }
}
