<?php

namespace App\Controller;

use App\Entity\Course;
use App\Entity\CourseCompletion;
use App\Repository\CompletionRepository;
use App\Repository\CourseCompletionRepository;
use App\Repository\CourseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/courses', name: 'course_')]
class CourseController extends AbstractController
{
    #[Route('/', name: 'index')]
    public function index(CourseRepository $courseRepository): Response
    {

        return $this->render('course/index.html.twig', [
            'courses' => $courseRepository->findBy(['status' => ['published', 'progress']]),
        ]);
    }

    #[Route('/{slug}', name: 'show')]
    public function show(Course $course, CompletionRepository $completionRepository, CourseCompletionRepository $courseCompletionRepository): Response
    {
        // Si le cours n'est pas publié et que l'on en est pas le propriétaire, on ne l'affiche pas
        if ($course->getAuthor() != $this->getUser() && $course->getStatus() != 'published') {
            $this->addFlash('warning', 'This course is not available');
            return $this->redirectToRoute('course_index');
        }

        // Récupérations des leçons terminées
        $completedLessons = $completionRepository->findBy(['user' => $this->getUser(), 'completed' => true]);
        $completedLessonIdsByCurrentUser = [];
        foreach ($completedLessons as $completed) {
            $completedLessonIdsByCurrentUser[] = $completed->getLesson()->getId();
        }

        return $this->render('course/show.html.twig', [
            'course' => $course,
            'isSubscribed' => $this->getUser() ? $course->isUserSubscribed($this->getUser()) : false,
            'completedLessonIds' => $completedLessonIdsByCurrentUser
        ]);
    }

    #[Route('/{slug}/subscribe', name: 'subscribe')]
    public function subscribeToCourse(Course $course, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED');

        /**
         * @var \App\Entity\User $currentUser
         */
        // Subscribe to course
        $currentUser = $this->getUser();
        $currentUser->subscribeToCourse($course);

        $entityManager->flush();

        $this->addFlash('app', 'Congratulations! Your registration has been processed');

        return $this->redirectToRoute('course_show', ['slug' => $course->getSlug()]);
    }

    #[Route('/{slug}/unsubscribe', name: 'unsubscribe')]
    public function unsubscribeFromCourse(Course $course, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED');

        /**
         * @var \App\Entity\User $currentUser
         */
        // Subscribe to course
        $currentUser = $this->getUser();
        $currentUser->unsubscribeFromCourse($course);

        $entityManager->flush();

        $this->addFlash('app', 'You are no longer taking this course');

        return $this->redirectToRoute('course_show', ['slug' => $course->getSlug()]);
    }
}
