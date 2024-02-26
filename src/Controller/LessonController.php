<?php

namespace App\Controller;

use App\Entity\Completion;
use App\Entity\Course;
use App\Entity\Lesson;
use App\Entity\Module;
use App\Repository\CompletionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/courses/{courseSlug}/{moduleSlug}/lesson', name: 'lesson_')]
#[IsGranted('IS_AUTHENTICATED', message: 'You must be logged in to view this lesson')]
class LessonController extends AbstractController
{
    public function __construct(private CompletionRepository $completionRepository)
    {
    }
    #[Route('/{id}', name: 'show')]
    public function show(
        Lesson $lesson,

        #[MapEntity(mapping: ['courseSlug' => 'slug'])]
        Course $course,

        #[MapEntity(mapping: ['moduleSlug' => 'slug'])]
        Module $module

    ): Response {

        $completedLessons = $this->completionRepository->findBy(['user' => $this->getUser(), 'completed' => true]);
        // Vous pouvez optimiser cette partie selon vos besoins
        $completedLessonIdsByCurrentUser = [];
        foreach ($completedLessons as $completed) {
            $completedLessonIdsByCurrentUser[] = $completed->getLesson()->getId();
        }

        return $this->render('lesson/show.html.twig', [
            'course' => $course,
            'module' => $module,
            'currentLesson' => $lesson,
            'isSubscribed' => $this->getUser() ? $course->isUserSubscribed($this->getUser()) : false,
            'completedLessonIds' => $completedLessonIdsByCurrentUser,
        ]);
    }

    #[Route('/{id}/complete/{completed}', name: 'complete')]
    public function markLessonAsCompleted(
        Lesson $lesson,
        EntityManagerInterface $entityManager,

        #[MapEntity(mapping: ['courseSlug' => 'slug'])]
        Course $course,

        #[MapEntity(mapping: ['moduleSlug' => 'slug'])]
        Module $module,
        $completed = 0
    ): Response {

        if (!$course->isUserSubscribed($this->getUser())) {
            // Si on est pas inscrit au cours, impossible de valider une leçon
            $this->addFlash('danger', 'You must first register for the course');

            // Go to course page
            return $this->redirectToRoute('course_show', [
                'slug' => $course->getSlug()
            ]);
        }

        // Toogle completion status
        $completed = !(boolval($completed));

        // Save completion status for current user
        // Check if completion already exist
        $user = $this->getUser();
        $completion = $this->completionRepository->findOneBy(['user' => $user, 'lesson' => $lesson]);
        if (!$completion) {
            $completion = new Completion();
        }
        $completion->setUser($this->getUser());
        $completion->setLesson($lesson);
        $completion->setCompleted($completed);

        $entityManager->persist($completion);
        $entityManager->flush();

        if ($completed == false) {
            // Show current lesson
            $this->addFlash('success', 'Lesson marked as unread');
            return $this->redirectToRoute('lesson_show', [
                'courseSlug' => $course->getSlug(),
                'moduleSlug' => $module->getSlug(),
                'id' => $lesson->getId()
            ]);
        }

        /**
         * @var \App\Entity\Lesson $nextLesson 
         */

        // Go to next lesson
        $lessons = $module->getLessons();
        $nextIndex = $lessons->indexOf($lesson) + 1;

        $nextLesson  = $lessons->get($nextIndex);

        if ($nextLesson) {
            // Go to next lesson
            $this->addFlash('success', 'Lesson completed');
            return $this->redirectToRoute('lesson_show', [
                'courseSlug' => $course->getSlug(),
                'moduleSlug' => $module->getSlug(),
                'id' => $nextLesson->getId()
            ]);
        } else {
            $this->addFlash('success', 'Module completed');
            // Go to next module
            return $this->redirectToRoute('course_show', [
                'slug' => $course->getSlug()
            ]);
        }
    }
}
