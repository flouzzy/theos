<?php

namespace App\Controller\Admin;

use App\Entity\Cohort;
use App\Form\CohortType;
use App\Repository\CohortRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/cohort', name: 'admin_cohort_')]
class CohortController extends AbstractController
{
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(CohortRepository $cohortRepository): Response
    {
        return $this->render('admin/cohort/index.html.twig', [
            'cohorts' => $cohortRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $cohort = new Cohort();
        $form = $this->createForm(CohortType::class, $cohort);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($cohort);
            $entityManager->flush();

            return $this->redirectToRoute('admin_cohort_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/cohort/new.html.twig', [
            'cohort' => $cohort,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Cohort $cohort): Response
    {
        return $this->render('admin/cohort/show.html.twig', [
            'cohort' => $cohort,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Cohort $cohort, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CohortType::class, $cohort);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('admin_cohort_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/cohort/edit.html.twig', [
            'cohort' => $cohort,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, Cohort $cohort, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $cohort->getId(), $request->request->get('_token'))) {
            $entityManager->remove($cohort);
            $entityManager->flush();
        }

        return $this->redirectToRoute('admin_cohort_index', [], Response::HTTP_SEE_OTHER);
    }
}
