<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\StudyGroup;
use App\Repository\StudyGroupRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/study-groups', name: 'study_group_')]
#[IsGranted('IS_AUTHENTICATED')]
class StudyGroupController extends AbstractController
{
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(StudyGroupRepository $repo): Response
    {
        return $this->render('study_group/index.html.twig', [
            'groups' => $repo->findAll(),
        ]);
    }

    #[Route('/create', name: 'create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em): Response
    {
        $group = new StudyGroup();
        $group->setName($request->getPayload()->getString('name'));
        $group->setTopic($request->getPayload()->getString('topic'));
        $group->setCreator($this->getUser());
        $group->setMaxMembers((int)$request->getPayload()->get('maxMembers', 10));

        $em->persist($group);
        $em->flush();

        $this->addFlash('success', 'Groupe d\'étude créé avec succès !');

        return $this->redirectToRoute('study_group_index');
    }
}
