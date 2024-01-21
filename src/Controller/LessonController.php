<?php

namespace App\Controller;

use App\Entity\Course;
use App\Entity\Lesson;
use App\Entity\Module;
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
        dump('Hey', $course, $module, $lesson);
        return $this->render('lesson/show.html.twig', [
            'course' => $course,
            'module' => $module,
            'currentLesson' => $lesson,
        ]);
    }
}
