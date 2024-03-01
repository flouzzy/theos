<?php

namespace App\Controller\Admin;

use App\Entity\Module;
use App\Form\ModuleType;
use App\Repository\ModuleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/module', name: 'admin_module_')]
class ModuleController extends AbstractController
{
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(ModuleRepository $moduleRepository): Response
    {
        return $this->render('admin/module/index.html.twig', [
            'modules' => $moduleRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $module = new Module();
        $form = $this->createForm(ModuleType::class, $module, [
            'action' => $this->generateUrl('admin_module_new'),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Set author
            $module->setAuthor($this->getUser());


            $entityManager->persist($module);
            $entityManager->flush();

            $this->addFlash('success', 'New item added');

            return $this->redirectToRoute('admin_module_edit', ['id' => $module->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/module/new.html.twig', [
            'module' => $module,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Module $module): Response
    {
        return $this->render('admin/module/show.html.twig', [
            'module' => $module,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Module $module, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ModuleType::class, $module, [
            'action' => $this->generateUrl('admin_module_edit', ['id' => $module->getId()]),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Your data has been saved');

            return $this->redirectToRoute('admin_module_edit', ['id' => $module->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/module/edit.html.twig', [
            'module' => $module,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, Module $module, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $module->getId(), $request->request->get('_token'))) {
            $entityManager->remove($module);
            $entityManager->flush();

            $this->addFlash('success', 'Item deleted');
        }

        return $this->redirectToRoute('admin_module_index', [], Response::HTTP_SEE_OTHER);
    }
}
