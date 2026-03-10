<?php

namespace App\Controller;

use App\Entity\Assignment;
use App\Entity\AssignmentSubmission;
use App\Entity\PeerReview;
use App\Service\GamificationService;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

use App\Service\MediaManager;

#[Route('/assignment', name: 'assignment_')]
#[IsGranted('IS_AUTHENTICATED')]
class AssignmentController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private GamificationService $gamificationService,
        private NotificationService $notificationService,
        private MediaManager $mediaManager
    ) {}

    #[Route('/{id}', name: 'show')]
    public function show(Assignment $assignment): Response
    {
        $user = $this->getUser();
        $submission = $this->entityManager->getRepository(AssignmentSubmission::class)->findOneBy([
            'assignment' => $assignment,
            'user' => $user
        ]);

        return $this->render('assignment/show.html.twig', [
            'assignment' => $assignment,
            'submission' => $submission,
        ]);
    }

    #[Route('/{id}/submit', name: 'submit', methods: ['POST'])]
    public function submit(Assignment $assignment, Request $request): Response
    {
        $submittedToken = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('submit_assignment' . $assignment->getId(), $submittedToken)) {
            $this->addFlash('error', 'Invalid CSRF token.');
            return $this->redirectToRoute('assignment_show', ['id' => $assignment->getId()]);
        }

        $user = $this->getUser();
        $content = $request->request->get('content');

        if (!$content) {
            $this->addFlash('error', 'Content cannot be empty');
            return $this->redirectToRoute('assignment_show', ['id' => $assignment->getId()]);
        }

        $submission = $this->entityManager->getRepository(AssignmentSubmission::class)->findOneBy([
            'assignment' => $assignment,
            'user' => $user
        ]);

        if (!$submission) {
            $submission = new AssignmentSubmission();
            $submission->setAssignment($assignment);
            $submission->setUser($user);
            $this->gamificationService->addXp($user, 20, 'assignment_submitted');

            // Trigger #6: Browser notification for new peer review requests
            $cohort = $user->getCurrentCohort();
            if ($cohort) {
                 foreach ($cohort->getUsers() as $peer) {
                     if ($peer->getId() !== $user->getId()) {
                         $this->notificationService->addNotification(
                             $peer,
                             "🤝 Nouvelle revue disponible",
                             sprintf("%s a soumis un travail. Viens l'aider en le corrigeant !", $user->getFullname()),
                             $this->generateUrl('assignment_review_pool', ['id' => $assignment->getId()], UrlGeneratorInterface::ABSOLUTE_URL)
                         );
                     }
                 }
            }
        }

        $submission->setContent($content);
        $submission->setStatus('submitted');

        $file = $request->files->get('submission_file');
        if ($file) {
            $filePath = $this->mediaManager->upload($file, 'assignments');
            $submission->setFilePath($filePath);
        }

        $this->entityManager->persist($submission);
        $this->entityManager->flush();

        $this->addFlash('success', 'Assignment submitted successfully!');

        return $this->redirectToRoute('assignment_show', ['id' => $assignment->getId()]);
    }

    #[Route('/{id}/review', name: 'review_pool')]
    public function reviewPool(Assignment $assignment): Response
    {
        // Find a submission that is not mine and I haven't reviewed yet
        $user = $this->getUser();

        $qb = $this->entityManager->getRepository(AssignmentSubmission::class)->createQueryBuilder('s');
        $qb->where('s.assignment = :assignment')
           ->andWhere('s.user != :user')
           ->andWhere('s.status = :status')
           ->leftJoin('s.reviews', 'r', 'WITH', 'r.reviewer = :user')
           ->andWhere('r.id IS NULL')
           ->setParameter('assignment', $assignment)
           ->setParameter('user', $user)
           ->setParameter('status', 'submitted')
           ->setMaxResults(1);

        $submissionToReview = $qb->getQuery()->getOneOrNullResult();

        if (!$submissionToReview) {
            $this->addFlash('info', 'No pending submissions to review at the moment.');
            return $this->redirectToRoute('assignment_show', ['id' => $assignment->getId()]);
        }

        return $this->render('assignment/review.html.twig', [
            'assignment' => $assignment,
            'submission' => $submissionToReview
        ]);
    }

    #[Route('/{id}/review/{submissionId}', name: 'submit_review', methods: ['POST'])]
    public function submitReview(Assignment $assignment, int $submissionId, Request $request): Response
    {
        $submittedToken = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('submit_review' . $submissionId, $submittedToken)) {
            $this->addFlash('error', 'Invalid CSRF token.');
            return $this->redirectToRoute('assignment_review_pool', ['id' => $assignment->getId()]);
        }

        $user = $this->getUser();
        $score = (int) $request->request->get('score');
        $feedback = $request->request->get('feedback');

        $submission = $this->entityManager->getRepository(AssignmentSubmission::class)->find($submissionId);

        if (!$submission || $submission->getAssignment() !== $assignment) {
            throw $this->createNotFoundException('Submission not found');
        }

        if ($submission->getUser()->getId() === $user->getId()) {
             $this->addFlash('error', 'You cannot review your own submission');
             return $this->redirectToRoute('assignment_show', ['id' => $assignment->getId()]);
        }

        $existingReview = $this->entityManager->getRepository(PeerReview::class)->findOneBy([
            'submission' => $submission,
            'reviewer' => $user
        ]);

        if ($existingReview) {
            $this->addFlash('error', 'You have already reviewed this submission.');
            return $this->redirectToRoute('assignment_review_pool', ['id' => $assignment->getId()]);
        }

        $review = new PeerReview();
        $review->setSubmission($submission);
        $review->setReviewer($user);
        $review->setFeedback($feedback);

        $totalScore = 0;
        $rubric = $assignment->getRubricEntity();
        if ($rubric) {
            foreach ($rubric->getCriteria() as $criterion) {
                $scoreValue = (int) $request->request->get('score_' . $criterion->getId());
                $peerScore = new PeerReviewScore();
                $peerScore->setCriterion($criterion);
                $peerScore->setScore($scoreValue);
                $review->addScore($peerScore);
                $totalScore += $scoreValue;
            }
        } else {
            $totalScore = (int) $request->request->get('score');
        }

        $review->setScore($totalScore);

        $this->entityManager->persist($review);
        $this->entityManager->flush();

        $this->gamificationService->addXp($user, 15, 'peer_review_completed');

        // Notify author
        $this->notificationService->addNotification(
            $submission->getUser(),
            "📝 Ton travail a été corrigé",
            sprintf("%s a corrigé ton travail pour l'exercice : %s", $user->getFullname(), $assignment->getTitle()),
            $this->generateUrl('assignment_show', ['id' => $assignment->getId()], UrlGeneratorInterface::ABSOLUTE_URL)
        );

        $this->addFlash('success', 'Review submitted successfully!');

        return $this->redirectToRoute('assignment_show', ['id' => $assignment->getId()]);
    }
}
