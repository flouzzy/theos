<?php

namespace App\Controller;

use App\Entity\Course;
use App\Repository\CourseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/courses', name: 'course_')]
class CourseController extends AbstractController
{
    #[Route('/', name: 'index')]
    public function index(CourseRepository $courseRepository): Response
    {
        return $this->render('course/index.html.twig', [
            'courses' => $courseRepository->findAll(),
        ]);
    }

    #[Route('/{slug}', name: 'show')]
    public function show(Course $course): Response
    {
        return $this->render('course/show.html.twig', [
            'course' => $course,
            'isSubscribed' => $this->getUser() ? $course->isUserSubscribed($this->getUser()) : false
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

        $this->addFlash('success', 'Congratulations! Your registration has been processed');

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

        $this->addFlash('success', 'You are no longer taking this course');

        return $this->redirectToRoute('course_show', ['slug' => $course->getSlug()]);
    }
}
