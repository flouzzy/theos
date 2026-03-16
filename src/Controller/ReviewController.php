<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Review;
use App\Entity\User;
use App\Repository\CourseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/review', name: 'review_')]
#[IsGranted('IS_AUTHENTICATED')]
class ReviewController extends AbstractController
{
    #[Route('/add/{courseId}', name: 'add', methods: ['POST'])]
    public function add(int $courseId, Request $request, EntityManagerInterface $em, CourseRepository $repo): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $course = $repo->find($courseId);
        
        if (!$course) {
            throw $this->createNotFoundException();
        }

        if (!$this->isCsrfTokenValid('add_review' . $courseId, $request->getPayload()->getString('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $review = new Review();
        $review->setCourse($course);
        $review->setAuthor($user);
        $review->setComment($request->getPayload()->getString('comment'));
        $review->setRating((int)$request->getPayload()->get('rating', 5));
        
        $em->persist($review);
        $em->flush();

        $this->addFlash('success', 'Votre avis a été publié !');

        return $this->redirectToRoute('course_show', ['slug' => $course->getSlug()]);
    }
}
