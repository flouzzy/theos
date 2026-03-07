<?php

namespace App\Controller\Admin;

use App\Entity\Evaluation;
use App\Form\EvaluationType;
use App\Repository\EvaluationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/evaluation')]
final class EvaluationController extends AbstractController
{
    #[Route(name: 'app_admin_evaluation_index', methods: ['GET'])]
    public function index(EvaluationRepository $evaluationRepository): Response
    {
        return $this->render('admin/evaluation/index.html.twig', [
            'evaluations' => $evaluationRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_admin_evaluation_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, \App\Service\GamificationService $gamificationService): Response
    {
        $evaluation = new Evaluation();
        $form = $this->createForm(EvaluationType::class, $evaluation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($evaluation);
            $entityManager->flush();

            // Give XP based on the score over 20 as base. (e.g. 15/20 => 15 XP)
            // But scaled to whatever maxScore is. So: (score / maxScore) * 20 XP
            $xp = (int) (($evaluation->getScore() / $evaluation->getMaxScore()) * 20);
            $gamificationService->addXp($evaluation->getUser(), max(5, $xp), 'Evaluation: ' . $evaluation->getTitle());

            $this->addFlash('success', 'Évaluation ajoutée avec succès.');

            return $this->redirectToRoute('app_admin_evaluation_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/evaluation/new.html.twig', [
            'evaluation' => $evaluation,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_evaluation_show', methods: ['GET'])]
    public function show(Evaluation $evaluation): Response
    {
        return $this->render('admin/evaluation/show.html.twig', [
            'evaluation' => $evaluation,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_admin_evaluation_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Evaluation $evaluation, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(EvaluationType::class, $evaluation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_admin_evaluation_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/evaluation/edit.html.twig', [
            'evaluation' => $evaluation,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_evaluation_delete', methods: ['POST'])]
    public function delete(Request $request, Evaluation $evaluation, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$evaluation->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($evaluation);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_admin_evaluation_index', [], Response::HTTP_SEE_OTHER);
    }
}
