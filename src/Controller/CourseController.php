<?php

namespace App\Controller;

use App\Entity\Course;
use App\Entity\CourseCompletion;
use App\Entity\User;
use App\Event\CourseSubscribedEvent;
use App\Repository\CohortRepository;
use App\Repository\CompletionRepository;
use App\Repository\CourseCompletionRepository;
use App\Repository\CourseRepository;
use App\Service\CohortSession;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/courses', name: 'course_')]
class CourseController extends AbstractController
{
    #[Route('/', name: 'index')]
    public function index(CourseRepository $courseRepository, CohortRepository $cohortRepository, CohortSession $cohortSession): Response
    {
        $cohorts = [];
        if ($this->isGranted('ROLE_ADMIN')) {
             $cohorts = $cohortRepository->findAll();
        } elseif ($this->getUser()) {
             /** @var User $user */
             $user = $this->getUser();
             $cohorts = $user->getCohorts();
        }

        $subscribedCourseIds = [];
        if ($this->getUser()) {
            /** @var \App\Entity\User $user */
            $user = $this->getUser();
            foreach ($user->getCourses() as $course) {
                $subscribedCourseIds[] = $course->getId();
            }
        }

        // Récupère la cohorte active via le service
        $activeCohort = $cohortSession->getSelectedCohort();

        return $this->render('course/index.html.twig', [
            'courses' => $courseRepository->findCoursesByVisibilityAndCohort($activeCohort),
            'cohorts' => $cohorts,
            'subscribedCourseIds' => $subscribedCourseIds
        ]);
    }

    #[Route('/{slug}', name: 'show')]
    public function show(#[MapEntity(mapping: ['slug' => 'slug'])] Course $course, CompletionRepository $completionRepository, CourseCompletionRepository $courseCompletionRepository): Response
    {
        // Si le cours n'est pas publié et que l'on en est pas le propriétaire, on ne l'affiche pas
        if ($course->getAuthor() != $this->getUser() && $course->getStatus() != 'published') {
            $this->addFlash('warning', 'This course is not available');
            return $this->redirectToRoute('course_index');
        }

        // Récupérations des leçons terminées
        $completedLessonIdsByCurrentUser = [];
        if ($this->getUser()) {
            /** @var User $user */
            $user = $this->getUser();
            $completedLessonIdsByCurrentUser = $completionRepository->findCompletedLessonIdsByCourse($user, $course);
        }

        $isCompleted = false;
        if ($this->getUser()) {
            $completion = $courseCompletionRepository->findOneBy([
                'user' => $this->getUser(),
                'course' => $course,
                'completed' => true
            ]);
            $isCompleted = (bool) $completion;
        }

        /** @var User|null $user */
        $user = $this->getUser();

        return $this->render('course/show.html.twig', [
            'course' => $course,
            'isSubscribed' => $user ? $course->isUserSubscribed($user) : false,
            'completedLessonIds' => $completedLessonIdsByCurrentUser,
            'isCompleted' => $isCompleted
        ]);
    }

    #[Route('/{slug}/subscribe', name: 'subscribe')]
    public function subscribeToCourse(Course $course, EntityManagerInterface $entityManager, EventDispatcherInterface $dispatcher): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED');

        /**
         * @var \App\Entity\User $currentUser
         */
        // Subscribe to course
        $currentUser = $this->getUser();
        $currentUser->subscribeToCourse($course);

        $entityManager->flush();

        $dispatcher->dispatch(new CourseSubscribedEvent($course, $currentUser));

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
