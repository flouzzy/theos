<?php

namespace App\Controller;

use App\Entity\Course;
use App\Entity\Lesson;
use App\Entity\Module;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/courses/{courseSlug}/{moduleSlug}/lesson', name: 'lesson_')]
class LessonController extends AbstractController
{
    #[Route('/{id}', name: 'show')]
    public function show(
        Lesson $lesson,

        #[MapEntity(mapping: ['courseSlug' => 'slug'])]
        Course $course,

        #[MapEntity(mapping: ['moduleSlug' => 'slug'])]
        Module $module

    ): Response {
        return $this->render('lesson/show.html.twig', [
            'course' => $course,
            'module' => $module,
            'currentLesson' => $lesson,
        ]);
    }

    #[Route('/{id}/complete', name: 'complete')]
    public function markLessonAsCompleted(
        Lesson $lesson,
        EntityManagerInterface $entityManager,

        #[MapEntity(mapping: ['courseSlug' => 'slug'])]
        Course $course,

        #[MapEntity(mapping: ['moduleSlug' => 'slug'])]
        Module $module
    ): Response {

        $this->denyAccessUnlessGranted('IS_AUTHENTICATED');

        $lesson->setCompleted(true);

        $entityManager->flush();

        /**
         * @var \App\Entity\Lesson $nextLesson 
         */

        // Go to next lesson
        $lessons = $module->getLessons();
        $nextIndex = $lessons->indexOf($lesson) + 1;

        $nextLesson  = $lessons->get($nextIndex);

        if ($nextLesson) {
            // Go to next lesson
            $this->addFlash('succes', 'Lesson completed');
            return $this->redirectToRoute('lesson_show', [
                'courseSlug' => $course->getSlug(),
                'moduleSlug' => $module->getSlug(),
                'id' => $nextLesson->getId()
            ]);
        } else {
            $this->addFlash('success', 'Module completed');
            // Go to next lesson module
            return $this->redirectToRoute('course_show', [
                'slug' => $course->getSlug()
            ]);
        }
    }
}
