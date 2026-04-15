<?php

namespace App\Controller\Admin;

use App\Entity\Cohort;
use App\Form\CohortType;
use App\Repository\CohortRepository;
use App\Event\CohortContentUnlockedEvent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
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
    public function edit(Request $request, Cohort $cohort, EntityManagerInterface $entityManager, EventDispatcherInterface $dispatcher): Response
    {
        $originalCourses = $cohort->getCourses()->toArray();
        $form = $this->createForm(CohortType::class, $cohort);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $newCourses = $cohort->getCourses();

            // Determine if any new courses were added
            $hasNewCourses = false;
            foreach ($newCourses as $course) {
                if (!in_array($course, $originalCourses, true)) {
                    $hasNewCourses = true;
                    break;
                }
            }

            // Eagerly fetch users if needed, avoiding N+1 query when the event is dispatched
            if ($hasNewCourses) {
                $entityManager->getRepository(Cohort::class)->createQueryBuilder('c')
                    ->leftJoin('c.users', 'u')
                    ->addSelect('u')
                    ->where('c.id = :id')
                    ->setParameter('id', $cohort->getId())
                    ->getQuery()
                    ->getResult();
            }

            foreach ($newCourses as $course) {
                if (!in_array($course, $originalCourses, true)) {
                    $dispatcher->dispatch(new CohortContentUnlockedEvent($cohort, $course));
                }
            }

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
        if ($this->isCsrfTokenValid('delete' . $cohort->getId(), $request->request->getString('_token'))) {
            $entityManager->remove($cohort);
            $entityManager->flush();
        }

        return $this->redirectToRoute('admin_cohort_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/regenerate-token', name: 'regenerate_token', methods: ['GET'])]
    public function regenerateToken(Cohort $cohort, EntityManagerInterface $entityManager): Response
    {
        $cohort->setInvitationToken(bin2hex(random_bytes(16)));
        $entityManager->flush();

        $this->addFlash('success', 'Le lien d\'invitation a été régénéré avec succès.');

        return $this->redirectToRoute('admin_cohort_edit', ['id' => $cohort->getId()]);
    }
}
