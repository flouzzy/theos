<?php

namespace App\Controller;

use App\Entity\Course;
use App\Repository\CourseCompletionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/certificate', name: 'certificate_')]
#[IsGranted('IS_AUTHENTICATED')]
class CertificateController extends AbstractController
{
    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(
        Course $course,
        CourseCompletionRepository $courseCompletionRepository
    ): Response {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        // Check if the user has completed the course
        $completion = $courseCompletionRepository->findOneBy([
            'user' => $user,
            'course' => $course,
            'completed' => true
        ]);

        if (!$completion) {
            $this->addFlash('error', 'You have not completed this course yet.');
            return $this->redirectToRoute('course_show', ['slug' => $course->getSlug()]);
        }

        return $this->render('certificate/index.html.twig', [
            'course' => $course,
            'user' => $user,
            'completionDate' => $completion->getUpdatedAt() ?? new \DateTime(), // Use completion date or now
        ]);
    }
}
