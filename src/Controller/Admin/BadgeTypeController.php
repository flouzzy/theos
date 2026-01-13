<?php

namespace App\Controller\Admin;

use App\Entity\BadgeType;
use App\Form\BadgeTypeType;
use App\Repository\BadgeTypeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/badge/type', name: 'admin_badge_type_')]
class BadgeTypeController extends AbstractController
{
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(BadgeTypeRepository $badgeTypeRepository): Response
    {
        return $this->render('admin/badge_type/index.html.twig', [
            'badge_types' => $badgeTypeRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $badgeType = new BadgeType();
        $form = $this->createForm(BadgeTypeType::class, $badgeType);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($badgeType);
            $entityManager->flush();

            return $this->redirectToRoute('admin_badge_type_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/badge_type/new.html.twig', [
            'badge_type' => $badgeType,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(BadgeType $badgeType): Response
    {
        return $this->render('admin/badge_type/show.html.twig', [
            'badge_type' => $badgeType,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, BadgeType $badgeType, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(BadgeTypeType::class, $badgeType);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('admin_badge_type_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/badge_type/edit.html.twig', [
            'badge_type' => $badgeType,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, BadgeType $badgeType, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $badgeType->getId(), $request->request->get('_token'))) {
            $entityManager->remove($badgeType);
            $entityManager->flush();
        }

        return $this->redirectToRoute('admin_badge_type_index', [], Response::HTTP_SEE_OTHER);
    }
}
